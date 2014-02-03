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
		Schema::create('countries', function(Blueprint $table)
        {
			$table->increments('id');
			$table->string('iso');
            $table->timestamps();
            $table->softDeletes();
		});

        Schema::create('country_translations', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('country_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();

            $table->unique(['country_id','locale']);
            $table->foreign('country_id')->references('id')->on('countries');
        });

	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('country_translations');
		Schema::drop('countries');
	}

}
