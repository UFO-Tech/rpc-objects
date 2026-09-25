<?php

namespace Ufo\RpcObject\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ufo\RpcError\RpcAsyncRequestException;
use Ufo\RpcError\RpcBadParamException;
use Ufo\RpcError\RpcBadRequestException;
use Ufo\RpcError\RpcInternalException;
use Ufo\RpcError\RpcRuntimeException;
use Ufo\RpcError\RpcJsonParseException;
use Ufo\RpcObject\RpcRequest;
use Ufo\RpcObject\RpcResponse;
use Ufo\RpcObject\SpecialRpcParams;
use Ufo\RpcObject\Transformer\Transformer;

class RpcRequestTest extends TestCase
{
    /**
     * Перевіряє створення запиту, сортування звичайних параметрів і відокремлення спеціальних
     * RPC-параметрів зі збереженням версії API.
     */
    public function testConstructsSortsAndSeparatesSpecialParams(): void
    {
        $request = new RpcRequest('r1', 'user.find', ['z' => 2, 'a' => 1, '$rpc' => ['timeout' => 30, 'rayId' => 'root']], apiVersion: 'v2');
        self::assertSame('r1', $request->getId());
        self::assertSame('user.find', $request->getMethod());
        self::assertSame('2.0', $request->getVersion());
        self::assertSame('v2', $request->apiVersion);
        self::assertSame(['a' => 1, 'z' => 2], $request->getParams());
        self::assertTrue($request->hasParams());
        self::assertTrue($request->hasRpcParams());
        self::assertSame(30, $request->getRpcParams()->getTimeout());
        self::assertSame('root', $request->getSpecialParams()['rayId']);
        self::assertNull($request->getRawJson());
        self::assertFalse($request->hasError());
    }

    /**
     * Перевіряє читання запиту з JSON, перетворення на масив і оновлення сирого JSON зі спеціальними
     * параметрами.
     */
    public function testParsesAndSerializesRequest(): void
    {
        $data = ['id' => 7, 'method' => 'sum', 'params' => ['a' => 2], 'jsonrpc' => '2.0'];
        $request = RpcRequest::fromJson(json_encode($data), 'v3');
        self::assertFalse($request->hasError());
        self::assertSame('v3', $request->apiVersion);
        self::assertSame($data, json_decode($request->getRawJson(), true));
        $expected = $data;
        $expected['params']['$rpc'] = ['timeout' => 10, 'ignoreCache' => false, 'rayId' => 7];
        self::assertEquals($expected, $request->toArray());
        $request->refreshRawJson(Transformer::getDefault());
        self::assertEquals($expected, json_decode($request->getRawJson(), true));
    }

    /**
     * Перевіряє автоматичне створення ідентифікатора та стандартні значення, якщо запит не містить id і
     * params.
     */
    public function testMissingIdAndParamsUseDefaults(): void
    {
        $request = RpcRequest::fromArray(['method' => 'ping']);
        self::assertFalse($request->hasError());
        self::assertNotSame('', $request->getId());
        self::assertFalse($request->hasParams());
        self::assertSame($request->getId(), $request->getSpecialParams()['rayId']);
    }

    /**
     * Перевіряє, що некоректний JSON або скалярні дані замість запиту спричиняють виняток розбору JSON.
     */
    #[DataProvider('invalidJson')]
    public function testInvalidJsonThrowsParseException(string $json): void
    {
        $this->expectException(RpcJsonParseException::class);
        RpcRequest::fromJson($json);
    }

    public static function invalidJson(): iterable
    {
        foreach (['{', 'null', '42', '"hello"'] as $json) yield $json => [$json];
    }

    /**
     * Перевіряє, що некоректні поля запиту зберігаються як помилка валідації, класифікована як помилка
     * користувача.
     */
    #[DataProvider('invalidRequests')]
    public function testInvalidArrayStoresValidationError(array $data): void
    {
        $request = RpcRequest::fromArray($data);
        self::assertTrue($request->hasError());
        self::assertTrue($request->isUserError());
    }

