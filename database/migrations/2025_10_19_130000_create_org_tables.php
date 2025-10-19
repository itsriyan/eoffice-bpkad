<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        // Add role_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->timestamp('last_login_at')->nullable();
        });

        // Grades (golongan)
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('code'); // e.g. III/a
            $table->string('category', 50);
            $table->string('rank'); // e.g. Penata Muda
            $table->timestamps();
        });

        // Work Units (unit_kerja)
        Schema::create('work_units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Employees (pegawai)
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->nullOnDelete();
            $table->foreignId('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->string('name');
            $table->string('nip')->unique();
            $table->string('position');
            $table->string('email')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('phone_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
        Schema::dropIfExists('work_units');
        Schema::dropIfExists('grades');
    }
};
