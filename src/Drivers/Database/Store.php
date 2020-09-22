<?php

namespace Flat3\OData\Drivers\Database;

use Illuminate\Support\Facades\DB;
use Flat3\OData\Entity;
use Flat3\OData\Exception\InternalErrorException;
use Flat3\OData\Option\Count;
use Flat3\OData\Option\Filter;
use Flat3\OData\Option\OrderBy;
use Flat3\OData\Option\Search;
use Flat3\OData\Option\Skip;
use Flat3\OData\Option\Top;
use Flat3\OData\Primitive;
use Flat3\OData\Transaction;
use PDO;

class Store extends \Flat3\OData\Store
{
    public const ENTITY_SET = EntitySet::class;

    protected $supportedQueryOptions = [
        Count::class,
        Filter::class,
        OrderBy::class,
        Search::class,
        Skip::class,
        Top::class,
    ];

    /** @var string $table */
    private $table;

    public function getTable(): string
    {
        return $this->table ?: $this->identifier;
    }

    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function getDbHandle(): PDO
    {
        return DB::connection()->getPdo();
    }

    public function getEntity(Transaction $transaction, Primitive $key): ?Entity
    {
        return $this->getEntitySet($transaction, $key)->getCurrentResultAsEntity();
    }

    public function convertResultToEntity($row = null): Entity
    {
        $entity = new Entity($this);

        $key = $this->getTypeKey()->getIdentifier()->get();
        $entity->setEntityIdValue($row[$key]);

        foreach ($row as $id => $value) {
            $property = $this->getTypeProperty($id);

            if (!$property) {
                throw new InternalErrorException(
                    sprintf(
                        'The service attempted to access an undefined property for %s',
                        $id
                    )
                );
            }

            $entity->addPrimitive($value, $property);
        }

        return $entity;
    }
}
