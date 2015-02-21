<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Notify extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('crud_notify', function($table)
        {
            $table->increments('id');
            $table->string('message');
            $table->integer('target_user_id');
            $table->integer('ttl')->default(0);
            $table->tinyInteger('broadcast')->default(0);
            $table->tinyInteger('delivered')->default(0);
            $table->integer('created_by');
            $table->integer('created_at');
            $table->integer('updated_at');
            $table->integer('delivered_at');
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
