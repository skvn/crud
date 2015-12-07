<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->string('acl_role');
            $table->string('acl_abilities');
            $table->string('first_name');
            $table->string('last_name');

        });


        \App\Model\User::create(
            [
                'email'=>'admin@domain.ru',
                'password'=>'12345',
                'acl_role' => 'root',
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
