<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->index();  // Identificatore unico del dispositivo
            $table->string('token');               // Token di autenticazione
            $table->dateTime('certified_at')->nullable();  // Data di certificazione
            $table->string('device_name')->nullable();     // Nome del dispositivo (opzionale)
            $table->string('ip_address')->nullable();      // Indirizzo IP (opzionale)
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_tokens');
    }
};
