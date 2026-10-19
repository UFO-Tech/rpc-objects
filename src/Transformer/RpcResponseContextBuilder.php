<?php

namespace Ufo\RpcObject\Transformer;

use Symfony\Component\Serializer\Context\ContextBuilderInterface;
use Symfony\Component\Serializer\Context\ContextBuilderTrait;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Ufo\RpcObject\RpcResponse;

use function array_merge_recursive;

/**
 * Immutable, like Symfony context builders: every with*() / removeParent() returns a new instance.
 * The builder is a shared service, so a mutated one carried the groups of one response
 * (e.g. "error") into the next one, and a DTO result was normalized as [].
 */
final class RpcResponseContextBuilder implements ContextBuilderInterface
{
    use ContextBuilderTrait;

    protected string $parent = '';

    public function __construct()
    {
        $this->context = [RpcErrorNormalizer::RPC_CONTEXT => true];
    }

    public function withContext(ContextBuilderInterface|array $context): static
    {
        if ($context instanceof ContextBuilderInterface) {
            $context = $context->toArray();
        }
        $instance = clone $this;
        $instance->context = array_merge_recursive($this->context, $context);
        return $instance;
    }

    /**
     * @param string|string[] $group
     * @return $this
     */
    public function withGroup(string|array $group): static
    {
        $instance = $this;
        foreach ((array) $group as $g) {
            $instance = $instance->withContext([AbstractNormalizer::GROUPS => [$g]]);
        }

        return $instance;
    }

    public function withResponseSignature(RpcResponse $response): static
    {
        $instance = $this->withGroup($response->getResponseSignature());
        $instance->parent = $response->getResponseSignature();
        return $instance;
    }

    public function removeParent(): static
    {
        $instance = clone $this;
        if (isset($instance->context[AbstractNormalizer::GROUPS])) {
            $instance->context[AbstractNormalizer::GROUPS] = array_filter(
                $instance->context[AbstractNormalizer::GROUPS],
                function ($item)  {
                    return $item !== $this->parent;
                }
            );
        }
        return $instance;
    }
}