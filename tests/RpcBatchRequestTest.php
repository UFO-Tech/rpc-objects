<?php

namespace Ufo\RpcObject\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ufo\RpcError\RpcBadRequestException;
use Ufo\RpcError\RpcJsonParseException;
use Ufo\RpcObject\RpcBatchRequest;
use Ufo\RpcObject\RpcRequest;
use Ufo\RpcObject\RpcResponse;

class RpcBatchRequestTest extends TestCase
{
    /**
     * Перевіряє розбір пакетного запиту, додавання незалежних запитів до черги виконання та зміну цієї
     * черги через посилання.
     */
    public function testParsesAndQueuesIndependentRequests(): void
    {
        $batch = RpcBatchRequest::fromJson('[{"id":"a","method":"ping"},{"id":"b","method":"ping"}]');
        self::assertSame(['a', 'b'], array_keys($batch->getCollection()));
        self::assertSame($batch->getCollection(), $batch->getReadyToHandle());
        self::assertSame([], $batch->getUnprocessedRequests());
        self::assertSame([], $batch->getResults());
        $ready = &$batch->getReadyToHandle();
        unset($ready['a']);
        self::assertSame(['b'], array_keys($batch->getReadyToHandle()));
    }

    /**
     * Перевіряє відхилення некоректного JSON пакетного запиту та елементів, які не є масивами запитів.
     */
    #[DataProvider('badJson')]
    public function testInvalidBatchJson(string $json): void
    {
        $this->expectException(RpcJsonParseException::class);
        RpcBatchRequest::fromJson($json);
    }

    public static function badJson(): iterable
    {
        yield ['{']; yield ['null']; yield ['[1]'];
    }

    /**
     * Перевіряє очікування кількох батьківських відповідей, підстановку окремого поля й цілого результату
     * та переведення дочірнього запиту до готових.
     */
    public function testResolvesMultipleParentsAndWholeResults(): void
    {
        $batch = new RpcBatchRequest();
        $first = new RpcRequest('a', 'first');
        $second = new RpcRequest('b', 'second');
        $child = new RpcRequest('c', 'child', ['id' => '@FROM:a(id)', 'all' => '@FROM:b()']);
        self::assertSame($batch, $batch->addRequestObject($first));
        $batch->addRequestObject($second)->addRequestObject($child);
        self::assertArrayNotHasKey('c', $batch->getReadyToHandle());
        $response = new RpcResponse('a', ['id' => 42]);
        $batch->addResponse($response, ['id' => 'a', 'result' => ['id' => 42]]);
        self::assertSame($response, $first->getResponseObject());
        self::assertSame(42, $child->getParams()['id']);
        self::assertTrue($child->hasRequire());
        $batch->addResponse(new RpcResponse('b', ['value' => 7]), ['id' => 'b', 'result' => ['value' => 7]]);
        self::assertFalse($child->hasRequire());
        self::assertSame(['value' => 7], $child->getParams()['all']);
        self::assertSame($child, $batch->getReadyToHandle()['c']);
        self::assertSame([], $batch->getUnprocessedRequests());
        self::assertSame(['a', 'b'], array_keys($batch->getResults()));
        self::assertSame(array_values($batch->getResults()), $batch->getResults(false));
    }

    /**
     * Перевіряє збереження помилки дочірнього запиту, якщо батьківська відповідь містить помилку або не
     * містить потрібного поля.
     */
    #[DataProvider('failedParentResults')]
    public function testUnresolvableParentResultStoresError(array $result, string $message): void
    {
        $batch = new RpcBatchRequest();
        $child = new RpcRequest('child', 'save', ['id' => '@FROM:parent(id)']);
        $batch->addRequestObject(new RpcRequest('parent', 'find'))->addRequestObject($child);
        $batch->addResponse(new RpcResponse('parent'), $result);
        self::assertInstanceOf(RpcBadRequestException::class, $child->getError());
        self::assertStringContainsString($message, $child->getError()->getMessage());
        $error = $child->getError();
        self::assertSame($child, $batch->provideUnprocessedRequests()['child']);
        self::assertSame($error, $child->getError());
    }

    public static function failedParentResults(): iterable
    {
        yield 'parent error' => [['error' => ['code' => -32600]], 'returned the error'];
        yield 'missing field' => [['result' => ['other' => 1]], 'does not have a "id" field'];
    }

    /**
     * Перевіряє встановлення помилки з ідентифікатором відсутнього батьківського запиту для нерозв’язаної
     * залежності.
     */
    public function testMissingParentBecomesError(): void
    {
        $batch = new RpcBatchRequest();
        $child = new RpcRequest('child', 'save', ['id' => '@FROM:missing(id)']);
        $batch->addRequestObject($child);
        $batch->provideUnprocessedRequests();
        self::assertStringContainsString('"missing" is not found', $child->getError()->getMessage());
    }

    /**
     * Перевіряє, що порожній пакет не має необроблених запитів.
     */
    public function testEmptyBatchHasNoPendingRequests(): void
    {
        self::assertSame([], RpcBatchRequest::fromJson('[]')->provideUnprocessedRequests());
    }
}
