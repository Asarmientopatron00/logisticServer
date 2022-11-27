<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedidos_maritimos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('tipo_producto_id')->constrained('tipos_productos_maritimos')->cascadeOnDelete();
            $table->integer('cantidad_producto');
            $table->date('fecha_registro');
            $table->date('fecha_entrega');
            $table->foreignId('puerto_id')->constrained('puertos')->cascadeOnDelete();
            $table->decimal('precio_envio', $precision = 20, $scale = 2);
            $table->decimal('descuento', $precision = 20, $scale = 2);
            $table->foreignId('flota_id')->constrained('flotas')->cascadeOnDelete();
            $table->string('guia')->unique();
            $table->enum('estado', ['P','F'])->default('P');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pedidos_maritimos');
    }
};
