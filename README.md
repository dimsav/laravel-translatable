Laravel-Translatable
====================

[![Latest Stable Version](https://poser.pugx.org/dimsav/laravel-translatable/v/stable.png)](https://packagist.org/packages/dimsav/laravel-translatable)
[![Build Status](https://travis-ci.org/dimsav/laravel-translatable.png?branch=master)](https://travis-ci.org/dimsav/laravel-translatable)
[![Code Coverage](https://scrutinizer-ci.com/g/dimsav/laravel-translatable/badges/coverage.png?s=da6f88287610ff41bbfaf1cd47119f4333040e88)](https://scrutinizer-ci.com/g/dimsav/laravel-translatable/)
[![License](https://poser.pugx.org/dimsav/laravel-translatable/license.png)](https://packagist.org/packages/dimsav/laravel-translatable)

This is a Laravel 4 package for translatable models. Its goal is to remove the complexity in retrieving and storing multilingual model instances. With this package you write less code, as the translations are being fetched/saved when you fetch/save your instance.

If you want to store translations of your models into the database, this package is for you.

* [Demo](#what-is-this-package-doing)
* [Installation](#installation-in-4-steps)
* [Laravel versions](#laravel-versions)
* [Support](#support)
* [Version History](#version-history)


## Demo

Getting translated attributes

```php
  $country = Country::where('iso', '=', 'gr')->first();
  echo $country->en->name; // Greece
  
  App::setLocale('en');
  echo $country->name;     // Greece

  App::setLocale('de');
  echo $country->name;     // Griechenland
```

Saving translated attributes

```php
  $country = Country::where('iso', '=', 'gr')->first();
  echo $country->en->name; // Greece
  
  $country->en->name = 'abc';
  $country->save();
  
  $country = Country::where('iso', '=', 'gr')->first();
  echo $country->en->name; // abc
```

Filling multiple translations

```php
  $data = array(
    'iso' => 'gr',
    'en'  => array('name' => 'Greece'),
    'fr'  => array('name' => 'Grèce'),
  );

  $country = Country::create($data);
  
  echo $country->fr->name; // Grèce
```

Please note that deleting an instance will delete the translations, while soft-deleting the instance will not delete the translations.

## Installation in 4 steps

### Step 1

Add the package in your composer.json file and run `composer update`.

```json
{
    "require": {
        "dimsav/laravel-translatable": "1.*"
    }
}
```

### Step 2

Let's say you have a model `Country`. To save the translations of countries you need in total two model classes and two tables.

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
    $table->foreign('country_id')->references('id')->on('countries');
});
```

### Step 3

The models:

1. The translatable model `Country` should use the trait `Dimsav\Translatable\Translatable`. 
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

Finally, you have to inform the package about the languages you plan to use for translation. This allows us to use the syntax `$country->es->name`. 

```php
// app/config/app.php

return array(

  // Just enter this array somewhere near your default locale
  'locales' => array('en', 'fr', 'es'),
  
  // The default locale
  'locale' => 'en',
  
);
```

*Note: There isn't any restriction for the format of the locales. Feel free to use whatever suits you better, like "eng" instead of "en", or "el" instead of "gr".  The important is to define your locales and stick to them till the end.*


## Laravel versions

Both Laravel versions `4.0` and `4.1` play nice with the package.

## Support

Got any question or suggestion? Feel free to open an [Issue](https://github.com/dimsav/laravel-translatable/issues/new).

## Version History

### v. 2.0
* Translatable is now a trait and can be used as add-on to your models.
* 100% code coverage

### v. 1.0
* Initial version
* Translatable is a class extending Eloquent
* 96% code coverage