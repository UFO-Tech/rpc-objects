<?php

namespace Ufo\RpcObject\Rules\Validator;

use Symfony\Component\Validator\Constraints\Url;

#[\Attribute]
class AssertRealUrl extends Url
{
    public string $message = 'This value is not a valid URL.';

    public function validatedBy(): string
    {
        return RealUrlValidator::class;
    }
}
