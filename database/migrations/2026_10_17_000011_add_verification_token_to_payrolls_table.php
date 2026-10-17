<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Token acak untuk verifikasi keaslian slip lewat QR (tanpa login).
            $table->uuid('verification_token')->nullable()->unique()->after('previous_job_status');
        });

        // Isi token untuk slip yang sudah ada.
        DB::table('payrolls')->whereNull('verification_token')->orderBy('id')->each(function ($row) {
            DB::table('payrolls')->where('id', $row->id)->update([
                'verification_token' => (string) Str::uuid(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique(['verification_token']);
            $table->dropColumn('verification_token');
        });
    }
};
