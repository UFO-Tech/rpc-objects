<?php

namespace Ufo\RpcObject\Tests\Rules;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Ufo\RpcError\RpcBadParamException;
use Ufo\RpcError\RpcBadRequestException;
use Ufo\RpcObject\Rules\Validator\Validator;

class ValidatorTest extends TestCase
{
    /**
     * Перевіряє, що переданий RPC-виняток кидається зі своїм кодом.
     */
    public function testThrowUsesPassedRpcException(): void
    {
        try {
            Validator::validate('', [new NotBlank()])->throw(RpcBadRequestException::class);
            self::fail('Expected RpcBadRequestException.');
        } catch (RpcBadRequestException $e) {
            self::assertSame(-32600, $e->getCode());
            self::assertSame(': This value should not be blank.', $e->getMessage());
        }
    }

    /**
     * Перевіряє, що клас не з ієрархії RPC-винятків замінюється на RpcBadParamException.
     */
    public function testThrowFallsBackForNonRpcException(): void
    {
        $this->expectException(RpcBadParamException::class);
        $this->expectExceptionCode(-32602);

        Validator::validate('', [new NotBlank()])->throw(RuntimeException::class);
    }

    /**
     * Перевіряє, що без аргументу кидається RpcBadParamException.
     */
    public function testThrowDefaultsToRpcBadParamException(): void
    {
        $this->expectException(RpcBadParamException::class);

        Validator::validate('', [new NotBlank()])->throw();
    }
}
