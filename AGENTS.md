# Repository guidance

## Project and layout

This is `ufo-tech/rpc-objects`, a reusable PHP component for the UFO-Tech RPC library, not a standalone application. It requires PHP >=8.3 and `ext-intl`; dependencies are declared in `composer.json`.

- `src/` maps to the PSR-4 namespace `Ufo\RpcObject\` and contains request, response, batch, async, error, and special-parameter objects.
- `src/RPC/` contains PHP attributes and metadata for parameters, DTOs, assertions, caching, locking, and API information.
- `src/Rules/` contains request and special-parameter rules; `src/Rules/Validator/` contains validator helpers and custom constraints.
- `src/Transformer/` contains serializer setup, normalizers, denormalizers, and DTO transformation helpers.
- `tests/` contains PHPUnit tests and fixtures; `tests/README.md` documents execution and known limitations.
- `docker-compose.yaml` and `Makefile` provide the development environment. `Makefile.md` describes its commands; check actual targets before using them.

## Setup and commands

- Install dependencies with `composer install`. Use `composer update` only when dependency updates are part of the task.
- For Docker development, create `.env` from `.env.dist` if absent and review its settings. `make up-d` starts the PHP container, and `make composer-install` installs dependencies inside it.
- The Makefile uses the `docker-compose` executable. On systems with only Compose v2, pass `DOCKER_COMPOSE='docker compose'` to make.
- `make exec` opens a shell in the PHP container; `make down` stops the environment.
- Compose currently uses `ufotech/php:8.4-fpm`. Keep library code compatible with the PHP 8.3 minimum, even when developing on 8.4.

## Code conventions and compatibility

Follow the surrounding PHP style: four-space indentation, matching namespaces and filenames, explicit types, and imports for dependencies. Avoid unrelated formatting changes or introducing repository-wide style rules.

Preserve public constructor signatures, named argument compatibility, serializer groups and serialized names, RPC field formats, and exception behavior unless the task calls for a breaking change. For serializer changes, inspect normalizer ordering and context handling in `Transformer::getDefault()` and the relevant normalizers. For validation changes, inspect both the rules and their callers.

Edit project code under `src/`, not installed dependencies in `vendor/`. Keep local environment settings and credentials out of commits. The repository ignores `vendor/`, `.env`, and `composer.lock`; do not force-add them as part of routine work.

## Validation

The test suite uses PHPUnit 11.5 with `phpunit.xml.dist`. Run `composer test` (or `make test` inside the configured Docker workflow). Run `composer test:coverage` with Xdebug to generate an HTML report under `build/coverage/`. No static-analysis configuration is currently checked in.

The HTTP integration test uses a temporary loopback server and requires `ext-curl` and `proc_open`. Run `composer test -- --exclude-group integration` when these are unavailable, and report the omitted check. Tests must not contact public callback URLs. Preserve fixture cleanup and restoration of global DTO transformer/URL-cache state.

- Check modified PHP files with `php -l path/to/changed.php` using PHP >=8.3.
- For a broader syntax check, run `find src -name '*.php' -print0 | xargs -0 -n1 php -l`.
- After changing dependency metadata, run `composer validate` and report any existing warnings separately from new issues.
- For behavior changes, add or update focused PHPUnit tests covering the affected public API, including relevant request/response serialization, validation failures, or DTO conversion cases. Run the affected tests and then the full suite. Syntax checks alone do not establish runtime correctness.
- Report what was checked and any checks blocked by the available runtime or dependencies. Documentation-only changes do not require runtime tests.

## Change scope

Keep changes focused on the requested task. Update documentation when public behavior or setup changes. Do not change the package version as part of unrelated work.

The `make commit-a` target amends commits, force-pushes, and can replace remote tags. It is a release/history operation, not a validation command; use it only when those actions are explicitly requested.
