<?php

namespace Ufo\RpcObject;

readonly class RpcCachedResponse
{
    public function __construct(public mixed $result = []) {}

}
