<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TooltipSystem extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('crud_tooltip', function($table)
        {
            $table->increments('id');
            $table->string('tt_index');
            $table->text('tt_text');
            $table->index('tt_index');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::dropIfExists('crud_tooltip');
    }

}
