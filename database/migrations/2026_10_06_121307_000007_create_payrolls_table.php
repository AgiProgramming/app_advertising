<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slip_number')->unique();
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('restrict');
            $table->foreignUuid('spk_id')->constrained('spks')->onDelete('restrict');
            $table->string('period');
            $table->decimal('original_spk_value', 15, 2);
            $table->decimal('net_spk_value', 15, 2);
            $table->decimal('salary_percentage', 5, 2)->default(22.00);
            $table->decimal('meal_allowance', 15, 2)->default(0);
            $table->decimal('cash_advance_deduction', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2);
            $table->enum('payment_status', ['Belum Dibayar', 'Sudah Dibayar'])->default('Belum Dibayar');
            $table->date('payment_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
