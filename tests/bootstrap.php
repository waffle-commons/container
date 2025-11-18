<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT', realpath(path: dirname(path: __DIR__)));
const APP_CONFIG = 'temp_config';

require_once __DIR__ . '/src/AbstractTestCase.php';

// required test helpers, so we include them manually.
require_once __DIR__ . '/src/Helper/ServiceWithoutDependencies.php';
require_once __DIR__ . '/src/Helper/ServiceWithDependencies.php';
require_once __DIR__ . '/src/Helper/ServiceWithDefaultParam.php';
require_once __DIR__ . '/src/Helper/ServiceWithNullableParam.php';
require_once __DIR__ . '/src/Helper/ServiceWithUnresolvableParam.php';
