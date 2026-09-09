# Login Email vs Password Error Messages

**Date:** 2026-09-08  
**Area:** Filament login (`App\Filament\Pages\Authx\Login`)  
**Approach:** Distinguish unknown email vs wrong password; show field-specific Indonesian copy

## Problem

Failed login always shows *Kredensial yang diberikan tidak dapat ditemukan.* under the email field, so users cannot tell whether the email or the password is wrong.

## Decisions (confirmed)

| Topic | Choice |
|---|---|
| Wrong email copy | `Email tidak ditemukan.` |
| Wrong password copy | `Password yang Anda masukkan salah.` |
| Error placement | Email message on `data.email`; password message on `data.password` |
| Approach | Customize `Authx\Login` (not only lang `failed` string) |
| Commits | User commits manually — do not auto-commit |

**Accepted risk:** Distinct messages allow email enumeration (attackers can learn which emails exist). Product accepted this for clearer UX.

## Behavior

On `authenticate()` after rate-limit check and form state:

1. If no `User` with the submitted email → `ValidationException` on `data.email` with the wrong-email message. Do not call `attempt()`.
2. If user exists but `Filament::auth()->attempt(...)` fails → `ValidationException` on `data.password` with the wrong-password message.
3. If attempt succeeds but user cannot access the panel → logout; keep a **generic** failure (existing `messages.failed` or equivalent) so this path does not imply “wrong password.”
4. Rate limit (5) unchanged.

## Copy / i18n

Prefer app lang keys under `lang/id/auth.php` (avoid reusing Laravel’s existing `auth.password` key):

- `email_not_found` → Email tidak ditemukan.
- `password_incorrect` → Password yang Anda masukkan salah.

English: add `lang/en/auth.php` only if the project already maintains `en` auth strings; otherwise Indonesian-only is enough for this panel.

## Touch points

| File | Role |
|---|---|
| `app/Filament/Pages/Authx/Login.php` | Split failure paths; field-targeted exceptions |
| `lang/id/auth.php` (and `lang/en/auth.php` if present) | New message keys |
| Feature test for login failures | Assert message + field for unknown email vs wrong password |

## Non-goals

- Changing rate limiting or remember-me
- Revealing why panel access was denied
- Changing registration / password reset copy
- Auto git commits

## Related

- Panel login registration: `app/Providers/Filament/AdminPanelProvider.php` (`->login(Login::class)`)
- Current generic message source: `lang/vendor/filament-panels/id/pages/auth/login.php` (`messages.failed`)
