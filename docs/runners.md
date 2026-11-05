# Runners (CI portability)

Every nreljed workflow reads `runs-on` from per-repo Actions variables, so switching between
self-hosted and GitHub-hosted runners is a variable flip — no code change.

| Variable | self-hosted (default) | GitHub-hosted |
|---|---|---|
| `RUNNER_CI` (validators, push-deploy, update workflows, core/module CI) | `self-hosted` | `ubuntu-latest` |
| `RUNNER_PACKAGE` (Windows-bound build/packaging: pwsh, 7-Zip) | `self-hosted` | `windows-latest` |

Flip all repos at once:

```powershell
pwsh -NoProfile -File C:\tools\switch-runners.ps1 self|cloud
```

Single repo:

```bash
gh variable set RUNNER_CI -R nreljed/<repo> --body ubuntu-latest
```

`runs-on` is `${{ vars.RUNNER_CI || 'self-hosted' }}`, so a missing variable degrades safely to self-hosted.

## Rules for workflow authors

- Validators (`ci.yml`, `pr.yml`, `consumer-ci.yml`, core/module CI) must run on `ubuntu-latest`:
  no hardcoded Windows paths (`C:\php`, `pwsh`, `Compress-Archive`, `taskkill`) outside a
  `RUNNER_PACKAGE` job. Use `setup-php` + cache steps gated on `inputs.hosted-runner`.
- `RUNNER_PACKAGE` stays on Windows only if packaging needs `Compress-Archive`/7-Zip.
- `consumer-ci.yml` cache steps are gated `RUNNER_CI != 'self-hosted'` — cache is re-enabled
  only on GitHub-hosted (ephemeral) runners; self-hosted stays cache-free (upload-stall trap).
- `workflow_call` reusables resolve `vars` against the **calling** repo — every consumer must
  have `RUNNER_CI` set.
- Project-WP caveat: its workflows hardcode `C:\php`; a cloud flip breaks those jobs until
  `setup-php` is added. Stage for consistency only.

Verified (2026-11-05): flipping `nreljed/laravel` to `ubuntu-latest` ran `dependency-sync.yml`
to a green conclusion; flipping back to `self-hosted` restored the original behaviour.
