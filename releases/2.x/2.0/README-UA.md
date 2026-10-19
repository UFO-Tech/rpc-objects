[English](README.md)

# 2.0 — `#[RPC\Cache]`, валідатор параметрів, Symfony 7

Версії: 2.0.0, 2.0.1. Перший реліз: 2024-05-10.

## Головне

- `#[RPC\Cache]` позначає відповідь методу як кешовану; `RpcResponse` несе налаштування кешу, `RpcCachedResponse` обгортає закешований результат.
- `Rules\Validator\RpcValidator` перевіряє аргументи методу за `#[RPC\Assertions]` і кидає `ConstraintsImposedException` з помилками по кожному параметру.
- Атрибути стали `final readonly` з публічними властивостями; `RPC\Assertions` і `RPC\AssertionsCollection` експортуються через `toArray()`.
- Метадані серіалізатора читаються з атрибутів PHP; `doctrine/annotations` більше не потрібен. `symfony/validator` `^7.0`.

## Що нового

### `#[RPC\Cache]`

```php
use Ufo\RpcObject\RPC;

#[RPC\Cache(lifetimeSecond: RPC\Cache::T_10_MINUTES, environments: [RPC\Cache::ENV_PROD])]
public function list(): array { /* ... */ }
```

За замовчуванням: `lifetimeSecond: Cache::T_MINUTE`, `environments: [Cache::ENV_PROD]`. Константи — від `T_MINUTE` до `T_YEAR`. `RpcResponse` отримав новий аргумент конструктора `cache` і `getCacheInfo(): ?Cache`. Саме кешування виконує сервер.

### `RpcValidator`

```php
use Ufo\RpcObject\Rules\Validator\ConstraintsImposedException;
use Ufo\RpcObject\Rules\Validator\RpcValidator;

$validator = new RpcValidator($symfonyValidator);
try {
    $validator->validateMethodParams($procedure, 'create', $params);
} catch (ConstraintsImposedException $e) {
    $e->getConstraintsImposed(); // ["name" => ["This value should not be blank."]]
}
```

`ConstraintsImposedException` наслідує `RpcBadParamException`.

### `RPC\Response` з nullable-властивостями

Коли `RPC\Response` будує формат з DTO, nullable-властивості отримують префікс `?` (`?string`).

## Примітки щодо оновлення

- `RPC\Info` втратив `getAlias()` / `getConcat()`: читайте публічні властивості `$alias` / `$concat`.
- `RPC\Assertions::$assertions` не має значення за замовчуванням; `#[RPC\Assertions]` без аргументів більше не валідний.
- `RPC\Info`, `RPC\Assertions`, `RPC\Cache` — `final`.
- `symfony/validator` `^7.0` (з 2.0.1; у межах 2.0.0 обмеження змінювалося з `*` на `^6.4`); `symfony/serializer`, `symfony/http-client`, `symfony/property-access` більше не прив'язані до `6.*`.
- Анотації серіалізатора (`@Groups` у docblock) більше не читаються; використовуйте атрибути PHP.
