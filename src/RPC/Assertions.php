<?php

namespace Ufo\RpcObject\RPC;


use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class Assertions
{
    public function __construct(
        public array $assertions = []
    ) {}
}