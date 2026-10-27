<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Carbon;
use Smalot\PdfParser\Parser;

/**
 * Membaca dokumen SPK dari klien (PDF) lalu mengambil data utamanya,
 * supaya admin tidak perlu mengetik ulang.
 *
 * Contoh isi dokumen:
 *   No. SPK : SPK-BE-CO/2026/03/016/004390
 *   Tanggal : 10 Juli 2026
 *   Nama Supplier/Kontraktor : MANN PLUS
 *   1 Lisplang Acrylic- Cuci Acrylic Lisplang 16.00 m 100,000.00 1,600,000.00
 *   Total       : 2,449,636.00
 *   Lokasi pekerjaan : T4PO - GEBANG KULON
 *   Tanggal pekerjaan : 10 Juli 2026 sampai dengan 16 Juli 2026
 */
class SpkPdfParser
{
    private const BULAN = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    /**
     * Baca berkas PDF lalu kembalikan data SPK.
     */
    public function parseFile(string $path): array
    {
        if (! is_file($path)) {
            throw new Exception('Berkas PDF tidak ditemukan.');
        }

        try {
            $text = (new Parser)->parseFile($path)->getText();
        } catch (\Throwable $e) {
            throw new Exception('PDF tidak bisa dibaca: '.$e->getMessage());
        }

        return $this->parseText($text);
    }

    /**
     * Ambil data dari teks PDF (dipisah agar mudah diuji).
     */
    public function parseText(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Spasi berlebih dari hasil ekstraksi PDF.
        $flat = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        $spkNumber = $this->firstMatch('/No\.?\s*SPK\s*:\s*([^\n]+)/i', $flat);
        $supplier = $this->firstMatch('/Nama Supplier\s*\/?\s*Kontraktor\s*:\s*([^\n]+)/i', $flat);
        $location = $this->firstMatch('/Lokasi pekerjaan\s*:\s*([^\n]+)/i', $flat);

        $dateRaw = $this->firstMatch('/\bTanggal\s*:\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4})/i', $flat);
        $totalRaw = $this->firstMatch('/\bTotal\s*:\s*([\d.,]+)/i', $flat);

        $period = $this->parsePeriod($flat);
        $items = $this->parseItems($text);

        $value = $this->toNumber($totalRaw);

        // Bila total tidak terbaca, jumlahkan item.
        if ($value <= 0 && $items !== []) {
            $value = array_sum(array_column($items, 'amount'));
        }

        $start = $period['start'] ?? $this->parseDate($dateRaw);
        $end = $period['end'] ?? $start;

        return [
            'spk_number' => $spkNumber ? trim($spkNumber) : null,
            'date' => $this->parseDate($dateRaw),
            'client_name' => $this->detectClient($flat),
            'supplier_name' => $supplier ? trim($supplier) : null,
            'location' => $location ? trim($location) : null,
            'start_date' => $start,
            'end_date' => $end,
            'duration_days' => $this->duration($start, $end),
            'spk_value' => $value,
            'items' => $items,
            'item_count' => count($items),
            'raw_total' => $totalRaw,
        ];
    }

    private function firstMatch(string $pattern, string $subject): ?string
    {
        return preg_match($pattern, $subject, $m) === 1 ? trim($m[1]) : null;
    }

    /**
     * "Tanggal pekerjaan : 10 Juli 2026 sampai dengan 16 Juli 2026"
     */
    private function parsePeriod(string $text): array
    {
        if (preg_match(
            '/Tanggal pekerjaan\s*:\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4})\s*(?:sampai dengan|s\.?\/?d\.?|hingga|-|–)\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4})/i',
            $text,
            $m
        ) === 1) {
            return ['start' => $this->parseDate($m[1]), 'end' => $this->parseDate($m[2])];
        }

        return ['start' => null, 'end' => null];
    }

    /**
     * Baris item: "1 Lisplang Acrylic 16.00 m 100,000.00 1,600,000.00"
     */
    private function parseItems(string $text): array
    {
        $items = [];
        $lines = preg_split('/\n/', $text) ?: [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/[ \t]+/', ' ', $line) ?? $line);

            if (preg_match('/^(\d{1,2})\s+(.+?)\s+([\d.,]+)\s+([A-Za-z0-9]{1,5})\s+([\d.,]+)\s+([\d.,]+)$/', $line, $m) !== 1) {
                continue;
            }

            $items[] = [
                'no' => (int) $m[1],
                'name' => trim($m[2]),
                'qty' => $this->toNumber($m[3]),
                'unit' => $m[4],
                'price' => $this->toNumber($m[5]),
                'amount' => $this->toNumber($m[6]),
            ];
        }

        return $items;
    }

    private function detectClient(string $text): ?string
    {
        if (preg_match('/PT\.?\s*Albany\s+Corona\s+Lestari/i', $text) === 1) {
            return 'PT Albany Corona Lestari';
        }

        if (preg_match('/PT\.?\s*Indomarco\s+Prismatama/i', $text) === 1) {
            return 'PT Indomarco Prismatama';
        }

        return null;
    }

    /**
     * "10 Juli 2026" -> 2026-07-10
     */
    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/', $value, $m) !== 1) {
            return null;
        }

        $month = self::BULAN[strtolower($m[2])] ?? null;
        if (! $month) {
            return null;
        }

        return Carbon::create((int) $m[3], $month, (int) $m[1])->toDateString();
    }

    private function duration(?string $start, ?string $end): int
    {
        if (! $start || ! $end) {
            return 1;
        }

        return max(1, Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1);
    }

    /**
     * "2,449,636.00" -> 2449636.0 (format dokumen: koma ribuan, titik desimal)
     */
    private function toNumber(?string $value): float
    {
        if ($value === null || trim($value) === '') {
            return 0.0;
        }

        $clean = preg_replace('/[^0-9,.\-]/', '', $value) ?? '';

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace(',', '', $clean);
        } elseif (substr_count($clean, '.') > 1) {
            $clean = str_replace('.', '', $clean);
        }

        return round((float) $clean, 2);
    }
}
