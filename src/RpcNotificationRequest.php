<?php

namespace Ufo\RpcObject;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Ufo\RpcObject\RPC\Info;

use function uniqid;

class RpcNotificationRequest extends RpcRequest
{
    const string S_GROUP = self::class;

    #[Groups([self::S_GROUP])]
    protected string $method;
    #[Groups([self::S_GROUP])]
    protected string $version;

    public function __construct(
        string $method,
        array $params = [],
        string $version = self::DEFAULT_VERSION,
        ?string $rawJson = null,
        string $apiVersion = Info::DEFAULT_VERSION
    )
    {
        parent::__construct(static::generateId(), $method, $params, $version, $rawJson, $apiVersion);
    }

    public function toArray(?NormalizerInterface $normalizer = null, array $context = []): array
    {
        $array = parent::toArray($normalizer, $context);
        unset($array['id']);
        return $array;
    }

    #[Groups([self::S_GROUP])]
    #[SerializedName('params')]
    public function getAllParams(): array
    {
        return parent::getAllParams();
    }

}
