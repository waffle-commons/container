[![PHP Version Require](http://poser.pugx.org/waffle-commons/container/require/php)](https://packagist.org/packages/waffle-commons/container)
[![PHP CI](https://github.com/waffle-commons/container/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/container/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/container/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/container)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/container/v)](https://packagist.org/packages/waffle-commons/container)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/container/v/unstable)](https://packagist.org/packages/waffle-commons/container)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/container.svg)](https://packagist.org/packages/waffle-commons/container)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/container)](https://github.com/waffle-commons/container/blob/main/LICENSE.md)

Waffle Container Component
==========================

A lightweight, strict, and fully compliant PSR-11 Dependency Injection Container implementation for the Waffle Framework.

## 📦 Installation

```bash
composer require waffle-commons/container
```

## 🚀 Usage

### Basic Usage

```php
use Waffle\Commons\Container\Container;

$container = new Container();

// Register a simple value
$container->set('api_key', 'secret-123');

// Register a closure (lazy loading)
$container->set('database', function () {
    return new DatabaseConnection('localhost', 'root', 'password');
});

// Register a service
$container->set(MyService::class, new MyService());

// Retrieve services
$apiKey = $container->get('api_key');
$db = $container->get('database');
$service = $container->get(MyService::class);
```

### Autowiring

The container supports automatic dependency resolution (autowiring) for concrete classes.

```php
class Database { ... }

class UserRepository {
    public function __construct(private Database $db) {}
}

// Automatically resolves Database dependency
$repo = $container->get(UserRepository::class);
```

### Advanced Autowiring

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


### PSR-11 Compliance

This container implements `Psr\Container\ContainerInterface`, making it compatible with any library that consumes PSR-11 containers.

This component provides a robust foundation for managing dependencies with powerful features like autowiring and circular dependency detection, while adhering strictly to PHP standards.

Features
--------

*   **PSR-11 Compliance:** Fully implements `Psr\Container\ContainerInterface`.

*   **Autowiring:** Automatically resolves dependencies for classes.
*   **Interface Binding:** Bind interfaces to concrete implementations.
*   **Factory Support:** Register closures as factories for complex services.
*   **Circular Dependency Detection:** Throws an exception if a circular dependency is detected.
*   **PSR-11 Compliant:** Fully compatible with the PHP Standard Recommendation.

Testing
-------

To run the tests, use the following command:

```bash
composer tests
```

Contributing
------------

Contributions are welcome! Please refer to [CONTRIBUTING.md](./CONTRIBUTING.md) for details.

License
-------

This project is licensed under the MIT License. See the [LICENSE.md](./LICENSE.md) file for details.
