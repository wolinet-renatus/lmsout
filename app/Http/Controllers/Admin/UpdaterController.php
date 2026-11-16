<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeploymentInspectorService;
use App\Services\SystemBackupService;
use Database\Seeders\VersionUpdateSeeder;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Admin System Updater & Post-Deploy Sync Controller
 *
 * Inspects application version alignment, Git/CI-CD deployment status,
 * pending migrations, and executes post-deployment synchronization tasks.
 * @author Renatus Bernardo
 */
class UpdaterController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(
        protected DeploymentInspectorService $inspector,
        protected SystemBackupService $backupService
    ) {}

    /**
     * Display system update status and post-deployment checks.
     */
    public function index(Request $request): View
    {
        $versions = $this->inspector->getSystemVersions();
        $deployment = $this->inspector->getGitDeploymentInfo();
        $migrations = $this->inspector->getPendingMigrations();
        $checks = $this->inspector->getReadinessChecks();
        $backupStats = $this->backupService->getStats();

        $currentVersion = $versions['current_version'];
        $newVersion = $versions['new_version'];

        $syncRequired = $versions['status'] === 'update_available'
            || $migrations['count'] > 0
            || $deployment['new_commit_detected'];

        if ($request->boolean('recheck')) {
            if ($syncRequired) {
                ToastMagic::info('Deployment check complete: updates or pending migrations detected.');
            } else {
                ToastMagic::success('Deployment check complete: system is synchronized and up to date.');
            }
        }

        return view('admin.updater.index', compact(
            'versions',
            'deployment',
            'migrations',
            'checks',
            'backupStats',
            'currentVersion',
            'newVersion',
            'syncRequired'
        ));
    }

    /**
     * Run post-deployment synchronization (backup, migrations, version seed, storage link, cache clear).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'create_backup' => 'nullable|boolean',
            'clear_cache' => 'nullable|boolean',
        ]);

        $createBackup = (bool) ($validated['create_backup'] ?? false);
        $clearCache = (bool) ($validated['clear_cache'] ?? true);

        $logs = [];
        $deployment = $this->inspector->getGitDeploymentInfo();

        try {
            $logs[] = '[' . now()->format('Y-m-d H:i:s') . '] Starting post-deployment synchronization...';

            if ($createBackup) {
                $backupFile = $this->backupService->createBackup('database');
                $logs[] = "[OK] Pre-update database backup created: {$backupFile}";
            }

            Artisan::call('migrate', ['--force' => true]);
            $migrateOut = trim(Artisan::output());
            $logs[] = "[MIGRATE]\n" . ($migrateOut !== '' ? $migrateOut : 'Nothing to migrate.');

            Artisan::call('db:seed', ['--class' => 'VersionUpdateSeeder', '--force' => true]);
            $seedOut = trim(Artisan::output());
            $logs[] = "[VERSION SEED]\n" . ($seedOut !== '' ? $seedOut : 'Version updated to ' . VersionUpdateSeeder::NEW_VERSION);

            ensure_storage_link();
            $logs[] = '[OK] Public storage symbolic link verified.';

            if ($clearCache) {
                Artisan::call('optimize:clear');
                $optOut = trim(Artisan::output());
                $logs[] = "[CACHE CLEAR]\n" . ($optOut !== '' ? $optOut : 'Application caches cleared.');
            }

            $this->inspector->recordSyncCompleted($deployment['commit'], VersionUpdateSeeder::NEW_VERSION);
            $logs[] = '[DONE] System synchronized at v' . VersionUpdateSeeder::NEW_VERSION . ' (Laravel ' . app()->version() . ').';

            ToastMagic::success('System synchronized successfully.');

            return redirect()
                ->route('admin.updater.index')
                ->with('success', 'System updated and synchronized successfully.')
                ->with('output', implode("\n\n", $logs));
        } catch (\Throwable $e) {
            report($e);
            $logs[] = '[ERROR] ' . $e->getMessage();
            ToastMagic::error('Update failed: ' . $e->getMessage());

            return redirect()
                ->route('admin.updater.index')
                ->with('error', 'Update failed: ' . $e->getMessage())
                ->with('output', implode("\n\n", $logs));
        }
    }
}
