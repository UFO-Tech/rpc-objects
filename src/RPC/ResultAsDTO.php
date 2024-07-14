<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\RpcError\RpcInternalException;

use function implode;
use function is_null;

#[Attribute(Attribute::TARGET_METHOD)]
class ResultAsDTO
{
    /**
     * @var ?array
     */
    protected ?array $dtoFormat = null;

    public function __construct(
        public readonly string $dtoFQCN,
        public readonly bool $collection = false
    ) {}

    /**
     * @return array
     * @throws RpcInternalException
     */
    public function getResponseFormat(): array
    {
        if (is_null($this->dtoFormat)) {
            throw new RpcInternalException('Result DTO ' . $this->dtoFQCN.' is not parsed');
        }
        return $this->dtoFormat;
    }
}
