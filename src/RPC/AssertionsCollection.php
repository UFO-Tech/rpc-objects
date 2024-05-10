<?php

namespace Ufo\RpcObject\RPC;

use function array_map;

class AssertionsCollection
{
    /**
     * @var Assertions[]
     */
    protected array $collection = [];

    public function addAssertions(string|int $key, Assertions $assertions): static
    {
        $this->collection[$key] = $assertions;

        return $this;
    }

    public function getAssertionsCollection(): array
    {
        return $this->collection;
    }

    public function toArray(): array
    {
        return array_map(function (Assertions $a) {
            return $a->toArray();
        }, $this->collection);
    }
}