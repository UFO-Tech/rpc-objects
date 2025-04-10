<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\RpcError\RpcInternalException;

use function implode;
use function is_null;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY)]
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
