# Tests

The suite uses PHPUnit 11.5 on PHP >=8.3. Install development dependencies with `composer install`, then run:

```sh
composer test
composer test -- --filter RpcBatchRequestTest
composer test -- --exclude-group integration
composer test:coverage
```

`composer test:coverage` enables Xdebug coverage and writes an HTML report to `build/coverage/index.html`. It requires Xdebug; with PCOV, invoke PHPUnit directly with the appropriate coverage settings. Coverage measures execution, not proof that every input or branch is correct.

The Docker image configured by this repository includes PHP 8.4, Composer, curl, and Xdebug. A standalone run does not require starting the Compose stack:

```sh
docker run --rm -e XDEBUG_MODE=off -v "$PWD:/app" -w /app ufotech/php:8.4-fpm composer install
docker run --rm --network none -e XDEBUG_MODE=off -v "$PWD:/app" -w /app ufotech/php:8.4-fpm composer test
docker run --rm --network none -e XDEBUG_MODE=off -v "$PWD:/app" -w /app ufotech/php:8.4-fpm composer test:coverage
```

If an existing ignored lock file predates the dev dependencies or specifies DTO transformer 2.x, Composer will reject it as outdated. Refresh it with `composer update --with-all-dependencies`. Do not commit the ignored lock file or generated coverage artifacts.

## Scope

- Request parsing, validation, serialization, special parameters, dependency substitution, error classification, and response lifecycle.
- Factory selection by presence of `id`, notification serialization, zero IDs, invalid-message state, inherited factory methods, and mixed batches with notifications.
- Batch queues with multiple parents, missing parents/fields, failed parent results, and keyed/unkeyed results.
- Response envelopes, scalar/object/array results, cached and async wrappers, and exception conversion.
- DSN parsing, reconstruction, and secret masking.
- Attribute metadata, parameter masks, reflection-based assertions, cache relations, and lock acquisition.
- Timeout boundaries, method parameter constraints, callback validation, and validation state reset.
- Enum, DTO, constraint, associative-array, and error normalization; serializer contexts and response creation.
- DTO transformer boot with container overrides, invalid services, cache priority, and repeat initialization.

The HTTP integration test starts a short-lived PHP server on a dynamically selected loopback port, exercises OPTIONS/POST and connection failures, then cleans it up. It requires `ext-curl` and `proc_open`; otherwise PHPUnit reports a skipped test. No public service is contacted. Other callback tests seed and restore the validator's static URL cache. Tests that reset the DTO transformer restore it afterward.

## Current behavior and limitations

These tests preserve the current library behavior; production code was not changed to obtain passing results.

- `CallbackObject::validate()` currently retains unreachable callback targets instead of throwing. Its characterization test names that behavior explicitly.
- `RealUrlValidator` looks for `Allow`, while Symfony returns lower-case header keys. The HTTP test currently observes a POST fallback even after an OPTIONS response with `Allow: POST, OPTIONS`. It checks this existing behavior, not an endorsement of it.
- DTO and DTOCollection expose parsed-format getters, but this repository has no public path that populates the protected format. The unparsed exception paths are tested; the successful return statements remain uncovered rather than setting internal state solely to raise coverage.
- Coverage is limited to this package's `src/`; it excludes dependency implementations. Passing tests do not establish all possible malformed-input behavior or compatibility with every allowed dependency version.
