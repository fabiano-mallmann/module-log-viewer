# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| 1.x     | Yes       |

## Reporting a vulnerability

**Do not open a public GitHub issue** for security vulnerabilities.

Please report security issues privately using **GitHub Private Vulnerability Reporting**
(Security → Advisories → Report a vulnerability) on this repository:

https://github.com/fabiano-mallmann/module-log-viewer/security/advisories/new

If Private Vulnerability Reporting is unavailable, contact a repository maintainer
privately (for example via a private GitHub message). Do not include production log
contents that contain personal data or secrets unless strictly necessary; redact them.

We aim to acknowledge valid reports within a few business days and to coordinate a fix
and disclosure timeline with you.

## Scope

This module deals with admin access to files under `var/log`. Reports of particular interest include:

- Path traversal, null-byte, or symlink escape outside `var/log`
- ACL or role-pattern bypasses that expose or download unauthorized logs
- Privilege escalation via role configuration or admin controllers
- Information disclosure beyond what View/Download ACL intentionally allows

Out of scope: misconfiguration by store admins who grant overly broad patterns
(e.g. `*.log`) or Download permission — treat that as operational risk, not a module bug,
unless a bypass of documented controls is involved.

## Safe disclosure

Please give maintainers reasonable time to release a fix before public disclosure.
