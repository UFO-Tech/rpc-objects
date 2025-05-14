<?php

namespace Ufo\RpcObject\RPC;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class CacheRelation
{
    /**
     * @param string|null $serviceFQCN If you need to disable the cache of methods from another service class
     * @param string[] $methods List of methods whose cache needs to be disabled
     * @param bool $warmUp Disable the cache only if the current method is successful
     */
    public function __construct(
        public ?string $serviceFQCN = null,
        public array $methods = [],
        public bool $warmUp = false
    ) {
    }

    public function cloneToClass(string $serviceFQCN): static
    {
        return new self($serviceFQCN, $this->methods, $this->warmUp);
    }
}
