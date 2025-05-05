<?php

namespace Ufo\RpcObject\Transformer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;
use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcError\ExceptionToArrayTransformer;

final class RpcErrorNormalizer implements NormalizerInterface
{
    const RPC_CONTEXT = 'rpc_handle';

    public function __construct(protected string $environment = 'dev') {}

    /**
     * @param Throwable $object
     * @param ?string $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $normalized = new ExceptionToArrayTransformer($data, $this->environment);

        return $normalized->infoByEnvironment();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AbstractRpcErrorException && ($context[RpcErrorNormalizer::RPC_CONTEXT] ?? false);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return false;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            AbstractRpcErrorException::class => true,
        ];
    }

}

