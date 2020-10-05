<?php

namespace Flat3\OData\Helper;

use ArrayAccess;
use Countable;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Iterator;

class ObjectArray implements Countable, Iterator, ArrayAccess
{
    private $array = [];

    public static function merge(ObjectArray $map_a, ObjectArray $map_b): ObjectArray
    {
        $map = new self();

        foreach ($map_a as $a) {
            $map->replace($a);
        }

        foreach ($map_b as $b) {
            $map->replace($b);
        }

        return $map;
    }

    public function replace($key, $value = null): void
    {
        if (!$key) {
            $key = $value;
        }

        if (!$value) {
            $value = $key;
        }

        $this->array[(string) $key] = $value;
    }

    public function add($key, $value = null): void
    {
        if ($this->exists($key)) {
            throw new InternalServerErrorException(
                'cannot_add_existing_key',
                'Attempted to add an item that already exists'
            );
        }

        $this->replace($key, $value);
    }

    public function count()
    {
        return count($this->array);
    }

    public function exists($key): bool
    {
        return array_key_exists((string) $key, $this->array);
    }

    public function current()
    {
        return current($this->array);
    }

    public function next()
    {
        return next($this->array);
    }

    public function key()
    {
        return key($this->array);
    }

    public function valid()
    {
        $key = key($this->array);

        return !!$key;
    }

    public function rewind()
    {
        reset($this->array);
    }

    public function offsetExists($offset)
    {
        return !!$this->get($offset);
    }

    public function get($key)
    {
        return $this->array[(string) $key] ?? null;
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->replace($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->drop($offset);
    }

    public function drop($key): void
    {
        unset($this->array[(string) $key]);
    }

    public function hasEntries(): bool
    {
        return !!$this->array;
    }

    public function sliceByClass($class): self
    {
        $result = new self();

        foreach ($this->array as $key => $value) {
            if ($value instanceof $class) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
