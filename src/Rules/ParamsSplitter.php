<?php

namespace Ufo\RpcObject\Rules;

use Ufo\RpcObject\SpecialRpcParams;

class ParamsSplitter
{

    protected function __construct(protected array &$params, protected ?SpecialRpcParams $specialParams = null)
    {
    }

    public static function split(array &$params): static
    {
        $specialParams = null;
        $sp = [];
        if ($matched = preg_grep('/^\\$rpc\./i', array_keys($params))) {
            array_walk($matched, function ($v) use (&$sp, &$params) {
                $sp[$v] = $params[$v];
                unset($params[$v]);
            });
            $specialParams = SpecialRpcParams::fromArray($sp);
        }
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
