<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * System Backup Service
 *
 * Shared engine for creating, listing, and managing database SQL dumps
 * and media/full ZIP archives used by both Backup Management and System Updater.
 * @author Renatus Bernardo
 */
class SystemBackupService
{
    /**
     * Resolve the backup storage directory or a safe file path inside it.
     */
    public function backupPath(string $file = ''): string
    {
        $dir = storage_path('app/backups');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return $file !== '' ? $dir . DIRECTORY_SEPARATOR . basename($file) : $dir;
    }

    /**
     * List all available SQL and ZIP backups sorted newest first.
     */
    public function listBackups(): Collection
    {
        $dir = $this->backupPath();
        $files = File::files($dir);

        return collect($files)
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['sql', 'zip'], true))
            ->map(function ($file) {
                $name = $file->getFilename();
                $extension = strtolower($file->getExtension());
                $category = 'database';
                $type = 'Database (SQL)';

                if (str_contains($name, '-full-')) {
                    $category = 'full';
                    $type = 'Full Backup (DB + Media)';
                } elseif (str_contains($name, '-files-')) {
                    $category = 'files';
                    $type = 'Media & Uploads';
                }

                return [
                    'name' => $name,
                    'category' => $category,
                    'type' => $type,
                    'extension' => $extension,
                    'size_bytes' => $file->getSize(),
                    'size' => $this->formatBytes($file->getSize()),
                    'created_at' => Carbon::createFromTimestamp($file->getMTime()),
                ];
            })
            ->sortByDesc(fn ($item) => $item['created_at']->timestamp)
            ->values();
    }

    /**
     * Gather backup storage statistics and engine readiness.
     */
    public function getStats(?Collection $backups = null): array
    {
        $backups ??= $this->listBackups();
        $totalBytes = (int) $backups->sum('size_bytes');
        $diskFree = @disk_free_space(storage_path());
        $diskTotal = @disk_total_space(storage_path());

        return [
            'count' => $backups->count(),
            'sql_count' => $backups->where('extension', 'sql')->count(),
            'zip_count' => $backups->where('extension', 'zip')->count(),
            'total_size' => $this->formatBytes($totalBytes),
            'latest' => $backups->first()['created_at'] ?? null,
            'latest_name' => $backups->first()['name'] ?? null,
            'zip_available' => class_exists(ZipArchive::class),
            'database_name' => $this->activeDatabaseName(),
            'disk_free' => $diskFree !== false ? $this->formatBytes((int) $diskFree) : 'N/A',
            'disk_total' => $diskTotal !== false ? $this->formatBytes((int) $diskTotal) : 'N/A',
        ];
    }

    /**
     * Create a backup archive or SQL dump and return its filename.
     */
    public function createBackup(string $type): string
    {
        if (! in_array($type, ['database', 'files', 'full'], true)) {
            throw new \InvalidArgumentException("Unsupported backup type: {$type}");
        }

        $timestamp = now()->format('Y-m-d_His');

        if ($type === 'database') {
            $fileName = "backup-db-{$timestamp}.sql";
            $this->dumpDatabaseToSql($this->backupPath($fileName));

            return $fileName;
        }

        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('PHP ZipArchive extension is required for ZIP archives.');
        }

        $fileName = "backup-{$type}-{$timestamp}.zip";
        $zipPath = $this->backupPath($fileName);
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create zip archive in storage/app/backups.');
        }

        $tempSql = null;

        try {
            if ($type === 'full') {
                $tempSql = $this->backupPath("temp-db-{$timestamp}.sql");
                $this->dumpDatabaseToSql($tempSql);
                $zip->addFile($tempSql, 'database/database.sql');
            }

            $publicStorage = storage_path('app/public');
            if (File::isDirectory($publicStorage)) {
                $this->addDirectoryToZip($zip, $publicStorage, 'storage');
            }

            $uploadsDir = public_path('uploads');
            if (File::isDirectory($uploadsDir)) {
                $this->addDirectoryToZip($zip, $uploadsDir, 'uploads');
            }

            $zip->close();
        } finally {
            if ($tempSql !== null && File::exists($tempSql)) {
                File::delete($tempSql);
            }
        }

        return $fileName;
    }

    /**
     * Delete a backup file by filename if it exists.
     */
    public function deleteBackup(string $fileName): bool
    {
        $path = $this->backupPath($fileName);
        if (! File::exists($path)) {
            return false;
        }

        return File::delete($path);
    }

    /**
     * Generate a portable SQL dump of the active MySQL database using PDO.
     */
    public function dumpDatabaseToSql(string $targetPath): void
    {
        $pdo = DB::connection()->getPdo();
        $dbName = $this->activeDatabaseName();

        $handle = fopen($targetPath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open backup file for writing: ' . $targetPath);
        }

        try {
            fwrite($handle, "-- " . config('app.name', 'Platform') . " Database Backup\n");
            fwrite($handle, "-- Database: `{$dbName}`\n");
            fwrite($handle, '-- Generated: ' . now()->toDateTimeString() . "\n");
            fwrite($handle, "-- --------------------------------------------------------\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n");

            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $row) {
                $table = array_values((array) $row)[0];

                $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
                $createRow = (array) ($createResult[0] ?? []);
                $createSql = $createRow['Create Table'] ?? $createRow['Create View'] ?? null;

                if (! $createSql) {
                    continue;
                }

                fwrite($handle, "--\n-- Table structure for table `{$table}`\n--\n\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createSql . ";\n\n");

                if (isset($createRow['Create View'])) {
                    continue;
                }

                $count = DB::table($table)->count();
                if ($count === 0) {
                    continue;
                }

                fwrite($handle, "--\n-- Dumping data for table `{$table}` ({$count} rows)\n--\n\n");

                $rows = DB::table($table)->cursor();
                $batch = [];
                $columns = null;

                foreach ($rows as $record) {
                    $arr = (array) $record;
                    if ($columns === null) {
                        $columns = '`' . implode('`, `', array_keys($arr)) . '`';
                    }

                    $values = array_map(function ($val) use ($pdo) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        if (is_bool($val)) {
                            return $val ? '1' : '0';
                        }

                        return $pdo->quote((string) $val);
                    }, array_values($arr));

                    $batch[] = '(' . implode(', ', $values) . ')';

                    if (count($batch) >= 100) {
                        fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }

                if (! empty($batch)) {
                    fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n");
                }

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }
    }

    /**
     * Recursively add a directory to a ZipArchive.
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $zipPrefix): void
    {
        $files = File::allFiles($sourceDir);
        foreach ($files as $file) {
            $relativePath = ltrim(str_replace($sourceDir, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $zip->addFile($file->getPathname(), $zipPrefix . '/' . str_replace('\\', '/', $relativePath));
        }
    }

    /**
     * Resolve the active database name safely across connection configurations.
     */
    protected function activeDatabaseName(): string
    {
        return (string) (DB::connection()->getDatabaseName()
            ?: config('database.connections.' . config('database.default') . '.database', 'database'));
    }

    /**
     * Format byte size into human-readable units.
     */
    public function formatBytes(?int $bytes): string
    {
        if ($bytes === null || $bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}

