<?php

namespace Ufo\RpcObject\RPC;


use Attribute;

#[Attribute]
class Info
{
    public function __construct(
        protected ?string $alias = null
    ) {}

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

}