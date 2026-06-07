[![Discord](https://img.shields.io/discord/755288001592033391?logo=discord)](https://discord.gg/eKgywnfXr2)
[![PHP Version Require](http://poser.pugx.org/waffle-commons/container/require/php)](https://packagist.org/packages/waffle-commons/container)
[![PHP CI](https://github.com/waffle-commons/container/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/container/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/container/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/container)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/container/v)](https://packagist.org/packages/waffle-commons/container)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/container/v/unstable)](https://packagist.org/packages/waffle-commons/container)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/container.svg)](https://packagist.org/packages/waffle-commons/container)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/container)](https://github.com/waffle-commons/container/blob/main/LICENSE.md)

Waffle Container Component
==========================

> **Release:** `0.1.0-beta3` &nbsp;|&nbsp; [`CHANGELOG.md`](./CHANGELOG.md)
> **PSR Compliance:** PSR-11 (`Psr\Container\ContainerInterface`)

A strict PSR-11 service container with reflection-based autowiring, circular-dependency detection, and worker-mode resettability. Core services (the PSR-11 `ContainerInterface` itself) are locked from override after registration.

## 📦 Installation

```bash
composer require waffle-commons/container
```

## 🧱 Surface

| Class | Role |
| :--- | :--- |
| `Waffle\Commons\Container\Container` | The container. Implements `Waffle\Commons\Contracts\Container\ContainerInterface` (PSR-11 + `ResettableInterface`). |
| `Waffle\Commons\Container\Autowire` | Reflection-based autowiring helper used by `Container::build()` to resolve constructor parameters. |
| `Waffle\Commons\Container\Exception\ContainerException` | Thrown for retrieval / resolution failures. |
| `Waffle\Commons\Container\Exception\NotFoundException` | Thrown when `get($id)` cannot resolve the identifier. |

## 🚀 Usage

```php
use Waffle\Commons\Container\Container;

$container = new Container([
    // Direct instance
    LoggerInterface::class => new StreamLogger(),

    // Class string — autowired via reflection on first get()
    UserService::class => UserService::class,

    // Factory closure
    'app.config' => static fn() => new Config(__DIR__ . '/config', 'prod'),
]);

$logger = $container->get(LoggerInterface::class);
$exists = $container->has(UserService::class);

$container->set('db.cache', new ArrayCache());
```

The exact public signature, verbatim from `Waffle\Commons\Contracts\Container\ContainerInterface`:

```php
public function get(string $id): mixed;
public function has(string $id): bool;
public function set(string $id, object|callable|string $concrete): void;
public function reset(): void; // from ResettableInterface
```

## 🔁 Worker-mode reset

`Container` implements `ResettableInterface`. After each request, the kernel calls `reset()` so the container drops its instance cache while keeping the registered definitions, preventing user-context leaks across FrankenPHP worker requests.

## 🛡️ Locked core services

The constant `Container::CORE_SERVICES` lists identifiers that **must not** be redefined once registered. The PSR-11 `ContainerInterface` itself is in that list — any attempt to override it after the container is built throws `ContainerException`.

## 🔄 Circular-dependency detection

`Container::get($id)` tracks the resolution stack in `$resolving` and throws `ContainerException` if a cycle is detected before infinite recursion can occur.

## 🐘 PHP 8.5 features used

- `final class Container` — no subclassing.
- Typed properties throughout.
- Typed constants for service registries: `private const CORE_SERVICES = [...];`.
- `#[\Override]` on every method that overrides PSR-11.

## 🧭 Architectural boundary (`mago guard`)

An active dependency **perimeter** is enforced on every CI run by `vendor/bin/mago guard` (bundled into `composer mago`; zero baselines). The rules live in [`mago.toml`](./mago.toml) under `[guard.perimeter]` — a forbidden `use` statement fails the build, not a reviewer.

Production code under `Waffle\Commons\Container` may depend **only** on:

- `Waffle\Commons\Container\**` — itself
- `Waffle\Commons\Contracts\**` — the shared contracts package, the **only** Waffle dependency permitted
- `Psr\**` — PSR interfaces (PSR-11)
- `@global` + `Psl\**` — PHP core and the PHP Standard Library

Test code under `WaffleTests\Commons\Container` is unrestricted (`@all`). Structural rules are guarded too: interfaces must be named `*Interface`, `Exception\**` classes must end in `*Exception`, and any `Enum\**` namespace may hold only `enum` declarations.

Contract-first, component-agnostic by construction: components compose through `waffle-commons/contracts`, never directly through one another.

## 🧪 Testing

```bash
docker exec -w /waffle-commons/container waffle-dev composer tests
```

## 📄 License

MIT — see [LICENSE.md](./LICENSE.md).
