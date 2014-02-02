<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTables extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('countries', function(Blueprint $table) {
			$table->increments('id');
			$table->string('iso');
		});

        Schema::create('country_translations', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('country_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();

            $table->unique(['country_id','locale']);
            $table->foreign('country_id')->references('id')->on('countries');
        });

        Country::create(array('id'=>1, 'iso'=>'gr'));
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('language_translations');
		Schema::drop('languages');
	}

}
