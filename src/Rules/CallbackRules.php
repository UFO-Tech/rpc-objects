<?php

namespace Ufo\RpcObject\Rules;

use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcObject\Rules\Validator\AssertRealUrl;

class CallbackRules
{
    public static function assertUrl(): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Url(),
            new Assert\Url(message: 'Must have a protocol', relativeProtocol: true,),
            new Assert\Url(message: 'Invalid protocol', protocols: ['http'],),
            new AssertRealUrl(message: 'Callback does not respond'),
        ];
    }

}
