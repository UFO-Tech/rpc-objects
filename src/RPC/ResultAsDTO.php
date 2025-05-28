<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\RpcError\RpcInternalException;

#[Attribute(Attribute::TARGET_METHOD)]
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

    public function getRealFormat(string $paramName): ?string
    {
        return $this->realFormat[$paramName] ?? null;
    }

}
