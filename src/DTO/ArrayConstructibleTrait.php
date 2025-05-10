<?php

namespace Ufo\RpcObject\DTO;

use Ufo\RpcError\RpcBadParamException;

trait ArrayConstructibleTrait
{
    /**
     * @throws RpcBadParamException
     * @throws NotSupportDTOException
     */
    public static function fromArray(array $data, array $renameKey = []): static
    {
        /**
         * @var static $self
         */
        $self = DTOTransformer::fromArray(static::class, $data, $renameKey);
        return $self;
    }
}