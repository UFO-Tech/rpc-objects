<?php

namespace Ufo\RpcObject\Rules\Validator;

use ReflectionMethod;
use ReflectionParameter;
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
        $refMethod = new ReflectionMethod($procedureObject, $procedureMethod);
        $paramRefs = $refMethod->getParameters();
        foreach ($paramRefs as $paramRef) {
            $this->validateParam($paramRef, $params[$paramRef->getName()] ?? null);
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

    protected function validateParam(ReflectionParameter $paramRef, mixed $value): void
    {
        try {
            $attribute = $paramRef->getAttributes(Assertions::class);
            if (count($attribute) > 0) {
                $attribute = $attribute[0];
            }
            $assertions = $attribute->newInstance()->assertions;
            $violations = $this->validator->validate($value, $assertions);
            if (count($violations) > 0) {
                $this->violations[$paramRef->getName()] = $violations;
                $this->violationCount += count($violations);
            }
        } catch (\Throwable) {
        }
    }

}