<?php

namespace Ufo\RpcObject\RPC;


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

}