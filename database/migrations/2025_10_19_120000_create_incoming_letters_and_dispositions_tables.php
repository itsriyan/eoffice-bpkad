<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->string('letter_number')->unique(); // no_surat
            $table->date('letter_date'); // tanggal_surat
            $table->date('received_date')->nullable(); // tanggal_diterima
            $table->string('sender'); // pengirim
            $table->string('subject'); // perihal
            $table->text('summary')->nullable(); // isi_ringkas
            $table->string('primary_file')->nullable(); // file utama scan
            $table->string('archive_external_id')->nullable()->unique(); // arsip_id
            $table->enum('status', ['new', 'disposed', 'followed_up', 'rejected', 'completed', 'archived'])->default('new');
            $table->string('last_disposition')->nullable(); // last_disposisi
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // user yang input
            // Optional extended metadata (future) - kept nullable for now
            $table->string('classification_code', 50)->nullable();
            $table->string('security_level', 30)->nullable(); // contoh: open, restricted, confidential
            $table->string('speed_level', 30)->nullable(); // normal, urgent, very_urgent
            $table->string('origin_agency')->nullable(); // asal_instansi
            $table->string('physical_location')->nullable(); // lokasi_fisik
            $table->timestamp('disposed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedSmallInteger('disposition_count')->default(0);
            $table->string('file_hash', 64)->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('last_disposition');
            $table->index('classification_code');
            $table->index('letter_date');
            $table->index('received_date');
        });

        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_letter_id')->constrained('incoming_letters')->cascadeOnDelete();
            // snapshot sender (from)
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('from_name');
            $table->string('from_nip')->nullable();
            $table->string('from_phone')->nullable();
            // target (to) may be user or unit
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('to_unit_id')->nullable(); // FK planned to unit_kerja table
            $table->string('to_name')->nullable();
            $table->string('to_nip')->nullable();
            $table->string('to_phone')->nullable();
            $table->string('to_unit_name')->nullable();
            $table->text('instruction')->nullable();
            $table->enum('status', ['new', 'sent', 'received', 'rejected', 'followed_up', 'completed'])->default('new');
            $table->text('rejection_reason')->nullable();
            $table->string('template_code')->nullable();
            $table->unsignedSmallInteger('sequence')->default(0); // ordering if multiple dispositions parallel
            $table->enum('channel', ['manual', 'whatsapp', 'email', 'system'])->default('whatsapp');
            $table->string('whatsapp_message_id')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('followed_up_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['incoming_letter_id', 'status']);
            $table->index(['to_user_id', 'status']);
            $table->index('sequence');
            $table->index('channel');
            $table->index('whatsapp_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispositions');
        Schema::dropIfExists('incoming_letters');
    }
};
