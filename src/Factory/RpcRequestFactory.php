<?php

namespace Ufo\RpcObject\Factory;

use JsonException;
use ReflectionClass;
use Throwable;
use TypeError;
use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcError\RpcBadRequestException;
use Ufo\RpcError\RpcJsonParseException;
use Ufo\RpcObject\RPC\Info;
use Ufo\RpcObject\RpcNotificationRequest;
use Ufo\RpcObject\RpcRequest;
use Ufo\RpcObject\Rules\ParamsSplitter;
use Ufo\RpcObject\Rules\RequestRules;
use Ufo\RpcObject\Rules\Validator\Validator;

use function array_key_exists;
use function class_exists;
use function is_a;
use function is_numeric;
use function json_encode;

use const JSON_THROW_ON_ERROR;

class RpcRequestFactory
{
    public static function fromJson(
        string $json,
        string $apiVersion = Info::DEFAULT_VERSION,
        ?string $classFQCN = null
    ): RpcRequest
    {
        try {
            return static::fromArray(json_decode($json, true), $apiVersion, $classFQCN);
        } catch (TypeError $e) {
            throw new RpcJsonParseException('Invalid json data', previous: $e);
        }
    }


    public static function fromArray(
        array $data,
        string $apiVersion = Info::DEFAULT_VERSION,
        ?string $classFQCN = null
    ): RpcRequest
    {
        if (!is_a($classFQCN,RpcRequest::class, true)) {
            $classFQCN = RpcRequest::class;
        }
        $requestClass = match (true) {
            /*
             * Відсутність ключа id визначає нотифікацію. Явний клас нащадка зберігає
             * сумісність успадкованого RpcRequest::fromArray() з пізнім статичним зв’язуванням.
             */
            !array_key_exists('id', $data) => RpcNotificationRequest::class,
            default => $classFQCN
        };
        try {
            $rawJson = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $rawJson = null;
        }

        $data['id'] ??= '';
        $validator = Validator::validate($data, RequestRules::assertAll($requestClass));
        $params = $data['params'] ?? [];

        $splitter = null;

        try {
            $splitter = ParamsSplitter::split($params);
            $arguments = [
                'id' => $data['id'],
                'method' => $data['method'] ?? '',
                'params' => $splitter->getParams(),
                'version' => $data['jsonrpc'] ?? $requestClass::DEFAULT_VERSION,
                'rawJson' => $rawJson,
                'apiVersion' => $apiVersion,
            ];
            if ($requestClass === RpcNotificationRequest::class) unset($arguments['id']);

            $object = new $requestClass(...$arguments);
        } catch (Throwable $e) {
            // Некоректний запит також повертається як об’єкт зі збереженою помилкою.
            $reflection = new ReflectionClass($requestClass);
            $object = $reflection->newInstanceWithoutConstructor();
            $id = $data['id'] ?? null;
            $fields = [
                'id' => is_string($id) || is_int($id) ? $id : uniqid(),
                'method' => is_string($data['method'] ?? null) ? $data['method'] : 'noname',
                'version' => is_string($data['jsonrpc'] ?? null) ? $data['jsonrpc'] : RpcRequest::DEFAULT_VERSION,
                'params' => is_array($data['params'] ?? null) ? $data['params'] : [],
                'rawJson' => $rawJson,
                'apiVersion' => $apiVersion,
            ];
            foreach ($fields as $name => $value) {
                $reflection->getProperty($name)->setValue($object, $value);
            }
            $object->setError($e);
        }

        try {
            $validator->throw(RpcBadRequestException::class);
        } catch (AbstractRpcErrorException $e) {
            $object->setError($e);
        }

        if (!$object->hasError() && $splitter?->getSpecialParams()) {
            $object->setRpcParams($splitter->getSpecialParams());
        }
        return $object;
    }
}
