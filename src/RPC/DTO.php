<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\RpcError\RpcInternalException;

use function implode;
use function is_null;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class DTO
{
    /**
     * @var ?array
     */
    protected ?array $dtoFormat = null;

    public function __construct(
        public readonly string $dtoFQCN,
        public readonly bool $collection = false,
        public readonly array $renameKeys = [],
        public readonly ?string $transformerFQCN = null
    ) {}

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
