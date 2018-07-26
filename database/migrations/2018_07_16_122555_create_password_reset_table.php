<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePasswordResetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sec.PasswordReset', function (Blueprint $table) {
            $table->integer('UserId')->unsigned();
            $table->string('Uuid');

            $table->foreign('UserId')->references('UserId')->on('sec.Users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sec.PasswordReset');
    }
}
