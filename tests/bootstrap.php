<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Fixtures/Models.php';

\Ufo\DTO\DTOTransformer::boot(\Ufo\DTO\Factory\DefaultDTOTransformerFactory::default()->create());
