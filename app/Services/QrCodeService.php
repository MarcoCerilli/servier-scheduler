<?php

namespace App\Services;

use App\Models\Schedule;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    /**
     * Genera il QR Code PNG per l'URL pubblico della pianificazione
     * e lo salva in storage/app/public/qrcodes/{token}.png
     *
     * @return string Path relativo salvato nel DB (es. "qrcodes/abc123.png")
     */
    public function generate(Schedule $schedule): string
    {
        $publicUrl = $schedule->publicUrl();
        $filename  = "qrcodes/{$schedule->public_token}.png";

        // endroid/qr-code v6: usa named arguments nel costruttore
        $builder = new Builder(
            writer:               new PngWriter(),
            data:                 $publicUrl,
            encoding:             new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size:                 400,
            margin:               20,
            roundBlockSizeMode:   RoundBlockSizeMode::Margin,
        );

        $result = $builder->build();

        // Salva fuori dalla web root, accessibile solo tramite storage:link
        Storage::disk('public')->put($filename, $result->getString());

        return $filename;
    }

    /**
     * Restituisce l'URL pubblico del QR Code.
     */
    public function publicUrl(Schedule $schedule): string
    {
        if (! $schedule->qr_code_path) {
            return '';
        }

        return Storage::disk('public')->url($schedule->qr_code_path);
    }

    /**
     * Elimina il QR Code dallo storage.
     */
    public function delete(Schedule $schedule): void
    {
        if ($schedule->qr_code_path) {
            Storage::disk('public')->delete($schedule->qr_code_path);
        }
    }
}
