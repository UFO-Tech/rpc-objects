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
    protected array $realFormat = [];

    public function __construct(
        string $dtoFQCN,
        bool $collection = false,
        array $renameKeys = [],
        ?string $transformerFQCN = null
    )
    {
        parent::__construct($dtoFQCN, $collection, $renameKeys, $transformerFQCN);
    }

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
