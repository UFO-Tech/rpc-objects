<?php

namespace Ufo\RpcObject;

use Ufo\RpcError\RpcRuntimeException;
use Ufo\RpcObject\Rules\SpecialParamsRules;
use Ufo\RpcObject\Rules\Validator\Validator;

class SpecialRpcParams
{
    const PREFIX = '$rpc.';
    /**
     * Timeout for request. Second
     */
    const DEFAULT_TIMEOUT = 10;

    protected ?CallbackObject $callbackObject = null;

    public function __construct(
        ?string $callbackUrl = null,
        protected float $timeout = self::DEFAULT_TIMEOUT
    )
    {
        if (is_string($callbackUrl)) {
            $this->callbackObject = new CallbackObject($callbackUrl);
        }
    }

    public static function fromArray(array $data): static
    {
        Validator::validate($data, SpecialParamsRules::assertAllParamsCollection())->throw();

        return new static(
            $data['$rpc.callback'] ?? null,
            $data['$rpc.timeout'] ?? static::DEFAULT_TIMEOUT
        );
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
        $o = [self::PREFIX . 'timeout' => $this->timeout];
        if ($this->hasCallback()) {
            $o[self::PREFIX . 'callback'] = $this->getCallbackObject()->getTarget();
        }
        return $o;
    }
}
