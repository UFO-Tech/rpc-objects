<?php

namespace Ufo\RpcObject;

use function count;

final readonly class RpcTransport implements \Stringable
{
    public function __construct(
        public ?string $scheme,
        #[Secret]
        public ?string $user,
        #[Secret]
        public ?string $pass,
        public ?string $host,
        public ?int $port,
        public ?string $path,
        #[Secret]
        public ?string $query,
        public ?string $fragment
    ) {}

    public static function fromArray(array $parts): self
    {
        return new self(
            $parts['scheme'] ?? null,
            $parts['user'] ?? null,
            $parts['pass'] ?? null,
            $parts['host'] ?? null,
            isset($parts['port']) ? (int)$parts['port'] : null,
            $parts['path'] ?? null,
            $parts['query'] ?? null,
            $parts['fragment'] ?? null
        );
    }

    public static function fromDsn(string $dsn): self
    {
        return self::fromArray(parse_url($dsn));
    }

    public function toArray(): array
    {
        $ref = new \ReflectionObject($this);
        $array = [];
        foreach ($ref->getProperties() as $property) {
            if (!is_null($val = $property->getValue($this))) {
                if (count($secretAttr = $property->getAttributes(Secret::class)) > 0) {
                    $val = '{'.$property->getName().'}';
                    if ($property->getName() === 'query') {
                        /**
                         * @var Secret $secret
                         */
                        $secret = $secretAttr[0]->newInstance();
                        $val = $secret->replace($property->getValue($this));
                    }
                }
                $array[$property->getName()] = $val;
            }
        }

        return $array;
    }

    public function __toString(): string
    {
        $url = '';
        if ($this->scheme) {
            $url .= $this->scheme.'://';
        }
        if ($this->user) {
            $url .= $this->user;
            if ($this->pass) {
                $url .= ':'.$this->pass;
            }
            $url .= '@';
        }
        if ($this->host) {
            $url .= $this->host;
        }
        if ($this->port) {
            $url .= ':'.$this->port;
        }
        if ($this->path) {
            $url .= $this->path;
        }
        if ($this->query) {
            $url .= '?'.$this->query;
        }
        if ($this->fragment) {
            $url .= '#'.$this->fragment;
        }

        return $url;
    }

}