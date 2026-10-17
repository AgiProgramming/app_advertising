@php
    $original = (float) $payroll->original_spk_value;
    $netSpk = (float) $payroll->net_spk_value;
    $percentage = (float) $payroll->salary_percentage;
    $ppn = round($original - $netSpk, 2);
    $gross = round($netSpk * $percentage / 100, 2);
    $fmt = fn ($value) => number_format((float) $value, 2, ',', '.');
    $periodLabel = \Illuminate\Support\Carbon::createFromFormat('Y-m', $payroll->period)->locale('id')->translatedFormat('F Y');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji {{ $payroll->slip_number }}</title>
    <style>
        @page { margin: 16px 18px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; color: #0F172A; margin: 0; }
        .head { text-align: center; border-bottom: 1px dashed #CBD5E1; padding-bottom: 7px; }
        .brand { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
        .addr { font-size: 7.5px; color: #64748B; line-height: 1.5; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .lbl { color: #64748B; }
        .val { text-align: right; }
        .meta { padding: 7px 0; border-bottom: 1px dashed #CBD5E1; }
        .block { padding-top: 6px; }
        .sep { border-top: 1px dashed #CBD5E1; margin: 6px 0; }
        .sep-double { border-top: 3px double #CBD5E1; margin: 7px 0 5px; }
        .total-lbl { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; color: #475569; }
        .total-val { font-family: 'DejaVu Sans', sans-serif; font-size: 13px; font-weight: bold; }
        .badge { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5px; font-weight: bold; padding: 2px 6px; border-radius: 8px; }
        .paid { background: #D1FAE5; color: #059669; }
        .unpaid { background: #FEF3C7; color: #B45309; }
        .foot { margin-top: 10px; padding-top: 7px; border-top: 1px dashed #CBD5E1; }
        .note { font-size: 7px; color: #64748B; line-height: 1.6; }
        .qr { text-align: right; }
        .qr img { width: 62px; height: 62px; }
        .small { font-size: 7px; color: #64748B; }
    </style>
</head>
<body>

<div class="head">
    <div class="brand">Mann Plus Advertising</div>
    <div class="addr">
        Neon Box &middot; Billboard &middot; Media Promosi<br>
        Jl. Cirebon &mdash; Jawa Barat
    </div>
</div>

<table class="meta">
    <tr><td class="lbl">No. Slip</td><td class="val">{{ $payroll->slip_number }}</td></tr>
    <tr><td class="lbl">Periode</td><td class="val">{{ $periodLabel }}</td></tr>
    <tr><td class="lbl">Tanggal</td><td class="val">{{ $payroll->created_at?->format('d-m-Y') }}</td></tr>
</table>

<table class="block">
    <tr><td class="lbl">Nama</td><td class="val">{{ $payroll->employee?->name }}</td></tr>
    <tr><td class="lbl">NIK</td><td class="val">{{ $payroll->employee?->nik }}</td></tr>
    <tr><td class="lbl">Jabatan</td><td class="val">{{ $payroll->employee?->position }}</td></tr>
    <tr><td class="lbl">Pekerjaan</td><td class="val">{{ $payroll->spk?->job_name }}</td></tr>
</table>

<div class="sep"></div>

<table>
    <tr><td class="lbl">Nilai SPK</td><td class="val">{{ $fmt($original) }}</td></tr>
    <tr><td class="lbl">PPN 3%</td><td class="val">-{{ $fmt($ppn) }}</td></tr>
    <tr><td class="lbl">Nilai bersih</td><td class="val">{{ $fmt($netSpk) }}</td></tr>
</table>

<div class="sep"></div>

<table>
    <tr><td class="lbl">Komisi {{ rtrim(rtrim(number_format($percentage, 2, ',', '.'), '0'), ',') }}%</td><td class="val">{{ $fmt($gross) }}</td></tr>
    <tr><td class="lbl">Tunjangan makan</td><td class="val">{{ $fmt($payroll->meal_allowance) }}</td></tr>
    <tr><td class="lbl">Potongan kasbon</td><td class="val">-{{ $fmt($payroll->cash_advance_deduction) }}</td></tr>
</table>

<div class="sep-double"></div>

<table>
    <tr>
        <td class="total-lbl">Gaji bersih</td>
        <td class="val total-val">Rp {{ $fmt($payroll->net_salary) }}</td>
    </tr>
</table>

<table style="margin-top: 6px;">
    <tr>
        <td>
            <span class="badge {{ $payroll->payment_status === 'Sudah Dibayar' ? 'paid' : 'unpaid' }}">
                {{ $payroll->payment_status }}
                @if ($payroll->payment_date)
                    {{ \Illuminate\Support\Carbon::parse($payroll->payment_date)->format('d-m-Y') }}
                @endif
            </span>
        </td>
        <td class="val small">Diterbitkan oleh sistem penggajian</td>
    </tr>
</table>

<table class="foot">
    <tr>
        <td class="note" style="width: 62%;">
            Slip ini dibuat otomatis oleh sistem penggajian Mann Plus Advertising.<br>
            Verifikasi keaslian: {{ $verifyUrl }}
        </td>
        <td class="qr">
            <img src="data:image/png;base64,{{ $qrBase64 }}" alt="QR verifikasi">
            <div class="small">{{ $payroll->slip_number }}</div>
        </td>
    </tr>
</table>

</body>
</html>
