# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2026-07-12

### Added

- Open-source contribution docs, GitHub templates, and CI unit-test workflow
- Magento dual-license files (`COPYING.txt`, `LICENSE_AFL.txt`) and source copyright headers
- Packagist `homepage` / `support` metadata and GitHub Actions status badge

### Fixed

- Unit tests runnable in standalone CI via Mage-OS Composer mirror
- Keep `CHANGELOG.md` and `SECURITY.md` in Composer package exports

### Changed

- Security policy points at Private Vulnerability Reporting on the published repository

## [1.0.0] - 2026-07-12

### Added

- Admin Log Viewer grid, tail view, and download for files under `var/log`
- Per-role glob patterns and optional download flag on User Roles
- ACL resources for view and download
- Unit tests for path/pattern safety, services, plugins, UI, and controllers

[Unreleased]: https://github.com/fabiano-mallmann/module-log-viewer/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/fabiano-mallmann/module-log-viewer/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/fabiano-mallmann/module-log-viewer/releases/tag/v1.0.0
