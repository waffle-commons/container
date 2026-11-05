# Changelog — waffle-commons/container

All notable changes to this component are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Released in lockstep with the Waffle Commons umbrella tag.

## [0.1.0-beta7] — 2026-11

**Theme: one instance per service in AOT mode (CARRY-06).**

### Added
- `Container::memoise(string $id, Closure $build)` returns the instance the container holds for an id, building it with `$build` the first time, under the same circular-dependency guard as `get()`, which now builds through it. A compiled container (AOT, RFC-019) memoises the services it wires statically through it. Whichever path resolves an id first, the runtime and the compiled container then share one instance, and `reset()` covers it once. It memoises instances, not definitions, so it still works after `lock()`, but only for an id the container can resolve. An unknown id throws `NotFoundException`, as `get()` does, so `memoise()` cannot add a service after `lock()` (CARRY-06).

## [0.1.0-beta6] — 2026-09

**Theme: documentation.**

### Documentation
- The README now links into the central Diátaxis documentation tree (DOC-02).

## [0.1.0-beta5] — 2026-07-08

**Theme: sealed autowiring & complexity guard.**

### Changed
- `Autowire` is now `final` — sealing the reflection-based autowiring helper against subclassing and keeping the PSR-11 container surface closed (FINAL-04).
- Enabled the `cyclomatic-complexity` lint rule (`threshold = 50`) in [`mago.toml`](./mago.toml), ratcheting in a per-method complexity ceiling enforced on every CI run.

## [0.1.0-beta4] — 2026-06-13

**Theme: worker-mode diagnostics.**

### Added
- `Compliance\ComplianceScanner` + `Exception\ComplianceException` — a dev-only boot-time scan (new `Container` ctor flag `strictComplianceScan`) that halts the boot when a shared service holds mutable non-readonly state without implementing `ResettableInterface`, honouring `#[WorkerSafe]`, `readonly`, and virtual-hook exemptions (DIAG-02).

### Changed
- Worker-safety migration to igor-php 0.7 (`#[WorkerSafe]`).

## [0.1.0-beta3] — 2026-06-07

**Theme: identity federation & stateless persistence (ecosystem wave).**

### Changed
- `Container` memoizes built instances so the kernel reset loop reaches every `ResettableInterface` service between worker requests; lazy factory definitions remain unbuilt until first use (no eager side effects on reset).
- `Autowire` / `Container` internals refactored for dependency-injection clarity and readability.
- Lockstep version bump; `composer.lock` refreshed with the beta-3 dependency wave.

## [0.1.0-beta2.1] — 2026-05-30

### Changed
- Lockstep re-tag of `0.1.0-beta2` (umbrella housekeeping patch) — no source changes in this component.

## [0.1.0-beta2] — 2026-05-29

### Changed
- Lockstep version bump only. No behavioural changes since `0.1.0-beta1`.
- `composer.lock` refreshed to align with the ecosystem-wide dependency wave.

## [0.1.0-beta1]

See the umbrella [CHANGELOG](../CHANGELOG.md#010-beta1) for the full Beta-1 narrative — `get()` short-circuits on `has()` (no control-flow-by-exception); core services lock from override after boot; `ResettableInterface` for worker-mode reset.
