<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\RpcError\RpcInternalException;

use function is_null;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class DTO extends AttrDTO
{
    /**
     * @var ?array
     */
    protected ?array $dtoFormat = null;

    /**
     * @return array
     * @throws RpcInternalException
     */
    public function getFormat(): array
    {
        if (is_null($this->dtoFormat)) {
            throw new RpcInternalException('DTO ' . $this->dtoFQCN . ' is not parsed');
        }
        return $this->dtoFormat;
    }
}
