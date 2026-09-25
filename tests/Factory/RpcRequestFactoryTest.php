<?php

namespace Ufo\RpcObject\Tests\Factory;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ufo\RpcError\RpcJsonParseException;
use Ufo\RpcObject\Factory\RpcRequestFactory;
use Ufo\RpcObject\RpcBatchRequest;
use Ufo\RpcObject\RpcNotificationRequest;
use Ufo\RpcObject\RpcRequest;
use Ufo\RpcObject\Transformer\Transformer;

class CustomRequest extends RpcRequest {}

class RpcRequestFactoryTest extends TestCase
{
    /**
     * Перевіряє створення звичайного запиту за наявності id та збереження його значення,
     * зокрема числового нуля й рядка «0».
     */
    #[DataProvider('requestIds')]
    public function testPresentIdCreatesRequest(string|int $id): void
    {
        $request = RpcRequestFactory::fromArray(['id' => $id, 'method' => 'ping']);
        self::assertSame(RpcRequest::class, $request::class);
        self::assertSame($id, $request->getId());
        self::assertFalse($request->hasError());
        self::assertSame($id, $request->toArray()['id']);
    }

    public static function requestIds(): iterable
    {
        yield ['request-1'];
        yield [42];
        yield [0];
        yield ['0'];
    }

    public function testNotPresentIdCreatesRequest(): void
    {
        $request = RpcRequestFactory::fromArray(['method' => 'ping']);
        self::assertSame(RpcNotificationRequest::class, $request::class);
        self::assertFalse($request->hasError());

        $request = RpcRequestFactory::fromArray(['id' => 111, 'method' => 'ping']);
        self::assertSame(RpcRequest::class, $request::class);
        self::assertFalse($request->hasError());
    }

    /**
     * Перевіряє, що явно переданий некоректний id не перетворює запит на нотифікацію,
     * а помилка валідації залишається доступною в об’єкті запиту.
     */
    #[DataProvider('invalidIds')]
    public function testInvalidPresentIdStillCreatesRequest(mixed $id): void
    {
        $request = RpcRequestFactory::fromArray(['id' => $id, 'method' => 'ping'], 'v2');
        self::assertSame(RpcRequest::class, $request::class);
        self::assertTrue($request->hasError());
        self::assertSame('v2', $request->apiVersion);
    }

    public static function invalidIds(): iterable
    {
        yield [null];
        yield [''];
        yield [false];
        yield [[]];
    }

    /**
     * Перевіряє створення нотифікації без id зі збереженням методу, параметрів,
     * версій протоколу й API, сирого JSON та спеціальних RPC-параметрів.
     */
    public function testMissingIdCreatesNotificationWithAllRequestData(): void
    {
        $data = [
            'method' => 'users.update',
            'jsonrpc' => '2.0',
            'params' => ['z' => 2, 'a' => 1, '$rpc' => ['timeout' => 30, 'rayId' => 'parent', 'ignoreCache' => true]],
        ];
        $request = RpcRequestFactory::fromArray($data, 'v3');
        self::assertInstanceOf(RpcNotificationRequest::class, $request);
        self::assertFalse($request->hasError());
        self::assertSame('users.update', $request->getMethod());
        self::assertSame(['a' => 1, 'z' => 2], $request->getParams());
        self::assertSame('2.0', $request->getVersion());
        self::assertSame('v3', $request->apiVersion);
        self::assertSame($data, json_decode($request->getRawJson(), true));
        self::assertSame(['timeout' => 30, 'ignoreCache' => true, 'rayId' => 'parent'], $request->getSpecialParams());
        self::assertNotSame('', $request->getId());
    }

    /**
     * Перевіряє, що внутрішній id нотифікації не потрапляє до масиву або JSON,
     * а повторний розбір серіалізованих даних знову створює нотифікацію.
     */
    public function testNotificationSerializationOmitsInternalId(): void
    {
        $request = RpcRequestFactory::fromArray(['method' => 'ping']);
        $id = $request->getId();
        self::assertArrayNotHasKey('id', $request->toArray());
        self::assertArrayNotHasKey('id', Transformer::getDefault()->normalize($request, context: ['groups' => [$request::S_GROUP]]));
        $request->refreshRawJson(Transformer::getDefault());
        $data = json_decode($request->getRawJson(), true);
        self::assertArrayNotHasKey('id', $data);
        self::assertSame('ping', $data['method']);
        self::assertSame($id, $request->getId());
        self::assertInstanceOf(RpcNotificationRequest::class, RpcRequestFactory::fromJson($request->getRawJson()));
    }

