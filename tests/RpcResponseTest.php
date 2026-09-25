<?php

namespace Ufo\RpcObject\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ufo\RpcError\RpcBadRequestException;
use Ufo\RpcObject\RPC\Cache;
use Ufo\RpcObject\RpcError;
use Ufo\RpcObject\RpcResponse;
use Ufo\RpcObject\RpcCachedResponse;
use Ufo\RpcObject\RpcRequest;
use Ufo\RpcObject\Transformer\RpcResponseContextBuilder;
use Ufo\RpcObject\Tests\Fixtures\Person;

class RpcResponseTest extends TestCase
{
    /**
     * Перевіряє збереження скалярних, порожніх і масивних результатів, стандартні поля відповіді та
     * відсутність винятку без помилки.
     */
    #[DataProvider('results')]
    public function testScalarAndArrayResultsArePreserved(mixed $result): void
    {
        $response = new RpcResponse(9, $result);
        self::assertSame(9, $response->getId());
        self::assertSame('2.0', $response->getVersion());
        self::assertSame($result, $response->getResult());
        self::assertSame($result, $response->getResult(true));
        self::assertNull($response->getError());
        self::assertSame('result', $response->getResponseSignature());
        $response->throwError();
    }

    public static function results(): iterable
    {
        foreach ([null, false, true, 0, 1.5, '', 'pong', [], ['a' => 1, 'b' => [2]]] as $index => $result) yield (string)$index => [$result];
    }

    /**
     * Перевіряє нормалізацію об’єктів і об’єктів усередині масиву з урахуванням груп серіалізації та
     * доступ до початкового результату.
     */
    public function testObjectResultsRespectGroups(): void
    {
        $person = new Person('Ada', 37);
        $context = (new RpcResponseContextBuilder())->withGroup('detail');
        $response = new RpcResponse('r', $person, contextBuilder: $context);
        self::assertSame($person, $response->getResult(true));
        self::assertSame(['name' => 'Ada', 'age' => 37], $response->getResult());
        $response = new RpcResponse('r', ['person' => $person, 'scalar' => 3], contextBuilder: $context);
        self::assertSame(['person' => ['name' => 'Ada', 'age' => 37], 'scalar' => 3], $response->getResult());
    }

    /**
     * Перевіряє, що серіалізована відповідь містить лише поля RPC, а пов’язаний запит і налаштування кешу
     * доступні окремо.
     */
    public function testResponseEnvelopeExcludesInternalState(): void
    {
        $request = new RpcRequest(1, 'ping');
        $cache = new Cache(60);
        $response = new RpcResponse(1, ['ok' => true], requestObject: $request, cache: $cache);
        self::assertSame($request, $response->getRequestObject());
        self::assertSame($cache, $response->getCacheInfo());
        self::assertEquals(['id' => 1, 'result' => ['ok' => true], 'jsonrpc' => '2.0'], $response->toArray((new RpcResponseContextBuilder())->withResponseSignature($response)));
    }

    /**
     * Перевіряє серіалізацію коду, повідомлення й даних помилки без включення поля результату.
     */
    public function testErrorEnvelopeContainsNoResult(): void
    {
        $error = new RpcError(-32600, 'bad request', ['field' => 'id']);
        $response = new RpcResponse('r', 'hidden', $error);
        self::assertSame('error', $response->getResponseSignature());
        self::assertSame($error, $response->getError());
        self::assertEquals(['id' => 'r', 'jsonrpc' => '2.0', 'error' => ['code' => -32600, 'message' => 'bad request', 'data' => ['field' => 'id']]], $response->toArray((new RpcResponseContextBuilder())->withResponseSignature($response)));
    }

    /**
     * Перевіряє збереження початкового винятку в RpcError і повторне створення винятку відповідного типу
     * за його кодом.
     */
    public function testThrowableIsRetainedAndRethrown(): void
    {
        $exception = new RpcBadRequestException('bad', -32600);
        $error = RpcError::fromThrowable($exception);
        self::assertSame($exception, $error->getData());
        self::assertSame($exception->getCode(), $error->getCode());
        self::assertSame('bad', $error->getMessage());
        $this->expectException(RpcBadRequestException::class);
        (new RpcResponse(1, error: $error))->throwError();
    }

    /**
     * Перевіряє стандартний порожній результат кешованої відповіді та збереження переданого об’єкта без
     * перетворення.
     */
    public function testCachedResponsePreservesRawResult(): void
    {
        self::assertSame([], (new RpcCachedResponse())->result);
        $value = new Person('Ada');
        self::assertSame($value, (new RpcCachedResponse($value))->result);
    }

    /**
     * Перевіряє створення відповідного RPC-винятку за кодом помилки, дані якої представлені масивом.
     */
    public function testArrayErrorIsRecreatedByCode(): void
    {
        $this->expectException(RpcBadRequestException::class);
        (new RpcResponse(1, error: new RpcError(-32600, 'bad', [])))->throwError();
    }
}
