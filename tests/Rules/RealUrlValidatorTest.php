<?php

namespace Ufo\RpcObject\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Ufo\RpcObject\Rules\Validator\RealUrlValidator;
use Ufo\RpcObject\Rules\Validator\AssertRealUrl;
use Ufo\RpcObject\Rules\CallbackRules;
use Ufo\RpcObject\{CallbackObject, RpcRequest, SpecialRpcParams};

class RealUrlValidatorTest extends TestCase
{
    private array $previousCache;

    protected function setUp(): void
    {
        $this->previousCache = (new \ReflectionProperty(RealUrlValidator::class, 'checkedUrls'))->getValue();
        $this->setCache(['https://callback.example.test/hook' => true, 'https://down.example.test/hook' => false]);
    }

    protected function tearDown(): void { $this->setCache($this->previousCache); }

    private function setCache(array $cache): void
    {
        (new \ReflectionProperty(RealUrlValidator::class, 'checkedUrls'))->setValue(null, $cache);
    }

    /**
     * Перевіряє, що валідатор доступності URL відхиляє обмеження іншого типу.
     */
    public function testWrongConstraintTypeThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        (new RealUrlValidator())->validate('https://example.test', new Assert\NotBlank());
    }

    /**
     * Перевіряє пропуск перевірки доступності для null і порожнього рядка та вибір валідатора для
     * AssertRealUrl.
     */
    public function testEmptyValuesDoNotTriggerNetworkValidation(): void
    {
        $validator = Validation::createValidator();
        self::assertCount(0, $validator->validate(null, new AssertRealUrl()));
        self::assertCount(0, $validator->validate('', new AssertRealUrl()));
        self::assertSame(RealUrlValidator::class, (new AssertRealUrl())->validatedBy());
    }

    /**
     * Перевіряє використання кешованої доступності URL і формування повідомлення про порушення для
     * недоступної адреси.
     */
    public function testCachedReachableAndUnreachableUrls(): void
    {
        $validator = Validation::createValidator();
        self::assertCount(0, $validator->validate('https://callback.example.test/hook', CallbackRules::assertUrl()));
        $errors = $validator->validate('https://down.example.test/hook', new AssertRealUrl(message: 'Unavailable'));
        self::assertCount(1, $errors);
        self::assertSame('Unavailable', $errors[0]->getMessage());
        self::assertSame('https://down.example.test/hook', $errors[0]->getParameters()['{{ string }}']);
    }

    /**
     * Перевіряє збереження перевіреної адреси в callback і спеціальних параметрах та визначення запиту як
     * асинхронного.
     */
    public function testCallbackAndAsyncRequestUseValidatedTarget(): void
    {
        $url = 'https://callback.example.test/hook';
        $callback = new CallbackObject($url);
        self::assertSame($url, $callback->getTarget());
        self::assertSame($url, (string)$callback);
        $params = new SpecialRpcParams($url, 20, 'parent', true);
        self::assertTrue($params->hasCallback());
        self::assertSame($url, $params->toArray()['callback']);
        $request = RpcRequest::fromArray(['id' => 1, 'method' => 'ping', 'params' => ['$rpc' => ['callback' => $url]]]);
        self::assertFalse($request->hasError());
        self::assertTrue($request->isAsync());
        self::assertSame($url, $request->getCallbackUrl());
        self::assertSame($url, $request->getCallbackObject()->getTarget());
    }

    /**
     * Фіксує поточну поведінку CallbackObject: недоступна адреса зберігається без винятку, попри виявлену
     * помилку валідації.
     */
    public function testCallbackObjectCurrentlyRetainsUnreachableTarget(): void
    {
        // Characterizes the existing TODO in CallbackObject::validate(): errors are not thrown.
        self::assertSame('https://down.example.test/hook', (string)new CallbackObject('https://down.example.test/hook'));
    }
}
