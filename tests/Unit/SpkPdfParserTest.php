<?php

namespace Tests\Unit;

use App\Services\SpkPdfParser;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian pembacaan dokumen SPK dari klien (tanpa berkas PDF asli,
 * cukup teks hasil ekstraksi agar cepat dan tidak bergantung library).
 */
class SpkPdfParserTest extends TestCase
{
    private function teksContoh(): string
    {
        return <<<'TXT'
        Surat Perintah Kerja
        No. SPK : SPK-BE-CO/2026/03/016/004390
        Tanggal : 10 Juli 2026
        Nama Supplier/Kontraktor : MANN PLUS
        Alamat Supplier/Kontraktor : DUSUN 1 KR ASRI ,MUNDU PESISIR
        No Nama Material / Pekerjaan Qty Satuan Harga Satuan (Rp.) Jumlah (Rp.)
        1 Lisplang Acrylic- Cuci Acrylic Lisplang 16.00 m 100,000.00 1,600,000.00
        2 Pembersihan + Buang Puing ringan 1.00 ls 150,000.00 150,000.00
        3 KM- Keramik Lantai KM 4.00 m2 165,000.00 660,000.00
        4 Keramik - Kupas Keramik 4.00 m2 9,909.00 39,636.00
        Total       : 2,449,636.00
        Keterangan  : Harga tersebut tanpa PPN
        1. Lokasi pekerjaan : T4PO - GEBANG KULON
        2. Tanggal pekerjaan : 10 Juli 2026 sampai dengan 16 Juli 2026
        PT. Albany Corona Lestari
        TXT;
    }

    public function test_membaca_data_utama_spk(): void
    {
        $data = (new SpkPdfParser)->parseText($this->teksContoh());

        $this->assertSame('SPK-BE-CO/2026/03/016/004390', $data['spk_number']);
        $this->assertSame('2026-07-10', $data['date']);
        $this->assertSame('2026-07-10', $data['start_date']);
        $this->assertSame('2026-07-16', $data['end_date']);
        $this->assertSame(7, $data['duration_days']);
        $this->assertSame('T4PO - GEBANG KULON', $data['location']);
        $this->assertSame('PT Albany Corona Lestari', $data['client_name']);
        $this->assertEqualsWithDelta(2449636, $data['spk_value'], 0.01);
    }

    public function test_membaca_daftar_item(): void
    {
        $data = (new SpkPdfParser)->parseText($this->teksContoh());

        $this->assertSame(4, $data['item_count']);
        $this->assertSame('Lisplang Acrylic- Cuci Acrylic Lisplang', $data['items'][0]['name']);
        $this->assertEqualsWithDelta(16, $data['items'][0]['qty'], 0.01);
        $this->assertSame('m', $data['items'][0]['unit']);
        $this->assertEqualsWithDelta(1600000, $data['items'][0]['amount'], 0.01);
    }

    public function test_nilai_total_dihitung_dari_item_bila_total_tidak_terbaca(): void
    {
        $teks = str_replace('Total       : 2,449,636.00', '', $this->teksContoh());

        $data = (new SpkPdfParser)->parseText($teks);

        $this->assertEqualsWithDelta(2449636, $data['spk_value'], 0.01);
    }

    public function test_dokumen_tanpa_data_spk_tidak_error(): void
    {
        $data = (new SpkPdfParser)->parseText('Ini bukan dokumen SPK.');

        $this->assertNull($data['spk_number']);
        $this->assertSame(0.0, $data['spk_value']);
        $this->assertSame([], $data['items']);
        $this->assertSame(1, $data['duration_days']);
    }
}
