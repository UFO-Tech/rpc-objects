<?php

namespace Ufo\RpcObject;

use Ufo\RpcError\RpcRuntimeException;

class SpecialRpcParams
{
    protected ?CallbackObject $callbackObject = null;

    public function __construct(
        ?string $callbackUrl = null,
        protected float $timeout = SpecialRpcParamsEnum::DEFAULT_TIMEOUT,
        protected string|int|null $rayId = null,
    )
    {
        if (is_string($callbackUrl)) {
            $this->callbackObject = new CallbackObject($callbackUrl);
        }
    }

    /**
     * @return CallbackObject
     * @throws RpcRuntimeException
     */
    public function getCallbackObject(): CallbackObject
    {
        if (!$this->hasCallback()) {
            throw new RpcRuntimeException('Callback is not set');
        }
        return $this->callbackObject;
    }

    /**
     * @return float|int
     */
    public function getTimeout(): float|int
    {
        return $this->timeout;
    }

    public function hasCallback(): bool
    {
        return !is_null($this->callbackObject);
    }

    public function toArray(): array
    {
        $o = [SpecialRpcParamsEnum::TIMEOUT->value => $this->timeout];
        if ($this->hasCallback()) {
            $o[SpecialRpcParamsEnum::CALLBACK->value] = $this->getCallbackObject()->getTarget();
        }
        return $o;
    }

    public function getRayId(): int|string|null
    {
        return $this->rayId;
    }
}
