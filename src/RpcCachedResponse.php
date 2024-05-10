<?php

namespace Ufo\RpcObject;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\SerializerInterface;
use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcError\WrongWayException;
use Ufo\RpcObject\RPC\Cache;
use Ufo\RpcObject\Transformer\Transformer;

readonly class RpcCachedResponse
{
    public function __construct(public mixed $result = []) {}

}
