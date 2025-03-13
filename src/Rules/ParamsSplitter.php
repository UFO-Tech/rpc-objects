<?php

namespace Ufo\RpcObject\Rules;

use Ufo\RpcObject\SpecialRpcParams;
use Ufo\RpcObject\SpecialRpcParamsEnum;

class ParamsSplitter
{

    protected function __construct(protected array &$params, protected ?SpecialRpcParams $specialParams = null) {}

    public static function split(array &$params): static
    {
        $sp = $params[SpecialRpcParamsEnum::PREFIX] ?? [];
        $specialParams = SpecialRpcParamsEnum::fromArray($sp);
        unset($params[SpecialRpcParamsEnum::PREFIX]);
        return new static($params, $specialParams);
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return SpecialRpcParams|null
     */
    public function getSpecialParams(): ?SpecialRpcParams
    {
        return $this->specialParams;
    }

}
