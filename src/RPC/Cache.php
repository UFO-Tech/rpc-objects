<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\RpcError\RpcInternalException;
use Ufo\RpcObject\DTO\ArrayConvertibleTrait;
use Ufo\RpcObject\DTO\IArrayConvertible;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Cache
{
    const ENV_PROD = 'prod';
    const ENV_DEV = 'dev';
    const ENV_TEST = 'test';
    const T_MINUTE = 60;
    const T_5_MINUTES = self::T_MINUTE * 5;
    const T_10_MINUTES = self::T_MINUTE * 10;
    const T_30_MINUTES = self::T_MINUTE * 30;
    const T_HOUR = self::T_MINUTE * 60;
    const T_2_HOURS = self::T_HOUR * 2;
    const T_5_HOURS = self::T_HOUR * 5;
    const T_10_HOURS = self::T_HOUR * 10;
    const T_DAY = self::T_HOUR * 24;
    const T_2_DAYS = self::T_DAY * 2;
    const T_3_DAYS = self::T_DAY * 3;
    const T_5_DAYS = self::T_DAY * 5;
    const T_10_DAYS = self::T_DAY * 10;
    const T_WEEK = self::T_DAY * 7;
    const T_2_WEEKS = self::T_WEEK * 2;
    const T_3_WEEKS = self::T_WEEK * 3;
    const T_MONTH = self::T_DAY * 30;
    const T_2_MONTHS = self::T_MONTH * 2;
    const T_3_MONTHS = self::T_MONTH * 3;
    const T_HALF_YEAR = self::T_DAY * 182;
    const T_YEAR = self::T_DAY * 365;

    public function __construct(
        public int $lifetimeSecond = self::T_MINUTE,
        public array $environments = [self::ENV_PROD],
    ) {}
}
