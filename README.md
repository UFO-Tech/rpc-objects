# rpc-objects
Component for UFO-Tech RPC Library 🇺🇦

This package provides structured DTO handling for use in RPC-oriented systems, including:
* Attribute-based validation (Assertions)
* Recursive transformation into nested DTOs (DTO, ResultAsDTO)
* Conversion from/to arrays (IArrayConstructible, IArrayConvertible)
* Clean and extensible architecture compatible with Symfony Validator

### Installation

```bash
composer require ufo-tech/rpc-objects
```


### Example DTO with Assertions and DTO

* Use DTOTransformer::fromArray() to hydrate DTO from array
* Use toArray() method from ArrayConvertibleTrait for serialization
* DTO attributes like Assertions, DTO, and ResultAsDTO control behavior of transformation and validation

```php
namespace App\DTO;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Interfaces\IArrayConstructible;
use Ufo\DTO\Interfaces\IArrayConvertible;
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcObject\RPC;

class CarDTO implements IArrayConvertible, IArrayConstructible
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;

    public function __construct(
        #[RPC\ResultAsDTO(Engine::class)]
        #[RPC\DTO(Engine::class)]
        public Engine $engine,
    
        #[RPC\Assertions([new Assert\NotBlank(), new Assert\Length(min: 2, max: 50)])]
        public string $brand,
        
        #[RPC\Assertions([new Assert\NotBlank(), new Assert\Length(min: 1, max: 50)])]
        public string $model,

        #[RPC\Assertions([new Assert\Range(min: 1886, max: 2100)])]
        public int $year,

        #[RPC\Assertions([new Assert\Range(min: 0)])]
        public int $mileage = 0
    ) {}
}
```