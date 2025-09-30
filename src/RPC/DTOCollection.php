<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\RpcError\RpcInternalException;

use function is_null;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class DTOCollection extends DTO
{
    /**
     * @var ?array
     */
    protected ?array $dtoFormat = null;
    protected array $realFormat = [];

    public function __construct(
        string $dtoFQCN,
        array $context = []
    )
    {
        parent::__construct($dtoFQCN, true, context: $context);
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
