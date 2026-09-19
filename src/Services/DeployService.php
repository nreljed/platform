<?php

namespace Nreljed\Platform\Services;

use Illuminate\Support\Facades\Schema;

/**
 * Admin-facing deploy status.
 *
 * Reads the REAL deploy state instead of a phantom lock file:
 *
 *  - `deploy.lock_path`  — PID file written by the Deploy\Services\DeployService
 *                          (config default base_path('.deploy-lock')). Present and
 *                          fresh within `deploy.lock_ttl` means a deploy is running.
 *  - `deploy.version_path` — last successfully deployed tag
 *                            (config default base_path('.deployed-version')).
 *  - `deploy_releases`   — latest recorded release row (status/error/timestamps).
 *
 * This keeps the admin status page truthful even though the deploy engine never
 * writes a JSON status file — it drives state through the lock + version
 * markers and the deploy_releases history table.
 */
class DeployService
{
    /**
     * @return array{status: string, tag: string|null, output: list<string>, core: bool, locked: bool, environment: string, last_release: array<string, mixed>|null}
     */
    public function check(): array
    {
        $lockFile = config('deploy.lock_path', base_path('.deploy-lock'));
        $versionFile = config('deploy.version_path', base_path('.deployed-version'));
        $lockTtl = (int) config('deploy.lock_ttl', 600);

        $locked = is_file($lockFile) && (time() - filemtime($lockFile)) < $lockTtl;

        $tag = null;
        if (is_file($versionFile)) {
            $tag = trim((string) file_get_contents($versionFile)) ?: null;
        }

        return [
            'status' => $locked ? 'running' : 'idle',
            'tag' => $tag,
            'output' => [],
            'core' => true,
            'locked' => $locked,
            'environment' => app()->environment(),
            'last_release' => $this->lastRelease(),
        ];
    }

    /**
     * Latest recorded deploy_releases row, or null when the history table is
     * not available (pre-migration / fresh install).
     *
     * @return array<string, mixed>|null
     */
    private function lastRelease(): ?array
    {
        try {
            if (! Schema::hasTable('deploy_releases')) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        $release = \Nreljed\Platform\Models\DeployRelease::query()
            ->latest('id')
            ->first();

        if ($release === null) {
            return null;
        }

        return [
            'id' => $release->id,
            'tag' => $release->tag,
            'status' => $release->status,
            'error' => $release->error,
            'deployed_at' => $release->deployed_at?->toISOString(),
            'rolled_back_at' => $release->rolled_back_at?->toISOString(),
        ];
    }
}