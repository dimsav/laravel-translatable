Laravel-Translatable
====================

[![Latest Stable Version](http://img.shields.io/packagist/v/dimsav/laravel-translatable.svg)](https://packagist.org/packages/dimsav/laravel-translatable)
[![Build Status](https://travis-ci.org/dimsav/laravel-translatable.svg?branch=master)](https://travis-ci.org/dimsav/laravel-translatable)
[![Code Coverage](https://scrutinizer-ci.com/g/dimsav/laravel-translatable/badges/coverage.png?s=da6f88287610ff41bbfaf1cd47119f4333040e88)](https://scrutinizer-ci.com/g/dimsav/laravel-translatable/)
[![License](https://poser.pugx.org/dimsav/laravel-translatable/license.svg)](https://packagist.org/packages/dimsav/laravel-translatable)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/c105358a-3211-47e8-b662-94aa98d1eeee/mini.png)](https://insight.sensiolabs.com/projects/c105358a-3211-47e8-b662-94aa98d1eeee)

This is a Laravel 4 package for translatable models. Its goal is to remove the complexity in retrieving and storing multilingual model instances. With this package you write less code, as the translations are being fetched/saved when you fetch/save your instance.

If you want to store translations of your models into the database, this package is for you.

* [Demo](#what-is-this-package-doing)
* [Installation](#installation-in-4-steps)
* [Laravel versions](#laravel-versions)
* [Support](#support)
* [FAQ](#faq)
* [Version History](#version-history)


## Demo

Getting translated attributes

```php
  $country = Country::where('iso', '=', 'gr')->first();
  echo $country->translate('en')->name; // Greece
  
  App::setLocale('en');
  echo $country->name;     // Greece

  App::setLocale('de');
  echo $country->name;     // Griechenland
```

Saving translated attributes

```php
  $country = Country::where('iso', '=', 'gr')->first();
  echo $country->translate('en')->name; // Greece
  
  $country->translate('en')->name = 'abc';
  $country->save();
  
  $country = Country::where('iso', '=', 'gr')->first();
  echo $country->translate('en')->name; // abc
```

Filling multiple translations

```php
  $data = array(
    'iso' => 'gr',
    'en'  => array('name' => 'Greece'),
    'fr'  => array('name' => 'Grèce'),
  );

  $country = Country::create($data);
  
  echo $country->translate('fr')->name; // Grèce
```

## Installation in 4 steps

### Step 1

Add the package in your composer.json file and run `composer update`.

```json
{
    "require": {
        "dimsav/laravel-translatable": "4.3.*"
    }
}
```

### Step 2

Let's say you have a model `Country`. To save the translations of countries you need one extra table `country_translations`.

Create your migrations:

```php
Schema::create('countries', function(Blueprint $table)
{
    $table->increments('id');
    $table->string('iso');
    $table->timestamps();
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
```

### Step 3

The models:

1. The translatable model `Country` should [use the trait](http://www.sitepoint.com/using-traits-in-php-5-4/) `Dimsav\Translatable\Translatable`. 
2. The convention for the translation model is `CountryTranslation`.


```php
// models/Country.php
class Country extends Eloquent {
    
    use \Dimsav\Translatable\Translatable;
    
    public $translatedAttributes = array('name');
    protected $fillable = ['iso', 'name'];

}

// models/CountryTranslation.php
class CountryTranslation extends Eloquent {

    public $timestamps = false;
    protected $fillable = ['name'];

}
```

The array `$translatedAttributes` contains the names of the fields being translated in the "Translation" model.

### Step 4

Optionally, edit the default locale.

```php
// app/config/app.php

return array(

  // Just enter this array somewhere near your default locale
  'locales' => array('en', 'fr', 'es'),

  // The default locale
  'locale' => 'en',

  // Override the default 'Translation' class suffix
  // to use CountryTrans instead of CountryTranslation
  'translatable_suffix' => 'Trans'

);
```

*Note: There isn't any restriction for the format of the locales. Feel free to use whatever suits you better, like "eng" instead of "en", or "el" instead of "gr".  The important is to define your locales and stick to them till the end.*


## Laravel versions

Laravel versions `4.0`, `4.1` and `4.2` play nice with the package.

## FAQ

### I need help!

Got any question or suggestion? Feel free to open an [Issue](https://github.com/dimsav/laravel-translatable/issues/new).

### I want to help!

You are awesome! Watched the repo and reply to the issues. You will help offering a great experience to the users of the package. `#communityWorks`

### Is this compatible with Ardent?

Translatable is fully compatible with all kinds of Eloquent extensions, including Ardent. If you need help to implement Translatable with these extensions, see this [example](https://gist.github.com/dimsav/9659552).

### Why do I get a mysql error while running the migrations?

If you see the following mysql error:

```
[Illuminate\Database\QueryException]
SQLSTATE[HY000]: General error: 1005 Can't create table 'my_database.#sql-455_63'
  (errno: 150) (SQL: alter table `country_translations` 
  add constraint country_translations_country_id_foreign foreign key (`country_id`) 
  references `countries` (`id`) on delete cascade)
```

Then your tables have the MyISAM engine which doesn't allow foreign key constraints. MyISAM was the default engine for mysql versions older than 5.5. Since [version 5.5](http://dev.mysql.com/doc/refman/5.5/en/innodb-default-se.html), tables are created using the InnoDB storage engine by default.

#### How to fix

For tables already created in production, update your migrations to change the engine of the table before adding the foreign key constraint.

```php
public function up()
{
	DB::statement('ALTER TABLE countries ENGINE=InnoDB');
}

public function down()
{
  DB::statement('ALTER TABLE countries ENGINE=MyISAM');
}
```

For new tables, a quick solution is to set the storage engine in the migration:

```php
Schema::create('language_translations', function(Blueprint $table){
  $table->engine = 'InnoDB';
  $table->increments('id');
	// ...
});
```

The best solution though would be to update your mysql version. And **always make sure you have the same version both in development and production environment!**


## Version History

### v. 4.3

* The `Translation` class suffix default can be overridden in the app config. See [7ecc0a75d](https://github.com/dimsav/laravel-translatable/commit/7ecc0a75dfcec58ebf694e0a7feb686294b49847)
* The `app.fallback_locale` setting can be overridden in each model separately. See [#33](https://github.com/dimsav/laravel-translatable/pull/33)
* Fallback translation is not returned if it is not defined.

### v. 4.2

* Fallback locale now is taken from `app.fallback_locale` config key.

### v. 4.1.1

* Fixed issue with saving translations, caused by the update of the laravel core.

### v. 4.1
* Added [fallback](https://github.com/dimsav/laravel-translatable/issues/23) to default locale if translations is missing.
* Added travis environment for laravel 4.2.

### v. 4.0
* Removed syntax `$model->en->name` because conflicts may happen if the model has a property named `en`. See [#18](https://github.com/dimsav/laravel-translatable/issues/18).
* Added method `hasTranslation($locale)`. See [#19](https://github.com/dimsav/laravel-translatable/issues/19).

### v. 3.0
* Fixed bug #7. Model's Translations were deleted when the model delete failed.

### v. 2.0
* Translatable is now a trait and can be used as add-on to your models.
* 100% code coverage

### v. 1.0
* Initial version
* Translatable is a class extending Eloquent
* 96% code coverage
