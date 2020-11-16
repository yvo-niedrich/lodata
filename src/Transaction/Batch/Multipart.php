<?php

namespace Flat3\Lodata\Transaction\Batch;

use Flat3\Lodata\Controller\Request;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Transaction\Batch;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\MultipartDocument;
use Illuminate\Support\Str;

/**
 * Multipart
 * @package Flat3\Lodata\Transaction\Batch
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_MultipartBatchFormat
 */
class Multipart extends Batch
{
    /**
     * @var MultipartDocument[] $documents
     * @internal
     */
    protected $documents = [];

    /**
     * @var string[] $boundaries
     * @internal
     */
    protected $boundaries = [];

    protected $references = [];

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $contentType = $transaction->getProvidedContentType();

        if (!$contentType->getParameter('boundary')) {
            throw new BadRequestException('missing_boundary', 'The provided content type had no boundary parameter');
        }

        array_unshift($this->boundaries, Str::uuid());
        $transaction->sendContentType(
            MediaType::factory()
                ->parse('multipart/mixed')
                ->setParameter('boundary', $this->boundaries[0])
        );

        $multipart = new MultipartDocument();
        $multipart->setHeaders($transaction->getRequestHeaders());
        $multipart->setBody($transaction->getBody());
        $this->documents[] = $multipart;

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function emit(Transaction $transaction): void
    {
        $document = array_pop($this->documents);

        foreach ($document->getDocuments() as $document) {
            $transaction->sendOutput(sprintf("\r\n--%s\r\n", $this->boundaries[0]));

            if ($document->getDocuments()) {
                array_unshift($this->boundaries, Str::uuid());
                $transaction->sendOutput(sprintf(
                    "content-type: multipart/mixed;boundary=%s\r\n\r\n",
                    $this->boundaries[0]
                ));
                $this->documents[] = $document;
                $this->emit($transaction);
                array_shift($this->boundaries);
            } else {
                $transaction->sendOutput("content-type: application/http\r\n\r\n");
                $requestTransaction = new Transaction();
                $requestTransaction->initialize(new Request($document->toRequest()));

                $requestTransactionPath = $requestTransaction->getRequest()->path();

                $lexer = new Lexer($requestTransactionPath);

                if ($lexer->maybeChar('$')) {
                    $contentId = $lexer->number();
                    $requestTransaction->getRequest()->setPath(parse_url($this->references[$contentId], PHP_URL_PATH));
                }

                $response = null;

                try {
                    $response = $requestTransaction->execute()->response($requestTransaction);
                } catch (ProtocolException $e) {
                    $response = $e->toResponse();
                }

                $transaction->sendOutput(sprintf(
                    "HTTP/%s %s %s\r\n",
                    $response->getProtocolVersion(),
                    $response->getStatusCode(),
                    $response->getStatusText()
                ));

                foreach ($response->headers->allPreserveCaseWithoutCookies() as $key => $values) {
                    if (Str::contains(strtolower($key), ['date', 'cache-control'])) {
                        continue;
                    }

                    foreach ($values as $value) {
                        $transaction->sendOutput($key.': '.$value."\r\n");
                    }
                }

                $transaction->sendOutput("\r\n");
                $response->sendContent();

                $contentId = $requestTransaction->getRequestHeader('content-id');
                if ($contentId) {
                    $this->references[$contentId] = $response->getSegment()->getResourceUrl($requestTransaction);
                }
            }
        }

        $transaction->sendOutput(sprintf("\r\n--%s--\r\n", $this->boundaries[0]));
    }
}