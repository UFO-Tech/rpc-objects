# rpc-objects
Component for UFO-Tech RPC Library

### Example DTO with Assertions
```php
namespace App\DTO;

use Ufo\RpcObject\DTO\ArrayConstructibleTrait;
use Ufo\RpcObject\DTO\ArrayConvertibleTrait;
use Ufo\RpcObject\DTO\IArrayConstructible;
use Ufo\RpcObject\DTO\IArrayConvertible;
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcObject\RPC;

class CarDTO implements IArrayConvertible, IArrayConstructible
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;

    public function __construct(
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