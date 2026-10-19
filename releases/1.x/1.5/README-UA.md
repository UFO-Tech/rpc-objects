[English](README.md)

# 1.5 — Атрибути `RPC\*`, `#[RPC\Assertions]`, дати у відповідях

Версії: 1.5. Перший реліз: 2024-04-19.

## Головне

- Атрибути перенесено в неймспейс `Ufo\RpcObject\RPC`: `RPC\Info` (аліас сервісу), `RPC\Response` (колишній `RpcInfo`), `RPC\Assertions`.
- `#[RPC\Assertions([...])]` прив'язує обмеження Symfony до параметра методу; `RPC\AssertionsCollection` збирає їх по параметрах.
- Значення `DateTimeInterface` серіалізатор за замовчуванням нормалізує в рядки.

## Що нового

### `RPC\Info`

Атрибут класу з `alias` і `concat` (за замовчуванням `.`). Сервер будує з нього назви методів виду `<alias><concat><method>`.

```php
use Ufo\RpcObject\RPC;

#[RPC\Info(alias: 'User')]
final class UserProcedure
{
    public function get(int $id): array { /* ... */ } // "User.get"
}
```

### `RPC\Response`

`Ufo\RpcObject\RpcInfo` перейменовано на `Ufo\RpcObject\RPC\Response` (ціль — метод). Аргументи ті самі: `responseFormat`, `dto`, `collection`.

### `RPC\Assertions`

```php
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcObject\RPC;

public function create(
    #[RPC\Assertions([new Assert\NotBlank(), new Assert\Length(max: 64)])]
    string $name,
): int { /* ... */ }
```

`RPC\Assertions` — `readonly`-клас з публічним масивом `$assertions`. `RPC\AssertionsCollection::addAssertions($key, $assertions)` / `getAssertionsCollection()` групують їх по параметрах. Саму валідацію виконує сервер.

### Дати

`Transformer::getDefault()` тепер реєструє `DateTimeNormalizer` перед `ObjectNormalizer`, а `ObjectNormalizer` визначає типи властивостей через `ReflectionExtractor`.

## Примітки щодо оновлення

- Потрібен PHP `>=8.2` (був `>=8.1`).
- Замініть `Ufo\RpcObject\RpcInfo` на `Ufo\RpcObject\RPC\Response`.
