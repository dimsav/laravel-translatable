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
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });

        Schema::create('cities', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('country_id')->unsigned();
            $table->timestamps();

            $table->foreign('country_id')->references('id')->on('countries');
        });

        Schema::create('city_translations', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('city_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();

            $table->unique(['city_id', 'locale']);
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });

	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('city_translations');
        Schema::drop('cities');
        Schema::drop('country_translations');
        Schema::drop('countries');
	}

}
