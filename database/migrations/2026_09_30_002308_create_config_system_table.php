<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_system', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->string('valor');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        DB::table('config_system')->insert([
            [
                'clave' => 'asignacion.modo',
                'valor' => 'automatica',
                'descripcion' => 'Modo de asignación de agentes: automatica o manual.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'asignacion.max_tickets_concurrentes',
                'valor' => '5',
                'descripcion' => 'Máximo de solicitudes activas simultáneas antes de considerar a un agente ocupado.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'estado.permitir_modificar_ticket_ajeno',
                'valor' => 'true',
                'descripcion' => 'Si un agente puede cambiar el estado de una solicitud asignada a otro agente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('config_system');
    }
};
