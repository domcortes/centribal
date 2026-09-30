<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo');
            $table->text('detalle')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['solicitud_id', 'user_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_solicitudes');
    }
};
