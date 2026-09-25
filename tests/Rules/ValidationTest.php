<?php

namespace Ufo\RpcObject\Tests\Rules;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcError\ConstraintsImposedException;
use Ufo\RpcError\RpcBadParamException;
use Ufo\RpcObject\Rules\RequestRules;
use Ufo\RpcObject\Rules\ParamsSplitter;
use Ufo\RpcObject\Rules\Validator\RpcValidator;
use Ufo\RpcObject\Rules\Validator\Validator;
use Ufo\RpcObject\SpecialRpcParams;
use Ufo\RpcObject\SpecialRpcParamsEnum;
use Ufo\RpcObject\Tests\Fixtures\Procedure;

class ValidationTest extends TestCase
{
    /**
     * Перевіряє прийняття граничних і дробових значень таймауту в дозволеному діапазоні.
     */
    #[DataProvider('timeouts')]
    public function testTimeoutBounds(int|float $timeout): void
    {
        self::assertSame($timeout, SpecialRpcParamsEnum::fromArray(['timeout' => $timeout])->getTimeout());
    }

    public static function timeouts(): iterable { yield [10]; yield [120]; yield [10.5]; }

    /**
     * Перевіряє відхилення таймаутів поза дозволеним діапазоном і значень некоректного типу.
     */
    #[DataProvider('badTimeouts')]
    public function testInvalidTimeoutRejected(mixed $timeout): void
    {
        $this->expectException(RpcBadParamException::class);
        SpecialRpcParamsEnum::fromArray(['timeout' => $timeout]);
    }

    public static function badTimeouts(): iterable
    {
        foreach ([9, 121, 0, -10, '30', false, null] as $i => $value) yield (string)$i => [$value];
    }

    /**
     * Перевіряє стандартні й явно задані спеціальні RPC-параметри, їх серіалізацію та прийняття додаткових
     * полів.
     */
    public function testSpecialParamsDefaultsAndValues(): void
    {
        $params = SpecialRpcParamsEnum::fromArray([]);
        self::assertSame(['timeout' => 10, 'ignoreCache' => false], $params->toArray());
        self::assertNull($params->getRayId());
        self::assertFalse($params->hasCallback());
        $params = SpecialRpcParamsEnum::fromArray(['timeout' => 40, 'rayId' => 'root', 'ignoreCache' => true, 'future' => 1]);
        self::assertSame(['timeout' => 40, 'ignoreCache' => true, 'rayId' => 'root'], $params->toArray());
        self::assertSame('root', $params->getRayId());
    }

    /**
     * Перевіряє вилучення службового ключа $rpc з вихідного масиву та збереження посилання на звичайні
     * параметри.
     */
    public function testSplitterRemovesReservedKeyAndRetainsReference(): void
    {
        $params = ['name' => 'Ada', '$rpc' => ['timeout' => 20]];
        $splitter = ParamsSplitter::split($params);
        self::assertSame(['name' => 'Ada'], $params);
        self::assertSame($params, $splitter->getParams());
        self::assertSame(20, $splitter->getSpecialParams()->getTimeout());
        $params['name'] = 'Grace';
        self::assertSame('Grace', $splitter->getParams()['name']);
    }

    /**
     * Перевіряє виняток при отриманні callback, який не було задано.
     */
    public function testMissingCallbackThrows(): void
    {
        $this->expectException(\Ufo\RpcError\RpcRuntimeException::class);
        (new SpecialRpcParams())->getCallbackObject();
    }

    /**
     * Перевіряє успішну валідацію коректних даних, доступ до повідомлення про порушення та виняток для
     * некоректних даних.
     */
    public function testValidatorAcceptsValidDataAndThrowsForInvalidData(): void
    {
        $valid = Validator::validate('Ada', [new Assert\NotBlank()]);
        self::assertFalse($valid->hasErrors());
        $valid->throw();
        $invalid = Validator::validate('', [new Assert\NotBlank()]);
        self::assertTrue($invalid->hasErrors());
        self::assertNotEmpty($invalid->getCurrentError());
        $this->expectException(RpcBadParamException::class);
        $invalid->throw();
    }

    /**
     * Перевіряє правила для обов’язкових і необов’язкових полів запиту, їх типів, версії та параметрів.
     */
    #[DataProvider('requestRules')]
    public function testRequestRules(array $input, bool $valid): void
    {
        $violations = Validation::createValidator()->validate($input, RequestRules::assertAll());
        self::assertSame($valid, count($violations) === 0);
    }

    public static function requestRules(): iterable
    {
        yield 'minimal' => [['method' => 'ping'], true];
        yield 'extra keys' => [['id' => 3, 'method' => 'ping', 'extension' => true], true];
        yield 'missing method' => [[], false];
        yield 'invalid id' => [['id' => [], 'method' => 'ping'], false];
        yield 'invalid method' => [['method' => 2], false];
        yield 'invalid version' => [['method' => 'ping', 'jsonrpc' => 'bad'], false];
        yield 'empty params' => [['method' => 'ping', 'params' => []], false];
        yield 'valid params' => [['method' => 'ping', 'params' => ['name' => 'Ada']], true];
    }

    /**
     * Перевіряє об’єднання помилок за параметрами методу та очищення попередніх порушень перед наступним
     * коректним викликом.
     */
    public function testMethodValidationAggregatesErrorsAndClearsPreviousState(): void
    {
        $validator = new RpcValidator(Validation::createValidator());
        $procedure = new Procedure();
        try {
            $validator->validateMethodParams($procedure, 'save', ['name' => '', 'age' => -1]);
            self::fail('Expected parameter validation to fail.');
        } catch (ConstraintsImposedException $e) {
            self::assertSame(['name', 'age'], array_keys($e->getConstraintsImposed()));
            self::assertStringContainsString('save', $e->getMessage());
        }
        $validator->validateMethodParams($procedure, 'save', ['name' => 'Ada', 'age' => 37]);
        self::assertTrue(true, 'A valid call after an invalid call must not retain violations.');
    }

    /**
     * Перевіряє, що відсутній обов’язковий параметр методу спричиняє помилку валідації.
     */
    public function testMissingRequiredMethodParameterIsValidated(): void
    {
        $this->expectException(ConstraintsImposedException::class);
        (new RpcValidator(Validation::createValidator()))->validateMethodParams(new Procedure(), 'save', ['age' => 37]);
    }
}
