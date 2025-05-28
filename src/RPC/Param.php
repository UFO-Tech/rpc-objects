<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\DTO\Helpers\TypeHintResolver;

use function array_filter;
use function array_values;
use function count;

use const ARRAY_FILTER_USE_KEY;

#[Attribute(Attribute::TARGET_PARAMETER|Attribute::TARGET_PROPERTY)]
final readonly class Param
{
    const int NULL   = 1;
    const int STRING = 2;
    const int FLOAT  = 4;
    const int INT    = 8;

    const int NUMBER = self::INT | self::FLOAT;
    const int MIXED = self::STRING | self::NUMBER | self::NULL;
    const int ANY = self::MIXED;
    const int NULLABLE_STRING = self::STRING | self::NULL;
    const int NULLABLE_NUMBER = self::NUMBER | self::NULL;

    const array TYPE_HINTS = [
        self::STRING => TypeHintResolver::STRING->value,
        self::FLOAT  => TypeHintResolver::FLOAT->value,
        self::INT    => TypeHintResolver::INT->value,
        self::NULL   => TypeHintResolver::NULL->value,
    ];

    private const int ALLOWED_BITS = self::STRING | self::FLOAT | self::INT | self::NULL;

    public function __construct(
        protected int $type,
        public null|string|int $default = null,
        public ?string $convertorFQCN = null,
    ) {
        if (($type & ~self::ALLOWED_BITS) !== 0) {
            throw new \InvalidArgumentException('Param for converter not have allowed type');
        }
    }

    public function getType(): string|array
    {
        $scalarTypes = array_values(
            array_filter(
                self::TYPE_HINTS,
                function (int $bit): string
                {
                    if (($this->type & $bit) !== 0) {
                        return TypeHintResolver::phpToJsonSchema(self::TYPE_HINTS[$bit]);
                    }
                    return '';
                },
                ARRAY_FILTER_USE_KEY
            )
        );
        return count($scalarTypes) > 1 ? $scalarTypes : $scalarTypes[0];
    }
}