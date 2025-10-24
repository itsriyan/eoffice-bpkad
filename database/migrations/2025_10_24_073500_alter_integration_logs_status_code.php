<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('integration_logs', function (Blueprint $table) {
            // Change status_code to unsignedInteger to support larger API error codes (e.g., 131032)
            $table->unsignedInteger('status_code')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('integration_logs', function (Blueprint $table) {
            // Revert back (WARNING: may truncate large codes) only if needed
            $table->unsignedSmallInteger('status_code')->nullable()->change();
        });
    }
};
