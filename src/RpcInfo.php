<?php

namespace Ufo\RpcObject;

use Ufo\RpcError\RpcInternalException;

#[\Attribute] class RpcInfo
{
    const STRING = 'string';
    const INT = 'int';
    const BOOL = 'bool';

    public function __construct(
        protected array  $responseFormat = [],
        protected string $dto = ''
    )
    {
        if (!empty($this->dto)) {
            $this->parseDto();
        }
    }

    protected function parseDto()
    {
        if (!class_exists($this->dto)) {
            throw new RpcInternalException('Class "' . $this->dto . '" is not found');
        }
        $ref = new \ReflectionClass($this->dto);
        $this->responseFormat = [];
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $this->responseFormat[$property->getName()] = $property->getType()->getName();
        }
    }

    /**
     * @return array
     */
    public function getResponseFormat(): array
    {
        return $this->responseFormat;
    }
}
