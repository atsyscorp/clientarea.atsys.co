<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Contracts;
use app\models\ContractDocuments;
use yii\helpers\Console;

/**
 * Gestión y mantenimiento de contratos vía CLI.
 */
class ContractsController extends Controller
{
    /**
     * Migra los contratos y documentos anexos locales hacia Google Drive,
     * organizados en carpetas nombradas con el código del contrato (ej. CTR-2026-001).
     *
     * Uso:
     *   php yii contracts/migrate-to-drive
     *   php yii contracts/migrate-to-drive --delete-local=1
     *
     * @param int $deleteLocal Si es 1, elimina los archivos locales tras subirse con éxito a Drive (por defecto 0).
     * @return int
     */
    public function actionMigrateToDrive($deleteLocal = 0)
    {
        if (!Yii::$app->has('googleDrive')) {
            $this->stderr("Error: El componente googleDrive no está configurado en console.php.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $googleDrive = Yii::$app->googleDrive;
        if (empty($googleDrive->clientId) || empty($googleDrive->clientSecret) || empty($googleDrive->refreshToken)) {
            $this->stderr("Error: Las credenciales de Google Drive no están configuradas en el sistema (system_settings o params.php).\n", Console::FG_RED);
            return ExitCode::CONFIG;
        }

        $this->stdout("====================================================\n", Console::BOLD);
        $this->stdout("  MIGRACIÓN DE CONTRATOS Y ANEXOS A GOOGLE DRIVE   \n", Console::BOLD, Console::FG_CYAN);
        $this->stdout("====================================================\n\n");

        if ($deleteLocal) {
            $this->stdout("AVISO: La opción --delete-local=1 está activa. Los archivos locales se eliminarán tras subirse a Drive.\n\n", Console::FG_YELLOW);
        }

        // 1. Migrar archivos principales de contratos
        $contracts = Contracts::find()
            ->where(['like', 'contract_file', '/uploads/'])
            ->all();

        $this->stdout("-> Encontrados " . count($contracts) . " contratos principales con archivo local.\n\n", Console::FG_YELLOW);

        $migratedContracts = 0;
        foreach ($contracts as $contract) {
            $relativeUrl = preg_replace('#^https?://[^/]+#', '', $contract->contract_file);
            $localPath = Yii::getAlias('@webroot' . $relativeUrl);

            if (!file_exists($localPath)) {
                $this->stderr("  [OMITIDO] Contrato #{$contract->code}: El archivo local no existe ({$localPath})\n", Console::FG_RED);
                continue;
            }

            $fileName = basename($localPath);
            $this->stdout("  Subiendo contrato #{$contract->code} ({$fileName})... ");

            $driveUrl = $googleDrive->uploadLocalFile($localPath, $fileName, $contract->code);
            if ($driveUrl) {
                $contract->contract_file = $driveUrl;
                if ($contract->save(false)) {
                    $this->stdout("OK -> {$driveUrl}\n", Console::FG_GREEN);
                    $migratedContracts++;
                    if ($deleteLocal) {
                        @unlink($localPath);
                    }
                } else {
                    $this->stderr("ERROR al actualizar en BD\n", Console::FG_RED);
                }
            } else {
                $this->stderr("FALLÓ la subida a Drive\n", Console::FG_RED);
            }
        }

        // 2. Migrar documentos anexos
        $documents = ContractDocuments::find()
            ->where(['like', 'file_url', '/uploads/'])
            ->all();

        $this->stdout("\n-> Encontrados " . count($documents) . " documentos anexos con archivo local.\n\n", Console::FG_YELLOW);

        $migratedDocs = 0;
        foreach ($documents as $doc) {
            $relativeUrl = preg_replace('#^https?://[^/]+#', '', $doc->file_url);
            $localPath = Yii::getAlias('@webroot' . $relativeUrl);
            $subfolder = $doc->contract ? $doc->contract->code : 'general';

            if (!file_exists($localPath)) {
                $this->stderr("  [OMITIDO] Documento ID #{$doc->id} ('{$doc->title}'): El archivo no existe ({$localPath})\n", Console::FG_RED);
                continue;
            }

            $ext = pathinfo($localPath, PATHINFO_EXTENSION);
            $cleanTitle = $doc->title ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $doc->title) : 'anexo';
            $fileName = $cleanTitle . ($ext ? '.' . $ext : '');
            $this->stdout("  Subiendo anexo #{$doc->id} [{$subfolder}] ('{$doc->title}')... ");

            $driveUrl = $googleDrive->uploadLocalFile($localPath, $fileName, $subfolder);
            if ($driveUrl) {
                $doc->file_url = $driveUrl;
                if ($doc->save(false)) {
                    $this->stdout("OK -> {$driveUrl}\n", Console::FG_GREEN);
                    $migratedDocs++;
                    if ($deleteLocal) {
                        @unlink($localPath);
                    }
                } else {
                    $this->stderr("ERROR al actualizar en BD\n", Console::FG_RED);
                }
            } else {
                $this->stderr("FALLÓ la subida a Drive\n", Console::FG_RED);
            }
        }

        $this->stdout("\n====================================================\n", Console::BOLD);
        $this->stdout("  RESUMEN:\n", Console::BOLD);
        $this->stdout("  - Contratos principales migrados: {$migratedContracts}\n", Console::FG_GREEN);
        $this->stdout("  - Documentos anexos migrados:     {$migratedDocs}\n", Console::FG_GREEN);
        $this->stdout("====================================================\n\n");

        return ExitCode::OK;
    }
}
