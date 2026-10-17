<?php

namespace Ufo\RpcObject\Rules\Validator;

use ReflectionMethod;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ufo\RpcError\ConstraintsImposedException;
use Ufo\RpcObject\RPC\Assertions;

use function array_map;
use function count;

class RpcValidator
{
    protected int $violationCount = 0;

    /**
     * @var ConstraintViolationListInterface[]
     */
    protected array $violations;

    /**
     * Constraint-и параметрів методів: "<class>::<method>" => [paramName => Constraint[]].
     * Залежать лише від коду, тому живуть весь процес (безпечно для long-running воркерів).
     *
     * @var array<string, array<string, Constraint[]>>
     */
    protected array $constraintsCache = [];

    public function __construct(protected ValidatorInterface $validator) {}

    /**
     * @throws ConstraintsImposedException|\ReflectionException
     */
    public function validateMethodParams(
        object $procedureObject,
        string $procedureMethod,
        array $params
    ): void {
        $this->clearPrev();
        foreach ($this->getMethodConstraints($procedureObject, $procedureMethod) as $paramName => $assertions) {
            $this->validateParam($paramName, $assertions, $params[$paramName] ?? null);
        }
        if ($this->violationCount > 0) {
            $errors = [];
            foreach ($this->violations as $paramName => $violations) {
                array_map(function (ConstraintViolationInterface $v) use (&$errors, $paramName) {
                    $errors[$paramName . $v->getPropertyPath()][] = $v->getMessage();
                }, (array)$violations->getIterator());
            }
            throw new ConstraintsImposedException("Invalid Data for call method: {$procedureMethod}", $errors);
        }
    }

    protected function clearPrev(): void
    {
        $this->violations = [];
        $this->violationCount = 0;
    }

    /**
     * Reflection і створення constraint-ів виконуються один раз на метод у межах процесу.
     * Помилки побудови (некоректний constraint) не ковтаються і не потрапляють у кеш.
     *
     * @return array<string, Constraint[]>
     * @throws \ReflectionException
     */
    protected function getMethodConstraints(object $procedureObject, string $procedureMethod): array
    {
        $key = $procedureObject::class . '::' . $procedureMethod;
        if (!isset($this->constraintsCache[$key])) {
            $constraints = [];
            $refMethod = new ReflectionMethod($procedureObject, $procedureMethod);
            foreach ($refMethod->getParameters() as $paramRef) {
                $attributes = $paramRef->getAttributes(Assertions::class);
                if (count($attributes) === 0) {
                    continue;
                }
                $constraints[$paramRef->getName()] = $attributes[0]->newInstance()->assertions;
            }
            $this->constraintsCache[$key] = $constraints;
        }

        return $this->constraintsCache[$key];
    }

    /**
     * @param Constraint[] $assertions
     */
    protected function validateParam(string $paramName, array $assertions, mixed $value): void
    {
        $violations = $this->validator->validate($value, $assertions);
        if (count($violations) > 0) {
            $this->violations[$paramName] = $violations;
            $this->violationCount += count($violations);
        }
    }

}