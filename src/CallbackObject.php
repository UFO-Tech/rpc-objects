<?php

namespace Ufo\RpcObject;

use Ufo\RpcObject\Rules\CallbackRules;
use Ufo\RpcObject\Rules\Validator\Validator;

class CallbackObject
{
    public function __construct(
        protected string $target,
    )
    {
        $this->validate();
    }

    protected function validate()
    {
        $validator = Validator::validate($this->target, CallbackRules::assertUrl());

        if ($validator->hasErrors()) {
            $message = $validator->getCurrentError();
            // todo fix it
        }
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    public function __toString(): string
    {
        return $this->getTarget();
    }

}
