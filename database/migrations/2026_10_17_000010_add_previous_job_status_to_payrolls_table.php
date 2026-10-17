<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Menyimpan status SPK sebelum slip dibuat, supaya bisa dipulihkan saat slip dihapus.
            $table->string('previous_job_status')->nullable()->after('payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('previous_job_status');
        });
    }
};
