# Contributing to Fsm_LogViewer

Thanks for your interest in contributing. By participating, you agree to follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## How to contribute

1. Fork the repository and create a branch from `main`.
2. Make your changes with clear, focused commits.
3. Add or update unit tests when behavior changes.
4. Open a pull request using the PR template.

### Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/), for example:

- `feat: allow role patterns to match rotated logs`
- `fix: reject symlink basenames under var/log`
- `test: cover download ACL denial`
- `docs: clarify installation steps`

## Development setup

### Standalone (package root)

From this module directory (repository root when published):

```bash
composer install
composer run test
```

This resolves Magento packages via the [Mage-OS mirror](https://mirror.mage-os.org/) declared in `composer.json`.

### Inside a Magento / Mage-OS install

When the module lives under `app/code/Fsm/LogViewer`:

```bash
# From Magento root
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  app/code/Fsm/LogViewer/Test/Unit --no-extensions
```

### Coding standard

```bash
# From Magento root, with Magento2 coding standard installed
vendor/bin/phpcs --standard=Magento2 app/code/Fsm/LogViewer
```

## Pull requests

- Keep PRs focused on one concern.
- Update `CHANGELOG.md` for user-facing changes.
- Do not commit secrets, store credentials, or customer log contents.
- Ensure CI unit tests pass on PHP 8.2 and 8.3.

## Security

Do not open public issues for vulnerabilities. See [SECURITY.md](SECURITY.md).

## Questions

Use GitHub Discussions or Issues (feature / bug templates) for non-security topics.
