<?php

namespace Ufo\RpcObject\Tests\Rules;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Ufo\RpcObject\Rules\Validator\{AssertRealUrl, RealUrlValidator};

#[Group('integration')]
class RealUrlHttpTest extends TestCase
{
    /**
     * Перевіряє через локальний HTTP-сервер успішний callback, відмову, кешування перевірки, запити
     * OPTIONS і POST та помилку після зупинки сервера.
     */
    public function testLocalHttpSuccessFailureAndCachedResult(): void
    {
        if (!extension_loaded('curl') || !is_callable('proc_open')) {
            self::markTestSkipped('HTTP integration requires ext-curl and proc_open.');
        }
        $cacheProperty = new \ReflectionProperty(RealUrlValidator::class, 'checkedUrls');
        $previousCache = $cacheProperty->getValue();
        $cacheProperty->setValue(null, []);
        $log = tempnam(sys_get_temp_dir(), 'rpc-http-');
        $serverLog = tempnam(sys_get_temp_dir(), 'rpc-server-');
        $process = null;
        try {
            // Ask the OS for a free loopback port; expose no port outside the test runtime.
            $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $message);
            self::assertIsResource($socket, $message);
            $address = stream_socket_get_name($socket, false);
            fclose($socket);
            $process = proc_open(
                [PHP_BINARY, '-d', 'xdebug.mode=off', '-S', $address, dirname(__DIR__) . '/Fixtures/http-router.php'],
                [0 => ['pipe', 'r'], 1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']],
                $pipes,
                null,
                array_merge(getenv(), ['RPC_TEST_HTTP_LOG' => $log, 'XDEBUG_MODE' => 'off']),
            );
            self::assertIsResource($process);
            fclose($pipes[0]);
            $ready = false;
            $deadline = microtime(true) + 5;
            while (microtime(true) < $deadline) {
                $connection = @stream_socket_client('tcp://' . $address, $errno, $message, 0.05);
                if (is_resource($connection)) {
                    fclose($connection);
                    $ready = true;
                    break;
                }
                usleep(10000);
            }
            self::assertTrue($ready, 'Local HTTP server failed to start: ' . file_get_contents($serverLog));
            $validator = Validation::createValidator();
            $url = 'http://' . $address;
            self::assertCount(0, $validator->validate($url . '/accept', new AssertRealUrl()));
            $firstLog = file_get_contents($log);
            self::assertCount(0, $validator->validate($url . '/accept', new AssertRealUrl()));
            self::assertSame($firstLog, file_get_contents($log), 'Cached validation should issue no further HTTP requests.');
            self::assertCount(1, $validator->validate($url . '/reject', new AssertRealUrl()));
            $requests = array_map(fn (string $line): array => json_decode($line, true), array_filter(explode("\n", trim(file_get_contents($log)))));
            self::assertSame(['OPTIONS', 'POST', 'OPTIONS', 'POST'], array_column($requests, 'method'));
            self::assertSame(['rpc.callback' => 'check real webhook url'], json_decode($requests[1]['body'], true));
            // A fresh URL after shutdown exercises the HTTP transport-exception path.
            proc_terminate($process);
            proc_close($process);
            $process = null;
            self::assertCount(1, $validator->validate($url . '/offline', new AssertRealUrl()));
        } finally {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
            $cacheProperty->setValue(null, $previousCache);
            unlink($log);
            unlink($serverLog);
        }
    }
}
