<?php

namespace Ufo\RpcObject\Rules\Validator;

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\UrlValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function current;
use function stripos;

class RealUrlValidator extends UrlValidator
{
    const string HEADER = 'Allow';

    protected static array $checkedUrls = [];

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof AssertRealUrl) {
            throw new UnexpectedTypeException($constraint, AssertRealUrl::class);
        }

        if (empty($value)) {
            return;
        }

        parent::validate($value, $constraint);

        $isValid = self::$checkedUrls[$value] ?? $this->checkUrl($value);
        $this->cacheResult($value, $isValid);

        if (!$isValid) {
            $this->addViolation($constraint, $value);
        }
    }

    private function checkUrl(string $value): bool
    {
        try {
            $client = new CurlHttpClient(['headers'=>['Content-Type' => 'application/json',],'timeout' => 2, 'max_duration' => 2]);

            $response = $client->request(Request::METHOD_OPTIONS, $value);
            $statusCode = $response->getStatusCode();
            $responseHeaders = $response->getHeaders();

            if ($statusCode !== 200 || !isset($responseHeaders[self::HEADER])) {
                $postResponse = $client->request(
                    Request::METHOD_POST,
                    $value,
                    [
                        'json' => [
                            'rpc.callback' => 'check real webhook url',
                        ]
                    ]
                );
                $valid = $postResponse->getStatusCode() < 400;
            } else {
                $valid = stripos(current($responseHeaders[self::HEADER]), Request::METHOD_POST) !== false;
            }
        } catch (\Throwable $e) {
            $valid = false;
        }
        return $valid;
    }


    private function cacheResult(string $value, bool $isValid): void
    {
        self::$checkedUrls[$value] = $isValid;
    }

    private function addViolation(Constraint $constraint, string $value): void
    {
        $this->context->buildViolation($constraint->message)
                      ->setParameter('{{ string }}', $value)
                      ->addViolation();
    }
}
