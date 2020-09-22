<?php

namespace Flat3\OData\Type;

use Flat3\OData\Type;

class Binary extends Type
{
    public const EDM_TYPE = 'Edm.Binary';

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return $this::URL_NULL;
        }

        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($this->value));
    }

    public function toJson(): ?string
    {
        return null === $this->value ? null : base64_encode($this->value);
    }

    public function toInternal($value): void
    {
        $result = base64_decode(str_replace(['-', '_'], ['+', '/'], $value));
        if (false === $result) {
            $result = null;
        }

        $this->value = $this->maybeNull(null === $value ? null : $result);
    }
}
