<?php

namespace Ufo\RpcObject\Rules\Validator;

use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcError\RpcBadParamException;
use Ufo\DTO\Helpers\Validator as DtoValidator;

class Validator extends DtoValidator
{
    /**
     * @param string $class
     * @return void
     * @throws AbstractRpcErrorException
     */
    public function throw(string $class = RpcBadParamException::class): void
    {
        if (!$class instanceof AbstractRpcErrorException) {
            $class = RpcBadParamException::class;
        }
        if ($this->hasErrors()) {
            throw new $class($this->getCurrentError());
        }
    }
}
