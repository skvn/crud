<?php

use Illuminate\Database\Migrations\Migration;

class Attach extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema :: create('crud_file', function ($table) {
            $table->increments('id');
            $table->string('file_name');
            $table->string('path');
            $table->string('mime_type');
            $table->string('title');
            $table->integer('created_at');
            $table->integer('updated_at');
            $table->integer('file_size');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
