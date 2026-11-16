<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemBackupService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Admin Backup Management Controller
 *
 * Provides database, media storage, and full system backup creation,
 * download, and lifecycle management from the Admin Panel.
 * @author Renatus Bernardo
 */
class BackupController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(
        protected SystemBackupService $backupService
    ) {}

    /**
     * List existing backups and system readiness.
     */
    public function index(): View
    {
        $backups = $this->backupService->listBackups();
        $stats = $this->backupService->getStats($backups);

        return view('admin.backups.index', compact('backups', 'stats'));
    }

    /**
     * Create a new backup (database, files, or full).
     */
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:database,files,full',
        ]);

        $type = $validated['type'];

        try {
            $fileName = $this->backupService->createBackup($type);
            $label = match ($type) {
                'database' => 'Database backup',
                'files' => 'Media backup',
                'full' => 'Full system backup',
            };

            ToastMagic::success("{$label} ({$fileName}) created successfully.");
        } catch (\Throwable $e) {
            report($e);
            ToastMagic::error('Backup failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.backups.index');
    }

    /**
     * Download a backup file.
     */
    public function download(string $fileName): BinaryFileResponse|RedirectResponse
    {
        $path = $this->backupService->backupPath($fileName);

        if (! File::exists($path)) {
            ToastMagic::error('Backup file not found.');

            return redirect()->route('admin.backups.index');
        }

        return response()->download($path, basename($path));
    }

    /**
     * Delete a backup file.
     */
    public function destroy(string $fileName): RedirectResponse
    {
        if ($this->backupService->deleteBackup($fileName)) {
            ToastMagic::success('Backup "' . basename($fileName) . '" deleted successfully.');
        } else {
            ToastMagic::error('Backup file not found.');
        }

        return redirect()->route('admin.backups.index');
    }
}
