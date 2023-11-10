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
        Schema::create('openaidatas', function (Blueprint $table) {
            $table->id();
            $table->text('user_mail');
            $table->text('question', 2000); // Set the maximum length to 2000 characters
            $table->text('result', 5000); // Set the maximum length to 5000 characters
            $table->timestamps();
            // Optionally, add a foreign key constraint if user_id is associated with the users table
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('openaidatas');
    }
};
