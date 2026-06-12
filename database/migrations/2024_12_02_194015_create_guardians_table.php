<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuardiansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->string('nationality'); // Nacionalidad
            $table->string('cedula')->unique();      // Cédula
            $table->string('name');            // Nombre
            $table->string('last_name_1');             // Primer Apellido
            $table->string('last_name_2');      // Segundo Apellido
            $table->boolean('lives_with_student')->default(false); // Vive con el estudiante
            $table->string('company')->nullable();   // Empresa donde labora
            $table->string('company_phone')->nullable(); // Teléfono de la empresa
            $table->string('personal_phone');        // Teléfono personal
            $table->string('email')->nullable();     // Correo electrónico
            $table->string('relationship');          // Parentesco
            $table->string('marital_status');        // Estado civil
            $table->string('education_level')->nullable(); // Escolaridad
            $table->string('ocupation')->nullable(); // Ocupación
            $table->boolean('authorized')->default(false); // Autorizado para trámites
            $table->timestamps();
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
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
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('guardians');
    }
}
