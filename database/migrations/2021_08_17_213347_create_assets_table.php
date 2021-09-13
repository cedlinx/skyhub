<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('skydahid')->unique()->nullable(); //should this (Skydah generated code) be nullable?
            $table->string('assetid'); //asset's oem unique identifier
            $table->integer('user_id');
            $table->integer('type_id'); //asset type: documents, vehicles, phones  & computers, electronics, etc
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
}
