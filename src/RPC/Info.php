<?php

namespace Ufo\RpcObject\RPC;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Info
{
    const string DEFAULT_CONCAT = '.';

    public function __construct(
        public ?string $alias = null,
        public string $concat = self::DEFAULT_CONCAT
    ) {}
}