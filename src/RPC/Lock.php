<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Ufo\RpcObject\RpcRequest;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Lock
{
    const int T_SECOND = 1;
    const int T_2_SECOND = self::T_SECOND * 2;
    const int T_5_SECOND = self::T_SECOND * 5;
    const int T_10_SECOND = self::T_SECOND * 10;
    const int T_20_SECOND = self::T_SECOND * 20;
    const int T_30_SECOND = self::T_SECOND * 30;

    public function __construct(
        public ?string $paramName = null,
        public ?int $ttl = null,
    ) {}

    /**
     * @param RpcRequest $request
     * @param LockFactory $lockFactory
     * @return SharedLockInterface|null
     * @throws LockConflictedException If the lock is acquired by someone else in blocking mode
     * @throws LockAcquiringException
     */
    public function acquire(RpcRequest $request, LockFactory $lockFactory): ?SharedLockInterface
    {
        $param = $request->getParams()[$this->paramName] ?? '';
        $id = md5($request->getMethod() . $param);
        $lock = $lockFactory->createLock($id, (float)$this->ttl);
        return $lock->acquire(true) ? $lock : null;
    }
}
