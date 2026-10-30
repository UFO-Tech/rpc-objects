<?php

namespace Ufo\RpcObject\Transformer;

use Ufo\DTO\Contract\ArrayConstructibleInterface;
use Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\RpcObject\RPC\Cache;
use Ufo\RpcObject\RPC\CacheRelation;
use Ufo\RpcObject\RPC\DTO;
use Ufo\RpcObject\RPC\IgnoreApi;
use Ufo\RpcObject\RPC\Lock;

use function in_array;

class AttributeTransformer implements StaticDTOFromArrayTransformerInterface
{
    public const array ATTRIBUTES = [
        Cache::class,
        Lock::class,
        IgnoreApi::class,
        CacheRelation::class,
        DTO::class,
    ];

    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object
    {
        foreach (self::ATTRIBUTES as $attributeFQCN) {
            try {
                return DTOTransformer::fromArray($attributeFQCN, $data, $renameKey, $namespaces);
            } catch (\Throwable) {}
        }
        throw new NotSupportDTOException('No support attribute');
    }

    public static function isSupportClass(string $classFQCN): bool
    {

        return in_array($classFQCN, self::ATTRIBUTES) || $classFQCN === ArrayConstructibleInterface::class;
    }

}