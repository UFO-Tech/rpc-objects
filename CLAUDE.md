# CLAUDE.md

Настанови для Claude Code при роботі в цьому репозиторії.
Детальні правила процесу описані в [AGENTS.md](AGENTS.md) — цей файл їх не дублює, а доповнює описом архітектури та локального оточення.

## Що це за проєкт

`ufo-tech/rpc-objects` — перевикористовувана PHP-бібліотека (composer `type: component`, не застосунок) для UFO-Tech JSON-RPC стеку. Вона визначає об'єктну модель JSON-RPC 2.0: запити, нотифікації, батчі, відповіді, помилки, спеціальні параметри, а також шар атрибутів і серіалізації для DTO.

- PSR-4: `Ufo\RpcObject\` → `src/`, `Ufo\RpcObject\Tests\` → `tests/`.
- PHP >= 8.3 (контейнер розробки — 8.4; код має лишатися сумісним з 8.3), потрібен `ext-intl`.
- Ключові залежності: `ufo-tech/rpc-exceptions` (ієрархія виключень), `ufo-tech/dto-transformer` (^3, DTO ↔ array), `symfony/serializer`, `symfony/validator`, `symfony/http-client`.
- Публічна документація: https://docs.ufo-tech.space/bin/view/docs/JsonRpcBundle/
- Поточна лінія версій — 3.7.x (git-теги `3.x.y`).

Бібліотеку споживає RPC-сервер/клієнт (JsonRpcBundle) — тут немає HTTP-ядра й роутингу. Відправлення відповідей, обробка черг і виконання процедур лишаються відповідальністю застосунку.

## Архітектура

### Запити
- `RpcRequest` — базовий об'єкт запиту (`id`, `method`, `params`, `jsonrpc`, `apiVersion`). Конструктор одразу валідує поля (`RequestRules`) і аналізує параметри. Некоректний запит не кидає виключення назовні, а зберігає його в `hasError()`/`getError()` — це свідомий контракт.
- `Factory\RpcRequestFactory` — єдина точка розбору вхідних повідомлень (`fromArray`/`fromJson`). Вибирає клас: відсутній `id` → `RpcNotificationRequest`, інакше `RpcRequest`. `RpcRequest::fromArray()/fromJson()` делегують сюди.
- `RpcNotificationRequest` — нотифікація: має внутрішній згенерований `id` для черг, але `toArray()`/`refreshRawJson()` не віддають поле `id`. Придушення відповіді робиться через `instanceof RpcNotificationRequest`.
- `RpcBatchRequest` — черга батчу з залежностями: параметр виду `@FROM:<requestId>(<field>)` створює `RpcRequestRequire`, запит чекає в `waitForOtherResponse`, поки батьківська відповідь не потрапить у `addResponse()`.
- `RpcAsyncRequest` — обгортка запиту для асинхронної передачі (токен + meta).

### Спеціальні параметри
- Префікс `$rpc` у `params` (`SpecialRpcParamsEnum::PREFIX`): `timeout`, `callback`, `rayId`, `ignoreCache`.
- `Rules\ParamsSplitter` відділяє їх від звичайних параметрів, `SpecialRpcParams` + `CallbackObject` їх представляють, `Rules\SpecialParamsRules`/`CallbackRules` валідують.
- Наявність `callback` робить запит асинхронним (`RpcRequest::isAsync()`).

### Відповіді
- `RpcResponse` (+ `RpcCachedResponse`) — конверт відповіді; групи серіалізації (`result`, `error`, `list`, `detail`, `group`…) керують видимістю полів.
- `RpcError::fromThrowable()` конвертує виключення в RPC-помилку; `Transformer\RpcErrorNormalizer` її нормалізує.
- `Transformer\RpcResponseContextBuilder` збирає контекст серіалізації (групи, підпис відповіді), `Transformer\ResponseCreator::fromJson()` відновлює відповідь з JSON.

### Атрибути (`src/RPC/`)
`Info` (alias/версія API, `removedMethods`), `Param`, `Assertions`/`AssertionsCollection`, `DTO`/`DTOCollection`, `Cache`/`CacheRelation`, `Lock`, `IgnoreApi`. Це метадані, які читає сервер для генерації схеми API та поведінки процедур. Версія API (`v1`, `v2`…) протягується через `apiVersion` у запитах і фабриці.

### Серіалізація (`src/Transformer/`)
- `Transformer::getDefault()` — синглтон із зібраним Symfony Serializer. **Порядок нормалізаторів має значення**: `EnumNormalizer`, `DateTimeNormalizer`, `UidNormalizer`, `DtoObjectNormalizer`, `ConstraintObjectNormalizer`, `AssociativeArrayDenormalizer`, `ArrayDenormalizer`. Будь-яка зміна тут впливає на весь формат RPC.
- `DTOTransformerBundleBootTrait` ініціалізує `DTOTransformer`/`ServiceTransformer` з контейнера, підбираючи кеш-сервіс за пріоритетом зі списку `CACHE_SERVICES`.
- `AttributeHelper`/`AttributeTransformer` — читання атрибутів через рефлексію.

### Валідація (`src/Rules/`)
`RequestRules` (id/version/method/params), `Validator` (обгортка над `DtoValidator`, `throw()` кидає RPC-виключення), `AssertRealUrl` + `RealUrlValidator` (перевірка досяжності callback-URL через HTTP-клієнт зі статичним кешем URL).

### Інше
`RpcTransport` — розбір/збирання DSN (`sync`/`rpc_async` схеми); `Secret` — маскування токенів у query-рядках.

## Локальне оточення та команди

**На цій машині немає локальних `php` і `composer`** — є лише Docker. Тести запускаються в контейнері:

```bash
docker run --rm --network none -e XDEBUG_MODE=off -v "$PWD:/app" -w /app ufotech/php:8.4-fpm composer test
```

```bash
docker run --rm --network none -e XDEBUG_MODE=off -v "$PWD:/app" -w /app ufotech/php:8.4-fpm composer test -- --exclude-group integration
```

Покриття (`composer test:coverage`, Xdebug) пише HTML у `build/coverage/`. Група `integration` піднімає тимчасовий PHP-сервер на loopback і потребує `ext-curl` + `proc_open`, тому з `--network none` її треба виключати.

Compose-стек: `make up-d`, `make composer-install`, `make exec`, `make down` (Makefile використовує `docker-compose`; для Compose v2 — `DOCKER_COMPOSE='docker compose'`). `.env` створюється з `.env.dist`.

`make commit-a` — реліз-операція (amend + force-push + перестановка тегів). Ніколи не використовувати як перевірку; лише за прямим проханням.

## Конвенції

- Відступ 4 пробіли, явні типи, імпорти класів і функцій (`use function ...`), неймспейс = шлях файлу.
- Коментарі в коді — українською, публічна документація (README, AGENTS.md, tests/README.md) — англійською.
- Зберігати зворотну сумісність: сигнатури конструкторів (іменовані аргументи), групи серіалізації та `SerializedName`, формати RPC-полів, поведінку виключень.
- Правити тільки `src/`; `vendor/`, `.env`, `composer.lock`, `build/` — в `.gitignore`, не комітити.
- Для змін поведінки — додавати/оновлювати тести в `tests/` і запускати повний набір.
