<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dispositions', function (Blueprint $table) {
            $table->foreignId('claimed_by_user_id')->nullable()->after('sequence')->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable()->after('claimed_by_user_id');
            $table->index('claimed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('dispositions', function (Blueprint $table) {
            $table->dropIndex(['claimed_by_user_id']);
            $table->dropColumn(['claimed_by_user_id', 'claimed_at']);
        });
    }
};
