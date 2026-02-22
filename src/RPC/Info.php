<?php

namespace Ufo\RpcObject\RPC;

use Attribute;

use function array_map;
use function array_unique;
use function trim;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Info
{
    const string DEFAULT_CONCAT = '.';

    private const int DEFAULT_VERSION_INT = 1;
    const string DEFAULT_VERSION = 'v1';

    public string $version;

    /**
     * @var string[]
     */
    public array $removedMethods;

    /**
     * @param string $alias
     * @param string $concat
     * @param ?int $version
     * @param array $removedMethods Methods (method names) that are excluded compared to the previous version.
     */
    public function __construct(
        public string $alias,
        public string $concat = self::DEFAULT_CONCAT,
        ?int $version = self::DEFAULT_VERSION_INT,
        array $removedMethods = [],
    )
    {
        $this->version = 'v' . $version;
        $this->removedMethods = array_values(
            array_unique(
                array_map(
                    static function (string $m) use ($alias, $concat): string
                    {
                        return $alias . $concat . trim($m);
                    },
                    $removedMethods
                )
            )
        );
    }
}