    public static function invalidRequests(): iterable
    {
        yield 'missing method' => [['id' => 1]];
        yield 'blank method' => [['id' => 1, 'method' => '']];
        yield 'invalid version' => [['id' => 1, 'method' => 'ping', 'jsonrpc' => 'wrong']];
        yield 'empty params' => [['id' => 1, 'method' => 'ping', 'params' => []]];
    }

    /**
     * Перевіряє підстановку значень замість посилань на батьківський запит, облік решти залежностей і їх
     * очищення після останньої підстановки.
     */
    public function testDependencyReplacementTracksRemainingReferences(): void
    {
        $request = new RpcRequest(2, 'save', ['a' => '@FROM:parent(id)', 'b' => '@FROM:parent()', 'c' => ['literal']]);
        self::assertTrue($request->hasRequire());
        self::assertSame(['parent' => 2], $request->getRequireIds());
        self::assertSame('parent', $request->getCurrentRequireId());
        self::assertSame('id', $request->getRequire()['a']->getResponseFieldName());
        self::assertSame('parent', $request->getRequire()['a']->getResponseId());
        $request->replaceRequestParam('a', 42);
        self::assertSame(['parent' => 1], $request->getRequireIds());
        $request->replaceRequestParam('b', ['id' => 42]);
        self::assertFalse($request->hasRequire());
        self::assertFalse($request->checkRequireId('parent'));
        self::assertNull($request->getCurrentRequireId());
        self::assertSame(['a' => 42, 'b' => ['id' => 42], 'c' => ['literal']], $request->getParams());
    }

    /**
     * Перевіряє збереження помилки при спробі замінити параметр без залежностей або параметр, якого немає
     * серед залежностей.
     */
    public function testInvalidReplacementIsStoredAsError(): void
    {
        $request = new RpcRequest(1, 'ping');
        $request->replaceRequestParam('missing', 1);
        self::assertInstanceOf(RpcRuntimeException::class, $request->getError());
        $request = new RpcRequest(2, 'ping', ['a' => '@FROM:p()']);
        $request->replaceRequestParam('missing', 1);
        self::assertInstanceOf(RpcBadRequestException::class, $request->getError());
    }

    /**
     * Перевіряє позначення запиту як обробленого після встановлення відповіді, класифікацію помилок і
     * оновлення залежностей та спеціальних параметрів.
     */
    public function testResponseAndErrorLifecycle(): void
    {
        $request = new RpcRequest(1, 'ping');
        self::assertFalse($request->isProcessed());
        self::assertNull($request->getResponseObject());
        $response = new RpcResponse(1, 'pong');
        $request->setResponse($response);
        self::assertTrue($request->isProcessed());
        self::assertSame($response, $request->getResponseObject());
        $request->setError(new RpcBadParamException('bad input'));
        self::assertTrue($request->isUserError());
        self::assertFalse($request->isServerError());
        $request->setError(new RpcInternalException('internal'));
        self::assertTrue($request->isServerError());
        self::assertFalse($request->isProcedureError());
        $request->setRequireIds(['manual' => 1]);
        self::assertTrue($request->checkRequireId('manual'));
        $params = new SpecialRpcParams(timeout: 50);
        $request->setRpcParams($params);
        self::assertSame($params, $request->getRpcParams());
    }

    /**
     * Перевіряє, що синхронний запит не є асинхронним і отримання об’єкта callback спричиняє відповідний
     * виняток.
     */
    public function testSynchronousRequestHasNoCallback(): void
    {
        $request = new RpcRequest(1, 'ping');
        self::assertFalse($request->isAsync());
        $this->expectException(RpcAsyncRequestException::class);
        $request->getCallbackObject();
    }

    /**
     * Перевіряє, що отримання URL callback для синхронного запиту спричиняє виняток асинхронного запиту.
     */
    public function testSynchronousCallbackUrlThrows(): void
    {
        $this->expectException(RpcAsyncRequestException::class);
        (new RpcRequest(1, 'ping'))->getCallbackUrl();
    }
}
