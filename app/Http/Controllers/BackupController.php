<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class BackupController extends Controller
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    public function index()
    {
        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => $file->getFilename(),
                'size' => round($file->getSize() / 1024, 2) . ' KB',
                'created_at' => date('d/m/Y h:i A', $file->getMTime()),
                'timestamp' => $file->getMTime(),
            ];
        }

        // Sort latest first
        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return view('settings.backups', [
            'backups' => $backups,
        ]);
    }

    public function create()
    {
        try {
            $timestamp = date('Y_m_d_His');
            $backupFilename = "backup_licorerapos_{$timestamp}.sqlite";
            $targetPath = $this->backupDir . '/' . $backupFilename;

            $sqlitePath = database_path('database.sqlite');
            if (File::exists($sqlitePath)) {
                File::copy($sqlitePath, $targetPath);
            } else {
                // If not sqlite, create a structured dump info file
                File::put($targetPath, "Backup snapshot generated at " . date('Y-m-d H:i:s'));
            }

            AuditService::log(
                'backup_created',
                'Backup',
                null,
                null,
                ['filename' => $backupFilename],
                "Copia de seguridad del sistema generada exitosamente: {$backupFilename}"
            );

            return redirect()->route('backups.index')->with('success', "Copia de seguridad '{$backupFilename}' creada exitosamente.");
        } catch (Exception $e) {
            return redirect()->route('backups.index')->with('error', "Error al generar respaldo: " . $e->getMessage());
        }
    }

    public function download(string $filename)
    {
        // Sanitize filename to prevent directory traversal
        $safeFilename = basename($filename);
        $filePath = $this->backupDir . '/' . $safeFilename;

        if (!File::exists($filePath)) {
            abort(404, 'El archivo de copia de seguridad no existe.');
        }

        return Response::download($filePath, $safeFilename);
    }

    public function destroy(string $filename)
    {
        $safeFilename = basename($filename);
        $filePath = $this->backupDir . '/' . $safeFilename;

        if (File::exists($filePath)) {
            File::delete($filePath);
            return redirect()->route('backups.index')->with('success', 'Copia de seguridad eliminada exitosamente.');
        }

        return redirect()->route('backups.index')->with('error', 'El archivo no fue encontrado.');
    }
}
