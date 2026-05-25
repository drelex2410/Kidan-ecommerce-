<?php

namespace App\Services\Uploads;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\Databases\MySql;
use ZipArchive;

class UploadBackupService
{
    public function createBackups(?string $targetDirectory = null): array
    {
        $backupDirectory = $targetDirectory ?: $this->createBackupDirectory();
        File::ensureDirectoryExists($backupDirectory);

        $fullDump = $backupDirectory . '/database-full.sql.gz';
        $uploadsDump = $backupDirectory . '/uploads-only.sql.gz';
        $settingsDump = $backupDirectory . '/settings-only.sql.gz';

        $this->dumpDatabase($fullDump);
        $this->dumpDatabase($uploadsDump, ['uploads']);
        $this->dumpDatabase($settingsDump, ['settings']);

        $archives = [];
        foreach ((array) config('uploads.filesystem_directories', []) as $directory) {
            $absolutePath = base_path($directory);

            if (!is_dir($absolutePath)) {
                continue;
            }

            $archivePath = $backupDirectory . '/' . str_replace(['/', '\\'], '-', trim($directory, '/')) . '.zip';
            $this->zipDirectory($absolutePath, $archivePath);
            $archives[] = $archivePath;
        }

        $manifest = [
            'created_at' => now()->toDateTimeString(),
            'backup_directory' => $backupDirectory,
            'database_backups' => [
                'full' => $fullDump,
                'uploads' => $uploadsDump,
                'settings' => $settingsDump,
            ],
            'file_archives' => $archives,
            'php_ini_recommendations' => config('uploads.php_ini_recommendations', []),
        ];

        file_put_contents(
            $backupDirectory . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $manifest;
    }

    public function createBackupDirectory(): string
    {
        $base = storage_path((string) config('uploads.backup_root', 'backups/uploads-reset'));
        $directory = $base . '/' . now()->format('Y-m-d-His');

        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /**
     * @param  array<int, string>|null  $tables
     */
    protected function dumpDatabase(string $targetFile, ?array $tables = null): void
    {
        $connection = config('database.connections.' . config('database.default'));

        if (!is_array($connection)) {
            throw new RuntimeException('Database connection is not configured for backup.');
        }

        $dumper = MySql::create()
            ->setDbName((string) ($connection['database'] ?? ''))
            ->setUserName((string) ($connection['username'] ?? ''))
            ->setPassword((string) ($connection['password'] ?? ''))
            ->setHost((string) ($connection['host'] ?? '127.0.0.1'))
            ->setPort((int) ($connection['port'] ?? 3306))
            ->useSingleTransaction()
            ->skipLockTables()
            ->useQuick()
            ->doNotUseColumnStatistics()
            ->useCompressor(new GzipCompressor());

        if (!empty($connection['unix_socket'])) {
            $dumper->setSocket((string) $connection['unix_socket']);
        }

        if ($tables !== null && $tables !== []) {
            $dumper->includeTables($tables);
        }

        $dumper->dumpToFile($targetFile);
    }

    protected function zipDirectory(string $sourceDirectory, string $archivePath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Unable to create archive: {$archivePath}");
        }

        $sourceDirectory = rtrim($sourceDirectory, DIRECTORY_SEPARATOR);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $pathName = $item->getPathname();
            $relativePath = ltrim(str_replace($sourceDirectory, '', $pathName), DIRECTORY_SEPARATOR);

            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
                continue;
            }

            $zip->addFile($pathName, $relativePath);
        }

        $zip->close();
    }
}
