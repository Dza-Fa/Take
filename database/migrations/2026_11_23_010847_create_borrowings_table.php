<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique(); // contoh: TAKE-20261123-001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('borrow_date');
            $table->date('return_date');
            $table->timestamp('returned_at')->nullable();
            $table->string('purpose'); // contoh: Rapat RT 04, Kerja Bakti RW, Syukuran
            $table->enum('status', [
                'pending',
                'approved',
                'borrowed',
                'returned',
                'rejected'
            ])->default('pending');
            $table->enum('return_condition', ['baik', 'perbaikan', 'rusak'])->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};