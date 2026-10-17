<?php

namespace Ufo\RpcObject\Tests\Rules;

use PHPUnit\Framework\TestCase;
use LogicException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ufo\RpcError\ConstraintsImposedException;
use Ufo\RpcObject\RPC\Assertions;
use Ufo\RpcObject\Rules\Validator\RpcValidator;
use Ufo\RpcObject\Tests\Fixtures\Procedure;

class RpcValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        CountingConstraint::$instances = 0;
    }

    /**
     * Перевіряє, що параметри без #[Assertions] не передаються у валідатор і не впливають на результат:
     * валідатор, що кидає виняток на будь-який виклик, не викликається жодного разу.
     */
    public function testParamsWithoutAssertionsAreSkipped(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects(self::never())->method('validate');
        (new RpcValidator($validator))->validateMethodParams(new PlainProcedure(), 'plain', ['a' => 'x', 'b' => []]);

        $validator = new RpcValidator(Validation::createValidator());
        $validator->validateMethodParams(new Procedure(), 'save', ['name' => 'Ada', 'age' => 37, 'unchecked' => ['any']]);
        self::assertTrue(true, 'An unchecked parameter must not produce violations.');
    }

    /**
     * Перевіряє, що constraint-и створюються один раз на class::method, а не на кожен виклик.
     */
    public function testConstraintsAreBuiltOncePerMethod(): void
    {
        $validator = new RpcValidator(Validation::createValidator());
        $procedure = new CountingProcedure();
        for ($i = 0; $i < 3; $i++) {
            $validator->validateMethodParams($procedure, 'run', ['value' => 'x']);
        }
        self::assertSame(1, CountingConstraint::$instances);

        $validator->validateMethodParams($procedure, 'other', ['value' => 'x']);
        self::assertSame(2, CountingConstraint::$instances, 'A different method has its own cache entry.');
    }

    /**
     * Фіксує формат помилок і текст ConstraintsImposedException, включно з propertyPath вкладених полів.
     */
    public function testViolationsFormatIsPreserved(): void
    {
        $validator = new RpcValidator(Validation::createValidator());
        $validator->validateMethodParams(new Procedure(), 'save', ['name' => 'Ada', 'age' => 37]);

        $e = $this->catchImposed($validator, new Procedure(), 'save', ['name' => 'A', 'age' => -1]);
        self::assertSame('Invalid Data for call method: save', $e->getMessage());
        self::assertSame([
            'name' => ['This value is too short. It should have 3 characters or more.'],
            'age' => ['This value should be positive.'],
        ], $e->getConstraintsImposed());

        $e = $this->catchImposed($validator, new NestedProcedure(), 'create', ['data' => ['email' => '']]);
        self::assertSame(['data[email]' => ['This value should not be blank.']], $e->getConstraintsImposed());
    }

    /**
     * Перевіряє, що некоректно налаштований constraint більше не вимикає валідацію мовчки.
     */
    public function testMisconfiguredConstraintIsNotSwallowed(): void
    {
        $validator = new RpcValidator(Validation::createValidator());
        try {
            $validator->validateMethodParams(new BrokenProcedure(), 'missingOptions', ['value' => 'x']);
            self::fail('Expected MissingOptionsException.');
        } catch (MissingOptionsException) {
        }
        // Невдала побудова не кешується: повторний виклик знову кидає помилку.
        $this->expectException(MissingOptionsException::class);
        $validator->validateMethodParams(new BrokenProcedure(), 'missingOptions', ['value' => 'x']);
    }

    /**
     * Перевіряє, що виняток усередині валідатора пробрасується назовні.
     */
    public function testValidatorErrorIsNotSwallowed(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('validator failure');
        (new RpcValidator(Validation::createValidator()))
            ->validateMethodParams(new BrokenProcedure(), 'throwing', ['value' => 'x']);
    }

    /**
     * Перевіряє, що послідовні виклики з різними даними не змішують violations.
     */
    public function testSequentialCallsDoNotMixViolations(): void
    {
        $validator = new RpcValidator(Validation::createValidator());
        $procedure = new Procedure();

        $e = $this->catchImposed($validator, $procedure, 'save', ['name' => '', 'age' => 1]);
        self::assertSame(['name'], array_keys($e->getConstraintsImposed()));

        $e = $this->catchImposed($validator, $procedure, 'save', ['name' => 'Ada', 'age' => 0]);
        self::assertSame(['age' => ['This value should be positive.']], $e->getConstraintsImposed());

        $validator->validateMethodParams($procedure, 'save', ['name' => 'Ada', 'age' => 1]);
    }

    private function catchImposed(
        RpcValidator $validator,
        object $procedure,
        string $method,
        array $params
    ): ConstraintsImposedException {
        try {
            $validator->validateMethodParams($procedure, $method, $params);
        } catch (ConstraintsImposedException $e) {
            return $e;
        }
        self::fail('Expected ConstraintsImposedException.');
    }
}

class CountingConstraint extends Assert\NotNull
{
    public static int $instances = 0;

    public function __construct()
    {
        parent::__construct();
        static::$instances++;
    }

    public function validatedBy(): string
    {
        return Assert\NotNullValidator::class;
    }
}

class ThrowingConstraint extends Constraint {}

class ThrowingConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        throw new LogicException('validator failure');
    }
}

class PlainProcedure
{
    public function plain(string $a, array $b = []): void {}
}

class CountingProcedure
{
    public function run(#[Assertions([new CountingConstraint()])] string $value): void {}

    public function other(#[Assertions([new CountingConstraint()])] string $value): void {}
}

class NestedProcedure
{
    public function create(
        #[Assertions([new Assert\Collection(['email' => [new Assert\NotBlank()]])])]
        array $data,
    ): void {}
}

class BrokenProcedure
{
    public function missingOptions(#[Assertions([new Assert\Length()])] string $value): void {}

    public function throwing(#[Assertions([new ThrowingConstraint()])] string $value): void {}
}
