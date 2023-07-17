<?php

namespace Ufo\RpcObject\Transformer;

use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcObject\RpcError;
use Ufo\RpcObject\RpcResponse;

class ResponseCreator
{
    /**
     * @param string $json
     * @return RpcResponse
     */
    public static function fromJson(string $json, bool $catchError = true): RpcResponse
    {
        $serializer = Transformer::getDefault();
        $responseArray = $serializer->decode($json, 'json');

        try {
            if (isset($responseArray['error'])) {
                throw AbstractRpcErrorException::fromCode($responseArray['error']['code'], $responseArray['error']['message']);
            }
            $response = $serializer->denormalize($responseArray, RpcResponse::class);
        } catch (\Throwable $e) {
            if ($e instanceof AbstractRpcErrorException) {
                $error = new RpcError($e->getCode(), $e->getMessage(), $e);
            } else {
                $error = new RpcError(
                    AbstractRpcErrorException::DEFAULT_CODE,
                    'Uncatchable async error',
                    $e
                );
            }
            if (!$catchError) {
                throw AbstractRpcErrorException::fromCode($error->getCode(), $error->getMessage());
            }
            $response = new RpcResponse(
                id: $responseArray['id'],
                error: $error,
                version: $responseArray['jsonrpc'],
            );
        }
        return $response;
    }
}
