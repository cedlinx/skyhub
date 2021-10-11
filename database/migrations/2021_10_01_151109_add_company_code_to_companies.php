<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyCodeToCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('code')->unique();
            $table->string('email');  //Use the same email used on the users tables... this is what identifies the company's suoeradmin

            //use the roles table to manage company roles while the groups table manages global roles/access control
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('code');
            $table->dropColumn('email');
        });
    }
}
