<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('users');
            $table->foreignId('agente_id')->nullable()->constrained('users');
            $table->string('asunto');
            $table->text('descripcion');
            $table->enum('prioridad', ['baja', 'media', 'alta']);
            $table->enum('estado', ['abierta', 'en_progreso', 'resuelta', 'cerrada', 'reabierta'])
                ->default('abierta');
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'idempotency_key']);
            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
