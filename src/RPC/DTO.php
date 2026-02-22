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
        array $context = []
    )
    {
        parent::__construct($dtoFQCN, context: [
            ...$context,
            ...[
                static::C_COLLECTION => $collection,
            ]
        ]);
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

    public function getRealFormat(string $paramName): ?string
    {
        return $this->realFormat[$paramName] ?? null;
    }

    public function isCollection(): bool
    {
        return $this->context[static::C_COLLECTION] ?? false;
    }
}
