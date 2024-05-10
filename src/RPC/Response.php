<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\RpcError\RpcInternalException;

#[Attribute(Attribute::TARGET_METHOD)]
class Response
{
    const STRING = 'string';
    const INT = 'int';
    const BOOL = 'bool';

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
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $this->responseFormat[$property->getName()] = $property->getType()->getName();
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

}
