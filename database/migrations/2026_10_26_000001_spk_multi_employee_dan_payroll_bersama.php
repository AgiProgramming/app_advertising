<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perombakan model penggajian:
 *  - Satu SPK dikerjakan beberapa orang (tabel spk_employees).
 *  - Tiap penugasan punya peran (worker/replacement) dan jumlah hari masuk.
 *  - Satu slip gaji = satu karyawan untuk satu periode, isinya banyak SPK (payroll_items).
 *  - Nilai tukar PPN, komisi, dan uang makan disimpan per slip agar riwayat tetap akurat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            $table->string('client_name')->nullable()->after('spk_number');
            $table->string('location')->nullable()->after('job_name');
            $table->date('start_date')->nullable()->after('date');
            $table->date('end_date')->nullable()->after('start_date');
            $table->unsignedSmallInteger('duration_days')->default(1)->after('end_date');
        });

        Schema::create('spk_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('spk_id')->constrained('spks')->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('role', ['worker', 'replacement'])->default('worker');
            $table->foreignUuid('replacement_for_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->unsignedSmallInteger('days_worked')->default(0);
            $table->timestamps();

            $table->unique(['spk_id', 'employee_id', 'role'], 'spk_employee_role_unique');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('gross_share', 15, 2)->default(0)->after('period');
            $table->decimal('ppn_rate', 5, 2)->default(4.00)->after('gross_share');
            $table->decimal('ppn_amount', 15, 2)->default(0)->after('ppn_rate');
            $table->decimal('commission_rate', 5, 2)->default(22.00)->after('ppn_amount');
            $table->decimal('commission_amount', 15, 2)->default(0)->after('commission_rate');
            $table->decimal('meal_rate', 15, 2)->default(100000)->after('commission_amount');
            $table->unsignedSmallInteger('meal_count')->default(0)->after('meal_rate');
            $table->decimal('meal_deduction', 15, 2)->default(0)->after('meal_count');
        });

        // Slip tidak lagi terikat pada satu SPK.
        Schema::table('payrolls', function (Blueprint $table) {
            $table->uuid('spk_id')->nullable()->change();
            $table->decimal('original_spk_value', 15, 2)->nullable()->change();
            $table->decimal('net_spk_value', 15, 2)->nullable()->change();
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignUuid('spk_id')->constrained('spks')->restrictOnDelete();
            $table->string('spk_number');
            $table->decimal('spk_value', 15, 2);
            $table->unsignedSmallInteger('slot_count');
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedSmallInteger('days_worked');
            $table->decimal('daily_rate', 15, 2);
            $table->decimal('share_amount', 15, 2);
            $table->timestamps();
        });

        // Data lama: satu SPK satu karyawan -> jadikan penugasan penuh.
        $legacy = DB::table('spks')->whereNotNull('employee_id')->get();
        foreach ($legacy as $row) {
            DB::table('spk_employees')->insertOrIgnore([
                'spk_id' => $row->id,
                'employee_id' => $row->employee_id,
                'role' => 'worker',
                'days_worked' => max(1, (int) ($row->duration_days ?? 1)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('spk_employees');

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'gross_share', 'ppn_rate', 'ppn_amount', 'commission_rate',
                'commission_amount', 'meal_rate', 'meal_count', 'meal_deduction',
            ]);
        });

        Schema::table('spks', function (Blueprint $table) {
            $table->dropColumn(['client_name', 'location', 'start_date', 'end_date', 'duration_days']);
        });
    }
};
