<?php

namespace Ufo\RpcObject\Rules\Validator;

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\UrlValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RealUrlValidator extends UrlValidator
{
    public function __construct() {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof AssertRealUrl) {
            throw new UnexpectedTypeException($constraint, AssertRealUrl::class);
        }
        parent::validate($value, $constraint);
        if (null === $value || '' === $value) {
            return;
        }
        try {
            $error = false;
            $client = new CurlHttpClient();
            $client->withOptions([
                'timeout'      => 1,
                'max_duration' => 1,
            ]);
            $request = $client->request('GET', $value);
            $request->getContent();
        } catch (\Throwable $e) {
            $error = true;
        }
        if ($error) {
            $this->context->buildViolation($constraint->message)->setParameter('{{ string }}', $value)->addViolation()
            ;
        }
    }

}