    /**
     * Перевіряє, що фабрика однаково визначає тип запиту для масиву та JSON,
     * а наявні методи RpcRequest делегують створення фабриці.
     */
    public function testExistingEntryPointsUseFactory(): void
    {
        foreach ([RpcRequest::class, RpcRequestFactory::class] as $class) {
            self::assertInstanceOf(RpcNotificationRequest::class, $class::fromArray(['method' => 'ping']));
            $notification = $class::fromJson('{"method":"ping"}', 'v2');
            self::assertInstanceOf(RpcNotificationRequest::class, $notification);
            self::assertSame('v2', $notification->apiVersion);
            self::assertSame(RpcRequest::class, $class::fromJson('{"method":"ping","id":1}')::class);
        }
    }

    /**
     * Перевіряє сумісність успадкованих фабричних методів із конструктором нотифікації
     * без параметра id та з іншими нащадками RpcRequest.
     */
    public function testInheritedEntryPointsPreserveSubclass(): void
    {
        $notification = RpcNotificationRequest::fromArray(['method' => 'ping'], 'v2');
        self::assertInstanceOf(RpcNotificationRequest::class, $notification);
        self::assertFalse($notification->hasError());
        self::assertSame('ping', $notification->getMethod());
        self::assertSame('v2', $notification->apiVersion);
        self::assertInstanceOf(RpcNotificationRequest::class, RpcNotificationRequest::fromJson('{"method":"ping"}'));
        self::assertInstanceOf(CustomRequest::class, CustomRequest::fromArray(['id' => 1, 'method' => 'ping']));
        self::assertInstanceOf(CustomRequest::class, CustomRequest::fromJson('{"id":1,"method":"ping"}'));
    }

    /**
     * Перевіряє збереження типу нотифікації, помилки та всіх необхідних службових полів,
     * якщо метод, версія або параметри вхідного повідомлення некоректні.
     */
    #[DataProvider('invalidNotifications')]
    public function testInvalidNotificationKeepsInitializedState(array $data): void
    {
        $request = RpcRequestFactory::fromArray($data, 'v4');
        self::assertInstanceOf(RpcNotificationRequest::class, $request);
        self::assertTrue($request->hasError());
        self::assertSame('v4', $request->apiVersion);
        self::assertSame($data, json_decode($request->getRawJson(), true));
        self::assertIsArray($request->getParams());
        self::assertIsString($request->getMethod());
        self::assertIsString($request->getVersion());
        self::assertArrayNotHasKey('id', $request->toArray());
    }

    public static function invalidNotifications(): iterable
    {
        yield 'missing method' => [[]];
        yield 'blank method' => [['method' => '']];
        yield 'invalid method type' => [['method' => []]];
        yield 'invalid params type' => [['method' => 'ping', 'params' => 'bad']];
        yield 'invalid version type' => [['method' => 'ping', 'jsonrpc' => []]];
        yield 'invalid special params' => [['method' => 'ping', 'params' => ['$rpc' => ['timeout' => 1]]]];
    }

    /**
     * Перевіряє відхилення синтаксично некоректного JSON і скалярів замість об’єкта запиту.
     */
    #[DataProvider('invalidJson')]
    public function testInvalidJsonThrowsParseException(string $json): void
    {
        $this->expectException(RpcJsonParseException::class);
        RpcRequestFactory::fromJson($json);
    }

    public static function invalidJson(): iterable
    {
        yield ['{']; yield ['null']; yield ['42']; yield ['"ping"'];
    }

    /**
     * Перевіряє розбір змішаного пакета, правильні типи його елементів і окремі внутрішні
     * ідентифікатори для кількох нотифікацій, щоб вони не перезаписували одна одну в черзі.
     */
    public function testBatchUsesFactoryForEachEntry(): void
    {
        $batch = RpcBatchRequest::fromJson('[{"id":0,"method":"request"},{"method":"first"},{"method":"second"}]');
        $requests = array_values($batch->getCollection());
        self::assertCount(3, $requests);
        self::assertCount(3, $batch->getReadyToHandle());
        self::assertSame(RpcRequest::class, $requests[0]::class);
        self::assertSame(0, $requests[0]->getId());
        self::assertInstanceOf(RpcNotificationRequest::class, $requests[1]);
        self::assertInstanceOf(RpcNotificationRequest::class, $requests[2]);
        self::assertNotSame($requests[1]->getId(), $requests[2]->getId());
    }

    /**
     * Перевіряє, що нотифікація зберігає залежність від батьківського запиту
     * та отримує його результат через звичайний механізм черги.
     */
    public function testNotificationResolvesBatchDependency(): void
    {
        $batch = RpcBatchRequest::fromJson('[{"id":"parent","method":"find"},{"method":"notify","params":{"value":"@FROM:parent()"}}]');
        self::assertCount(1, $batch->getUnprocessedRequests());
        $notification = array_values($batch->getUnprocessedRequests())[0];
        $batch->addResponse(new \Ufo\RpcObject\RpcResponse('parent', 42), ['result' => 42]);
        self::assertFalse($notification->hasRequire());
        self::assertSame(42, $notification->getParams()['value']);
        self::assertSame($notification, $batch->getReadyToHandle()[$notification->getId()]);
    }
}
