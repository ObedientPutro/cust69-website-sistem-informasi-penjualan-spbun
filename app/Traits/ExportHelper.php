<?php

namespace App\Traits;

use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportHelper
{
    /**
     * Generate PDF Download menggunakan Master Layout
     */
    public static function toPdf(string $view, array $data, string $filename): \Illuminate\Http\Response
    {
        // Inject Settings ke semua PDF secara otomatis
        $data['settings'] = SiteSetting::first();

        $data['settings']->logoLeftBase64 = self::imageToBase64($data['settings']->logo_left) ?? null;
        $data['settings']->logoRightBase64 = self::imageToBase64($data['settings']->logo_right) ?? null;

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Generate CSV Stream Download (Memory Efficient)
     * * @param string $filename Nama file
     * @param array $headers Header kolom CSV ['Tanggal', 'Produk', ...]
     * @param Builder $query Query Builder (belum di-get)
     * @param callable $rowMapper Function untuk memetakan object database ke array CSV
     */
    public static function toCsv(string $filename, array $headers, Builder|Collection $data, callable $rowMapper): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $data, $rowMapper) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            if ($data instanceof Builder) {
                // Jika Query Builder, pakai Chunking (Hemat RAM)
                $data->chunk(500, function ($rows) use ($handle, $rowMapper) {
                    foreach ($rows as $row) {
                        fputcsv($handle, $rowMapper($row));
                    }
                });
            } else {
                // Jika Collection, loop langsung
                foreach ($data as $row) {
                    fputcsv($handle, $rowMapper($row));
                }
            }

            fclose($handle);
        }, $filename);
    }

    // --- HELPER FUNCTION Base64 ---
    private static function imageToBase64($path): ?string
    {
        if (!$path) return null;

        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        try {
            $data = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path);
            return 'data:' . $mimeType . ';base64,' . base64_encode($data);
        } catch (\Exception $e) {
            return null;
        }
    }

}
