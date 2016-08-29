<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class HistoryTrack extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crud_history_track', function (Blueprint $table) {
            $table->increments('id');
            $table->string('model');
            $table->integer('ref_id');
            $table->integer('modified_by');
            $table->integer('date_modified');
            $table->string('field_name');
            $table->text('field_old_value');
            $table->text('field_value');
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
