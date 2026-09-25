<?php

namespace Ufo\RpcObject\Tests\RPC;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Lock\SharedLockInterface;
use Ufo\RpcObject\RPC\Lock;
use Ufo\RpcObject\RpcRequest;

class LockTest extends TestCase
{
    /**
     * Перевіряє формування ключа блокування з методу й вибраного параметра, передавання TTL і повернення
     * успішно захопленого блокування.
     */
    public function testAcquireUsesMethodAndSelectedParameterAsResource(): void
    {
        $shared = $this->createMock(SharedLockInterface::class);
        $shared->expects(self::once())->method('acquire')->with(true)->willReturn(true);
        $factory = $this->createMock(LockFactory::class);
        $factory->expects(self::once())->method('createLock')->with(md5('save42'), 10.0)->willReturn($shared);
        self::assertSame($shared, (new Lock('id', 10))->acquire(new RpcRequest(1, 'save', ['id' => 42]), $factory));
    }

    /**
     * Перевіряє повернення null при невдалому захопленні блокування та формування ключа лише з методу за
     * відсутності параметра.
     */
    public function testFailedAcquireReturnsNullAndMissingParameterUsesMethod(): void
    {
        $shared = $this->createMock(SharedLockInterface::class);
        $shared->expects(self::once())->method('acquire')->with(true)->willReturn(false);
        $factory = $this->createMock(LockFactory::class);
        $factory->expects(self::once())->method('createLock')->with(md5('save'), 0.0)->willReturn($shared);
        self::assertNull((new Lock())->acquire(new RpcRequest(1, 'save'), $factory));
    }

    /**
     * Перевіряє захоплення та звільнення справжнього блокування зі сховищем у пам’яті.
     */
    public function testAcquiresAndReleasesRealInMemoryLock(): void
    {
        $factory = new LockFactory(new InMemoryStore());
        $lock = (new Lock('id', 10))->acquire(new RpcRequest(1, 'save', ['id' => 'abc']), $factory);
        self::assertNotNull($lock);
        try { self::assertTrue($lock->isAcquired()); }
        finally { $lock->release(); }
        self::assertFalse($lock->isAcquired());
    }
}
