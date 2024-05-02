<?php

namespace Ufo\RpcObject\RPC;


use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Info
{
    const DEFAULT_CONCAT = '.';

    public function __construct(
        protected ?string $alias = null,
        protected string $concat = self::DEFAULT_CONCAT
    ) {}

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getConcat(): string
    {
        return $this->concat;
    }

}