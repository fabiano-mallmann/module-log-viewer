# Fsm_LogViewer

Admin Log Viewer for Magento Open Source / Mage-OS. Lists and displays files under `var/log` with **per-role glob patterns** and optional download permission.

## Features

- Grid of log files allowed for the current admin role
- Tail view (last 512 KB) with in-page search
- Download gated by ACL **and** role flag
- Role tab under **System → Permissions → User Roles** to configure patterns

## Requirements

- PHP 8.2+
- Magento Open Source / Mage-OS 2.4+ (compatible with Magento Backend, User, Authorization, UI)

## Installation

### Composer (recommended for distribution)

```bash
composer require fsm/module-log-viewer
bin/magento module:enable Fsm_LogViewer
bin/magento setup:upgrade
bin/magento cache:flush
```

### App code

Copy this module to `app/code/Fsm/LogViewer`, then run the same `module:enable` / `setup:upgrade` commands.

## Configuration

1. Open **System → Permissions → User Roles**.
2. Edit a role → **Log Viewer** tab.
3. Enter one glob pattern per line (basename under `var/log`), for example:

   ```
   system.log
   exception.log
   ```

4. Optionally enable **Allow Download**.
5. Assign ACL resources under **System → Tools → Log Viewer**:
   - **View Logs** — list and open files
   - **Download Logs** — download (also requires the role flag)

Empty patterns mean the role sees **no** log files.

The Size column filter uses **bytes** (display is human-readable).

## Security

Log files often contain personal data, stack traces, tokens, or other secrets.

- Prefer **specific** patterns (`system.log`, `exception.log`) over `*.log`.
- Grant **Download** only to roles that need full files.
- The module rejects path traversal, null bytes, and symlinks under `var/log`.
- Viewing still exposes file contents to anyone with View ACL and matching patterns — treat role setup as a security control.

## ACL

| Resource | ID |
|----------|-----|
| Log Viewer | `Fsm_LogViewer::logviewer` |
| View Logs | `Fsm_LogViewer::view` |
| Download Logs | `Fsm_LogViewer::download` |

## Development

Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/).

```bash
# Unit tests (from Magento root)
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  app/code/Fsm/LogViewer/Test/Unit --no-extensions

# Coding standard
vendor/bin/phpcs --standard=Magento2 app/code/Fsm/LogViewer
```

Coverage includes path/pattern safety, `LogFileService` (ACL, symlink, tail, download), repository, role-save plugin, listing DataProvider, role tab, and admin controllers.

## License

Open Software License (OSL 3.0) and Academic Free License (AFL 3.0).
