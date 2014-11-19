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

        Schema::create('companies', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('continents', function(Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('continent_translations', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('continent_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();
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

        Schema::dropIfExists('city_translations');
        Schema::dropIfExists('cities');

        Schema::dropIfExists('country_translations');
        Schema::dropIfExists('countries');

        Schema::dropIfExists('companies');

        Schema::dropIfExists('continent_translations');
        Schema::dropIfExists('continents');
	}

}
