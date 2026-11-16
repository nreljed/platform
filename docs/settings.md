# Settings

DB-backed application configuration, shared across apps. The `settings` table
(`Setting` model) holds `group` + `key` + `value` rows; the `SettingsService` reads them and
caches per group.

## Service

`Nreljed\Platform\Services\SettingsService` (singleton):

| Method                                             | Purpose                                                         |
| -------------------------------------------------- | --------------------------------------------------------------- |
| `getGroup(string $group): array`                   | All settings for a group (merged over config defaults, cached). |
| `get(string $group, string $key, $default = null)` | Single value with cast.                                         |
| `set(string $group, string $key, $value)`          | Persist via `updateOrCreate`.                                   |
| `setGroup(string $group, array $values)`           | Bulk persist (used by the settings controller).                 |
| `clearCache()`                                     | Forget the group cache (called after every settings update).    |

Base groups (always included): `general`, `registration`, `notifications`, `security`,
`email`, `maintenance`, `formatting`, `audit_logs`. Feature providers add domain groups via
`core.settings.groups` (e.g. Shop adds `shop`).

## Controllers & UI

- `SettingsController` (admin) — renders the settings pages (`dashboard/admin/settings/*`)
  and `update()` validates + persists via `setGroup`.
- `EmailSettingsController` — mail transport settings + test send.
- The account page exposes per-user `notification` prefs (not group settings).

## Configuration

- `config/core.php` → `settings.groups` — extra groups.
- Publish with `php artisan vendor:publish --tag=core-config`.

## Consumers

- `MailerConfigurator` — mail transport from `email` group.
- `HandleInertiaRequests` — shares `settings` for the UI.
- Feature `ShopSettingsService` / `ShopSettingsController` — a domain group (`shop`) built on
  this exact service (see Shop package `docs/settings.md`).

## Feature toggling

Features (modules) are enabled/disabled **programmatically**, not via a UI.
Use the `feature:toggle` command:

```bash
php artisan feature:toggle enable shop      # enable the Shop feature
php artisan feature:toggle disable shop     # disable it (runs onDisable() cleanup)
```

Feature slugs: `public-site`, `posts`, `shop`, `ai`, `marketing`.

Defaults come from `config/features.php` (`features.enabled.<slug>`). Runtime
overrides are persisted in the `settings` table (group `core`, key
`feature_enabled.<slug>`) via `FeatureSettingsService`. Disabling a feature
removes its routes, nav, settings, and permissions but preserves data.

## Related

- [Architecture](architecture.md) - boot-time wiring.
- [Commands](commands.md) - full command reference.
- Shop package `docs/settings.md` - example of a feature settings group.
