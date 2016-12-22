<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('country_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('country_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();

            $table->unique(['country_id', 'locale']);
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('country_id')->unsigned();
            $table->timestamps();

            $table->foreign('country_id')->references('id')->on('countries');
        });

        Schema::create('city_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('city_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();

            $table->unique(['city_id', 'locale']);
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('continents', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('foods', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('food_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('food_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();

            $table->unique(['food_id', 'locale']);
            $table->foreign('food_id')->references('id')->on('foods')->onDelete('cascade');
        });

        Schema::create('continent_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('continent_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();
            $table->timestamps();
        });

        Schema::create('vegetables', function (Blueprint $table) {
            $table->increments('identity');
            $table->timestamps();
        });

        Schema::create('vegetable_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('vegetable_identity')->unsigned();
            $table->string('name');
            $table->string('locale')->index();

            $table->unique(['vegetable_identity', 'locale']);
            $table->foreign('vegetable_identity')->references('identity')->on('vegetables')->onDelete('cascade');
        });

        Schema::create('people', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('person_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('person_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();

            $table->unique(['person_id', 'locale']);
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
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
        Schema::dropIfExists('food_translations');
        Schema::dropIfExists('foods');
        Schema::dropIfExists('vegetable_translations');
        Schema::dropIfExists('vegetables');
        Schema::dropIfExists('person_translations');
        Schema::dropIfExists('people');
    }
}
