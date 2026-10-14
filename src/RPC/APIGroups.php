<?php

namespace Ufo\RpcObject\RPC;

use Attribute;

use function array_unique;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
readonly class APIGroups
{
    public const string DEFAULT_GROUP = 'general';

    public array $groups;
    public function __construct(array $groups = [], bool $withDefault = true)
    {
        if ($withDefault) {
            $groups[] = self::DEFAULT_GROUP;
        }
        $this->groups = array_unique($groups);
    }

}