<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\RpcError\RpcInternalException;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class ResultAsDTO extends DTO
{
    /**
     * @return array
     * @throws RpcInternalException
     */
    public function getResponseFormat(): array
    {
        return $this->getFormat();
    }
}
