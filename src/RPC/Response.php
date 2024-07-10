<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\RpcError\RpcInternalException;

use function implode;

#[Attribute(Attribute::TARGET_METHOD)]
class Response
{
    const string STRING = 'string';
    const string INT = 'int';
    const string BOOL = 'bool';

    /**
     * @throws RpcInternalException
     */
    public function __construct(
        protected ?array $responseFormat = null,
        protected string $dto = '',
        protected bool $collection = false
    ) {
        if (!empty($this->dto)) {
            $this->parseDto();
        }
    }

    protected function parseDto(): void
    {
        if (!class_exists($this->dto)) {
            throw new RpcInternalException('Class "'.$this->dto.'" is not found');
        }
        $ref = new \ReflectionClass($this->dto);
        $this->responseFormat = [];
        $this->responseFormat['$dto'] = $ref->getShortName();
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $nullable = ($property->getType()->allowsNull()) ? '?' : '';
            try {
                $this->responseFormat[$property->getName()] = $nullable.$property->getType()->getName();
            } catch (\Throwable) {
                $t = [];
                foreach ($property->getType()->getTypes() as $type) {
                    $t[] = $type->getName();
                }
                $this->responseFormat[$property->getName()] = implode('|', $t);
            }
        }
        if ($this->collection) {
            $this->responseFormat = [$this->responseFormat];
        }
    }

    /**
     * @return array
     */
    public function getResponseFormat(): ?array
    {
        return $this->responseFormat;
    }

    public function getDto(): string
    {
        return $this->dto;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }
}
