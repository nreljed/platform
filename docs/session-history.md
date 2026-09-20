# Session History

Chronological log of audit/refactor sessions on this platform. Use this to track what
was done, why, and what remains — before starting new work, read the last entry.

## Session format

Each entry records: date, scope, what changed (with commit SHAs), what was verified,
what remains open. New sessions append at the top.

---

## 2026-09-20 — Full kernel audit (A→E hardening)

Scope: whole `nreljed/platform` v0.1.54 — code, security, scalability, docs, CI, tests.

### Why
Post-tag audit after the 0.1.54 deploy-dashboard strip. Requests: find hidden dead code,
check scalability/flexibility of services, security posture, platform health, and keep
docs up to date going forward.

### Findings locked in
- Broken `POST /deploy/backup` route — `DeployController::backup()` removed by strip.
- 4 HIGH security (role-duplicate escalation, media unowned-file destroy, deploy-token
  query transport, unwired `HtmlSanitizer` + JSON-LD `</script>` breakout), 10 MEDIUM, 14 LOW.
- Dead code: orphaned `resources/js/Components/editor/**` (64 files), `blog/ProseContent.tsx`,
  settings archive tree, ~9 orphan PHP services, unrouted `AdminController::settings()`,
  dead `DeployController::updateEnv()`.
- Scalability: unbounded user listing, PHP-side AI usage rollup, full-table bcrypt
  recovery-token scan, audit-log page scans, N+1s, sync heavy ops in-request.
- Queued-AI: decrypted BYOK key serialized into job payload; worker can't resolve.
- Health: no behavioral tests, no repo Pint/PHPStan/composer audit, CHANGELOG dead at
  0.1.34, docs stale (command names, config counts, notification types, pages).

### Changes applied
- Phase A: security P0 + removed `/deploy/backup` route.
  - Removed `POST /deploy/backup` route (`src/Deploy/Routes/web.php`) — controller
    method was stripped pre-0.1.54, endpoint 500'd on prod. Config + docs updated.
  - H1: `RoleController::duplicate()` now blocks `isSystem()` roles (matches
    update/destroy guards).
  - H2: `MediaController::destroy()` requires `canManageAny` before deleting
    unrecorded files by path (mirrors cleanup gate); media routes throttled.
  - H3: `VerifyDeployToken` reads `Authorization: Bearer` first; `?token=` accepted
    only while `deploy.allow_query_token=true` (default) so consumers migrate
    without breakage; new optional `deploy.ip_allowlist` (CIDR, `IpUtils`) via
    `DEPLOY_IP_ALLOWLIST`.
  - H4: escaped JSON-LD `</script>` breakout in blog Show theme — `escapeJsonLd()`
    helper encodes `<`/`>`/U+2028/U+2029. `HtmlSanitizer` kept (not deleted in B).
  - Commits: `591ac00` fix(deploy) route cleanup + token hardening;
    `6cf11d9` fix(auth/media/blog) close three HIGH security gaps.
- Phase B: dead-code strip.
- Phase C: scalability fixes + queued-AI fix + service splits.
- Phase D: docs/CHANGELOG/CI/tests/hygiene.
- Phase E: backlog items.
(fill commit SHAs per phase as they land)

### Verified
(fill: test suite counts, Pint, PHPStan, behavior checks)

### Remains open
- Consumer (mono) follow-ups once it syncs to this release: delete obsolete
  `tests/Feature/Deploy/DatabaseBackupEndpointTest.php` (tests removed route),
  `.env.example`/`.env.production.example` `# ... + /deploy/backup` comments.
- `laravel/ai` 1.0 migration when GA (0.x → 1.x is breaking).
- PHP floor decision (^8.4 vs ^8.3).