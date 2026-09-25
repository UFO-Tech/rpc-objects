<?php

namespace Ufo\RpcObject\Tests\Fixtures;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\DTO\Interfaces\IArrayConvertible;
use Ufo\RpcObject\RPC\Assertions;
use Ufo\RpcObject\RPC;

enum Status: string { case Active = 'active'; case Inactive = 'inactive'; }
enum Priority: int { case Low = 1; case High = 2; }
enum Direction { case North; case South; }

class Person
{
    public function __construct(
        #[Groups(['detail'])]
        public string $name,
        #[Groups(['detail'])]
        public int $age = 0,
    ) {}
}

class Convertible implements IArrayConvertible
{
    public function toArray(bool $publicOnly = true): array { return ['custom' => true]; }
}

class Procedure
{
    public function save(
        #[Assertions([new Assert\NotBlank(), new Assert\Length(min: 3)])]
        string $name,
        #[Assertions([new Assert\Positive()])]
        int $age,
        string $unchecked = '',
    ): void {}

    public function aliased(
        #[RPC\Assertions([new Assert\NotBlank()])]
        string $name,
    ): void {}
}
