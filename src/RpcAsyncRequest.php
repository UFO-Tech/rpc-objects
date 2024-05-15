<?php

namespace Ufo\RpcObject;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use TypeError;
use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcError\IProcedureExceptionInterface;
use Ufo\RpcError\IServerExceptionInterface;
use Ufo\RpcError\IUserInputExceptionInterface;
use Ufo\RpcError\RpcAsyncRequestException;
use Ufo\RpcError\RpcBadRequestException;
use Ufo\RpcError\RpcJsonParseException;
use Ufo\RpcError\RpcRuntimeException;
use Ufo\RpcObject\Rules\ParamsSplitter;
use Ufo\RpcObject\Rules\RequestRules;
use Ufo\RpcObject\Rules\Validator\Validator;
use Ufo\RpcObject\Transformer\Transformer;

class RpcAsyncRequest
{

    public function __construct(
        protected RpcRequest $rpcRequest,
        public readonly string $token = ''
    ) {}

    /**
     * @return RpcRequest
     */
    public function getRpcRequest(): RpcRequest
    {
        return $this->rpcRequest;
    }

}
