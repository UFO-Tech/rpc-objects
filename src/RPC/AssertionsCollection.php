<?php

namespace Ufo\RpcObject\RPC;

use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Ufo\RpcObject\Transformer\AttributeHelper;

use function array_map;

class AssertionsCollection
{
    /**
     * @var Assertions[]
     */
    protected array $collection = [];

    public function addAssertions(string|int $key, Assertions $assertions): static
    {
        $this->collection[$key] = $assertions;

        return $this;
    }

    public function getAssertionsCollection(): array
    {
        return $this->collection;
    }

    public function toArray(): array
    {
        return array_map(function (Assertions $a) {
            return $a->toArray();
        }, $this->collection);
    }

    /**
     * @throws ReflectionException
     */
    public function fillAssertion(
        string $className,
        ReflectionMethod $method,
        ReflectionParameter $paramRef
    ): void {
        $attrArguments = AttributeHelper::getMethodArgumentAttributesAsString(
            $className,
            $method->getName(),
            $paramRef->getName()
        );

        if(empty($paramRef->getAttributes(Assertions::class))) return;
        $assertionsOld = $paramRef->getAttributes(Assertions::class)[0]->newInstance();
        $assertions = new Assertions($assertionsOld->assertions, $attrArguments);

        $this->addAssertions(
            $paramRef->getName(),
            $assertions
        );
    }
}