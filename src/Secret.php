<?php

namespace Ufo\RpcObject;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Secret
{
    const VALUE = '{secret}';
    const QUERY_PATTERN = '/([\w\d_]*(?:secret|access|token|key)[_\w]*)=((?:\w|\d)+(?=&?))/';

    public function __construct(public string $pattern = self::QUERY_PATTERN) {}

    public function replace(string $value): string
    {
        return preg_replace_callback($this->pattern, function ($matches) {
            return $matches[1].'={'.$matches[1].'}';
        }, $value);
    }

}