[English](README.md)

# 2.8 — `#[RPC\Lock]`, об'єкт `$rpc` з `rayId`, DTO з валідацією та вкладеністю

Версії: 2.8.0–2.8.7. Перший реліз: 2025-03-13.

## Головне

- `#[RPC\Lock]` описує блокування методу (за потреби — для кожного значення параметра) поверх Symfony Lock.
- Спеціальні параметри переїхали в один об'єкт `$rpc` (`timeout`, `callback`, новий `rayId`), який описує `SpecialRpcParamsEnum`.
- `DTOTransformer` перевіряє `#[RPC\Assertions]` на властивостях DTO, гідратує вкладені DTO і колекції через `#[RPC\DTO]`, підтримує конструктори з `readonly`-властивостями та перейменування ключів.

## Що нового

### `#[RPC\Lock]`

`Ufo\RpcObject\RPC\Lock(?string $paramName = null, ?int $ttl = null)` з константами `T_SECOND` … `T_30_SECOND`. `acquire(RpcRequest, LockFactory)` створює lock з ключем `md5(метод + значення paramName)` і повертає його або `null`, якщо lock зайнятий. Коли його викликати, вирішує сервер (JsonRpcBundle 7.4).

```php
use Ufo\RpcObject\RPC;

#[RPC\Lock(paramName: 'orderId', ttl: RPC\Lock::T_10_SECOND)]
public function pay(string $orderId): bool { /* ... */ }
```

### Спеціальні параметри `$rpc` і `rayId`

Спеціальні параметри тепер читаються з одного ключа `$rpc` (`SpecialRpcParamsEnum::PREFIX`), а не з окремих ключів `$rpc.<name>`. `SpecialRpcParamsEnum` містить випадки `TIMEOUT` (`timeout`), `CALLBACK` (`callback`) і `PARENT_REQUEST` (`rayId`), константу `DEFAULT_TIMEOUT = 10` і після валідації будує `SpecialRpcParams` через `fromArray()`. `SpecialRpcParams::getRayId()` повертає id батьківського запиту.

```json
{"jsonrpc": "2.0", "id": 1, "method": "Report.build",
 "params": {"id": 7, "$rpc": {"timeout": 30, "callback": "https://example.com/hook", "rayId": "parent-1"}}}
```

### DTO з валідацією, вкладеністю та конструкторами

- `#[RPC\Assertions]` можна ставити на властивості; `DTOTransformer::fromArray()` перевіряє значення і кидає `RpcBadParamException` (`<властивість><порушення>`).
- Новий `#[RPC\DTO(dtoFQCN, collection)]` (і `ResultAsDTO`, який тепер його розширює) на властивості гідратує вкладений DTO або список таких. Атрибути обробляє `DTOAttributesEnum`; невідомі атрибути ігноруються.
- `fromArray(string $classFQCN, array $data, array $renameKey = [])` зіставляє назви властивостей з іншими ключами вхідних даних.
- Якщо публічний конструктор заповнює `readonly`-властивості, об'єкт створюється через конструктор (2.8.7).
- `toArray()` рекурсивно перетворює вкладені об'єкти й масиви.

```php
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcObject\DTO\DTOTransformer;
use Ufo\RpcObject\RPC;

final class CarDTO
{
    #[RPC\Assertions([new Assert\NotBlank(), new Assert\Length(min: 2, max: 50)])]
    public string $brand;

    #[RPC\DTO(EngineDTO::class)]
    public EngineDTO $engine;

    #[RPC\DTO(WheelDTO::class, collection: true)]
    public array $wheels = [];
}

$car = DTOTransformer::fromArray(CarDTO::class, $data, renameKey: ['brand' => 'make']);
```

У 2.8 `#[RPC\DTO]` можна ставити лише на методи й властивості; на promoted-параметри конструктора — з 2.9.

## Зміни та виправлення

- 2.8.0: `RpcRequest::getAllParams()` повертає спеціальні параметри в `params['$rpc']`. Типізовані константи класів (`const string …`) по всьому пакету. Новий інтерфейс `IRpcSpecialParamHandler`. Запасна назва методу запиту — `noname` замість випадкового id.
- 2.8.1–2.8.2: рекурсивний `toArray()`; додано `#[RPC\DTO]`; `TypeHintResolver::normalizeArray()`.
- 2.8.3–2.8.5: `DTOAttributesEnum`, колекції в `#[RPC\DTO]`, невідомі атрибути пропускаються.
- 2.8.6: потрібні `symfony/serializer ^7.2` і `symfony/validator ^7.2`; сигнатури нормалізаторів і валідатора оновлено під Symfony 7.2 (nullable-типи).
- 2.8.7: гідратація через конструктор для DTO з `readonly`-властивостями; відсутній ключ отримує значення за замовчуванням або кидає `Missing required key`, якщо його немає.

## Примітки щодо оновлення

- **Змінився формат спеціальних параметрів.** Надсилайте `"$rpc": {"timeout": …, "callback": …}` замість `"$rpc.timeout"` / `"$rpc.callback"`. `SpecialRpcParams::PREFIX`, `DEFAULT_TIMEOUT` і `SpecialRpcParams::fromArray()` видалено: використовуйте `SpecialRpcParamsEnum::PREFIX`, `::DEFAULT_TIMEOUT`, `::fromArray()`. `SpecialParamsRules::PARAMS` видалено.
- `#[RPC\Lock]` використовує `symfony/lock`, якого немає в `require` цього пакета; встановіть його, якщо користуєтеся атрибутом.
- З 2.8.6 потрібні Symfony Serializer і Validator 7.2+.
