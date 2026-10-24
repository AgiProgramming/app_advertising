<?php

return [
    // Potongan PPN dari bagian kotor karyawan (persen).
    'ppn_rate' => (float) env('PAYROLL_PPN_RATE', 4),

    // Komisi Mann Plus yang diambil dari bagian setelah PPN (persen).
    'commission_rate' => (float) env('PAYROLL_COMMISSION_RATE', 22),

    // Uang makan yang dipotong per SPK yang diikuti (rupiah).
    'meal_rate' => (float) env('PAYROLL_MEAL_RATE', 100000),
];
