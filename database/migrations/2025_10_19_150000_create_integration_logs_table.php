<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service'); // whatsapp | archive | other
            $table->string('endpoint');
            $table->string('method', 10)->default('POST');
            $table->text('request_payload')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('message_id')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamps();
            $table->index(['service', 'endpoint']);
            $table->index(['message_id']);
            $table->index(['correlation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
