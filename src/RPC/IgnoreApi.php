<?php

namespace Ufo\RpcObject\RPC;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class IgnoreApi
{
}