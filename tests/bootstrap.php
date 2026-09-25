<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Fixtures/Models.php';

use Ufo\DTO\TransformerBootstrapper;

// DTOTransformer is a booted singleton: outside a Symfony bundle nothing calls
// DTOTransformerBundleBootTrait, so every denormalization would fail with
// NotInitializeException. Boot it here the same way the bundle does.
TransformerBootstrapper::boot();
