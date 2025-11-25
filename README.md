[![PHP Version Require](http://poser.pugx.org/waffle-commons/container/require/php)](https://packagist.org/packages/waffle-commons/container)
[![PHP CI](https://github.com/waffle-commons/container/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/container/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/container/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/container)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/container/v)](https://packagist.org/packages/waffle-commons/container)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/container/v/unstable)](https://packagist.org/packages/waffle-commons/container)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/container.svg)](https://packagist.org/packages/waffle-commons/container)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/container)](https://github.com/waffle-commons/container/blob/main/LICENSE.md)

Waffle Commons - Container Component
====================================

A lightweight, strict, and fully compliant PSR-11 Dependency Injection Container implementation for the Waffle Framework.

This component provides a robust foundation for managing dependencies with powerful features like autowiring and circular dependency detection, while adhering strictly to PHP standards.

Features
--------

*   **PSR-11 Compliance:** Fully implements `Psr\Container\ContainerInterface`.

*   **Autowiring:** Automatically resolves class dependencies (constructor injection) using PHP Reflection.

*   **Recursion Support:** Resolves deep dependency trees automatically.

*   **Circular** Dependency **Detection:** Detects and throws exceptions for circular references (A -> B -> A) to prevent infinite loops.

*   **Zero Configuration:** Works out-of-the-box for most classes without manual definitions.

*   **Strict Typing:** Built with PHP 8.4+ strict types for reliability.


Installation
------------

You can install the package via Composer:

```shell
composer require waffle-commons/container
```

Usage
-----

### 1\. Basic Usage (Manual Registration)

You can manually register services or values using the `set()` method.

```php
use Waffle\Commons\Container\Container;

$container = new Container();

// Register a simple value
$container->set('api_key', 'secret-123');

// Register a closure (lazy loading)
$container->set('database', function () {
    return new DatabaseConnection('localhost', 'root', 'password');
});

// Retrieve services
$apiKey = $container->get('api_key');
$db = $container->get('database');
```

### 2\. Autowiring (Automatic Resolution)

The most powerful feature is autowiring. You don't need to register classes if they can be instantiated automatically (i.e., their dependencies are available).

**Example Classes:**

```php
class Logger {
    public function log(string $msg) { /* ... */ }
}

class UserService {
    public function __construct(
        private Logger $logger
    ) {}
}
```

**Resolution:**

```php
use Waffle\Commons\Container\Container;

$container = new Container();

// No need to call set()!
// The container sees UserService needs Logger, instantiates Logger, and injects it.
$userService = $container->get(UserService::class);
```

### 3\. Advanced Autowiring

The container handles complex cases:

*   **Default Values:** If a constructor parameter has a default value (e.g., `int $limit = 10`), it is used if no other value is found.

*   **Nullable Types:** If a dependency is not found but the parameter is nullable (e.g., `?Logger $logger`), `null` is injected.

*   **Recursion:** It can resolve chains like Controller -> Service -> Repository -> Database -> Config.


### Exceptions

The component throws PSR-11 compliant exceptions:

*   `Waffle\Commons\Container\Exception\NotFoundException`: Thrown when a requested identifier is not found and cannot be autowired.

*   `Waffle\Commons\Container\Exception\ContainerException`: Thrown for general errors, such as:

    *   Circular dependencies.

    *   Uninstantiable classes (abstract classes, interfaces without implementation).

    *   Unresolvable parameters (primitive types without default values).


Testing
-------

This component is fully tested with PHPUnit.

```shell
composer tests
```

Contributing
------------

Contributions are welcome! Please refer to [CONTRIBUTING.md](./CONTRIBUTING.md) for details.

License
-------

This project is licensed under the MIT License. See the [LICENSE.md](./LICENSE.md) file for details.
