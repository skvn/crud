<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ModUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('acl_role');
            $table->string('acl_abilities');
            $table->string('first_name');
            $table->string('last_name');
            $table->dropColumn('name');
        });


/*        \App\Model\User::create(
            [
                'email'=>'admin@domain.ru',
                'password'=>'12345',
                'acl_role' => 'root',
            ]
        );
*/
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
