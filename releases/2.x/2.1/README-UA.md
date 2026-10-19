[English](README.md)

# 2.1 — Обгортка async-запиту, DSN транспорту, обмеження як дані

Версії: 2.1.0–2.1.4. Перший реліз: 2024-05-16.

## Головне

- `RpcAsyncRequest` обгортає `RpcRequest` разом із токеном для асинхронної передачі.
- `RpcTransport` розбирає й збирає DSN транспорту; `#[Secret]` маскує облікові дані та секретні query-параметри в `toArray()`.
- `ConstraintObjectNormalizer` перетворює обмеження Symfony на `{class, context}`, тож `RPC\Assertions::toArray()` може їх описати (2.1.4).
- `RPC\Response` додає коротку назву DTO як `$dto` у формат відповіді (2.1.1).
- PHP `>=8.3`.

## Що нового

### `RpcAsyncRequest`

```php
use Ufo\RpcObject\RpcAsyncRequest;

$message = new RpcAsyncRequest($rpcRequest, token: $token);
$message->getRpcRequest();
$message->token;
```

### `RpcTransport` і `#[Secret]`

```php
use Ufo\RpcObject\RpcTransport;

$transport = RpcTransport::fromDsn('amqp://user:pass@rabbit:5672/%2f/rpc?access_token=abc');
(string) $transport;  // початковий DSN
$transport->toArray(); // user/pass → "{user}"/"{pass}", query → "access_token={access_token}"
```

`Secret::QUERY_PATTERN` знаходить query-ключі, що містять `secret`, `access`, `token` або `key`; `#[Secret(pattern: '...')]` приймає інший шаблон.

### Обмеження як дані

`Transformer::getDefault()` ставить `ConstraintObjectNormalizer` першим. Будь-який `Symfony\Component\Validator\Constraint` нормалізується в:

```php
['class' => Assert\Length::class, 'context' => [/* нормалізовані властивості обмеження */]]
```

`RPC\Assertions::toArray()` повертає список таких елементів; `RPC\AssertionsCollection::toArray()` — по кожному параметру. Сервер використовує це, щоб описати правила валідації в схемі API.

## Зміни та виправлення

- 2.1.0: `RpcValidator` скидає порушення між викликами, тож помилки попереднього методу більше не потрапляють у наступний.
- 2.1.1: `RealUrlValidator` перевіряє callback через `GET` замість `OPTIONS`. Обмеження протоколу в `CallbackRules` змінено з `['https']` на `['http']`.
- 2.1.2: `RpcResponse::$result` / `getResult()` типізовано як `string|int|float|bool|array|object|null` замість `mixed`.
- 2.1.3: ключі в `ConstraintsImposedException` містять шлях властивості порушення (`order[items]`), а не лише назву параметра.

## Примітки щодо оновлення

- Потрібен PHP 8.3 (2.1.0).
- Нащадки `RpcResponse`, що перевизначають `getResult(): mixed`, мають використати новий тип повернення (2.1.2).
