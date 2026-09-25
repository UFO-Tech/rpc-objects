<?php

namespace Ufo\RpcObject\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ufo\RpcObject\RpcTransport;
use Ufo\RpcObject\Secret;
use Ufo\RpcObject\RpcAsyncRequest;
use Ufo\RpcObject\RpcRequest;

class TransportTest extends TestCase
{
    /**
     * Перевіряє розбір і точне відновлення DSN, формування адреси сервера та маскування облікових даних і
     * секретних параметрів у масиві.
     */
    public function testDsnRoundTripAndRedaction(): void
    {
        $dsn = 'https://alice:password@example.test:8443/api?access_token=abc123&key=xyz&limit=5#result';
        $transport = RpcTransport::fromDsn($dsn);
        self::assertSame($dsn, (string)$transport);
        self::assertSame('https://alice:password@example.test:8443', $transport->getDomainUrl());
        self::assertSame('alice', $transport->user);
        self::assertSame('password', $transport->pass);
        self::assertSame([
            'scheme' => 'https', 'user' => '{user}', 'pass' => '{pass}',
            'host' => 'example.test', 'port' => 8443, 'path' => '/api',
            'query' => 'access_token={access_token}&key={key}&limit=5', 'fragment' => 'result',
        ], $transport->toArray());
    }

    /**
     * Перевіряє відновлення DSN із необов’язковими частинами та відсутність значень null у його поданні як
     * масиву.
     */
    #[DataProvider('dsns')]
    public function testOptionalDsnParts(string $dsn, string $domain): void
    {
        $transport = RpcTransport::fromDsn($dsn);
        self::assertSame($dsn, (string)$transport);
        self::assertSame($domain, $transport->getDomainUrl());
        self::assertNotContains(null, $transport->toArray());
    }

    public static function dsns(): iterable
    {
        yield ['https://example.test', 'https://example.test'];
        yield ['amqp://guest@example.test/queue', 'amqp://guest@example.test'];
        yield ['/relative/path', ''];
        yield ['', ''];
    }

    /**
     * Перевіряє перетворення рядкового порту на ціле число та обробку порожнього набору частин адреси.
     */
    public function testArrayPortIsConverted(): void
    {
        $transport = RpcTransport::fromArray(['host' => 'localhost', 'port' => '5672']);
        self::assertSame(5672, $transport->port);
        self::assertSame('localhost:5672', (string)$transport);
        self::assertSame([], RpcTransport::fromArray([])->toArray());
    }

    /**
     * Перевіряє заміну секретних значень параметрів запиту на маркери зі збереженням звичайних параметрів.
     */
    #[DataProvider('queries')]
    public function testSecretReplacement(string $input, string $expected): void
    {
        self::assertSame($expected, (new Secret())->replace($input));
    }

    public static function queries(): iterable
    {
        yield ['token=abc&access_key=123&secret_value=xyz', 'token={token}&access_key={access_key}&secret_value={secret_value}'];
        yield ['page=2&name=Ada', 'page=2&name=Ada'];
        yield ['', ''];
    }

    /**
     * Перевіряє маскування значення за користувацьким регулярним виразом.
     */
    public function testCustomSecretPattern(): void
    {
        self::assertSame('password={password}', (new Secret('/(password)=([^&]+)/'))->replace('password=a-b.c'));
    }

    /**
     * Перевіряє збереження запиту, токена й метаданих в асинхронній обгортці та сумісність застарілого
     * методу доступу до запиту.
     */
    public function testAsyncWrapperPreservesRequestAndMetadata(): void
    {
        $request = new RpcRequest(1, 'ping');
        $async = new RpcAsyncRequest($request, 'token', ['trace' => 1]);
        self::assertSame($request, $async->rpcRequest);
        self::assertSame($request, $async->getRpcRequest());
        self::assertSame('token', $async->token);
        self::assertSame(['trace' => 1], $async->meta);
        self::assertSame('', (new RpcAsyncRequest($request))->token);
    }
}
