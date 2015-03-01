<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrudUserPrefs extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema :: create('crud_user_pref', function($table){
            $table->increments('id');
            $table->integer("user_id");
            $table->integer("type_id");
            $table->string("scope");
            $table->text("pref");
            $table->index("user_id");
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
