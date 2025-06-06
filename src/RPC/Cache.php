<?php

namespace Ufo\RpcObject\RPC;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Cache
{
    const string ENV_PROD = 'prod';
    const string ENV_DEV = 'dev';
    const string ENV_TEST = 'test';
    const int T_MINUTE = 60;
    const int T_5_MINUTES = self::T_MINUTE * 5;
    const int T_10_MINUTES = self::T_MINUTE * 10;
    const int T_30_MINUTES = self::T_MINUTE * 30;
    const int T_HOUR = self::T_MINUTE * 60;
    const int T_2_HOURS = self::T_HOUR * 2;
    const int T_5_HOURS = self::T_HOUR * 5;
    const int T_10_HOURS = self::T_HOUR * 10;
    const int T_DAY = self::T_HOUR * 24;
    const int T_2_DAYS = self::T_DAY * 2;
    const int T_3_DAYS = self::T_DAY * 3;
    const int T_5_DAYS = self::T_DAY * 5;
    const int T_10_DAYS = self::T_DAY * 10;
    const int T_WEEK = self::T_DAY * 7;
    const int T_2_WEEKS = self::T_WEEK * 2;
    const int T_3_WEEKS = self::T_WEEK * 3;
    const int T_MONTH = self::T_DAY * 30;
    const int T_2_MONTHS = self::T_MONTH * 2;
    const int T_3_MONTHS = self::T_MONTH * 3;
    const int T_HALF_YEAR = self::T_DAY * 182;
    const int T_YEAR = self::T_DAY * 365;

    public function __construct(
        public int $lifetimeSecond = self::T_MINUTE,
        public array $environments = [self::ENV_PROD],
    ) {}
}
