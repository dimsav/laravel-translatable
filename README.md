Laravel-Translatable (beta)
====================

[![Build Status](https://travis-ci.org/dimsav/laravel-translatable.png?branch=master)](https://travis-ci.org/dimsav/laravel-translatable)

A Laravel package for translatable models.
This package offers easy management of models containing attributes in many languages.

* [Demo](#what-is-this-package-doing)
* [Installation](#installation-in-4-steps)
* [Laravel versions](#laravel-versions)
* [Support](#support)


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
        "dimsav/laravel-translatable": "1.*@beta"
    },
}

```

*Note: There is not a stable version released yet. Thanks for testing!*


### Step 2

To save the translations of countries you need two models and two tables.

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

The translatable model `Country` should extend `Dimsav\Translatable\Translatable`. The convention for the translation model is `CountryTranslation`.


```php
// models/Country.php
class Country extends \Dimsav\Translatable\Translatable {
    
    public $translatedAttributes = array('name');

}

// models/CountryTranslation.php
class CountryTranslation extends Eloquent {

    public $timestamps = false;

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
  
)

```

*Note: There isn't any restriction for the format of the locales. Feel free to use whatever suits you better, like "eng" instead of "en", or "el" instead of "gr".  The important is to define your locales and stick to them till the end.*


## Laravel versions

Both Laravel versions `4.0` and `4.1` play nice with the package.

## Support

Got any question or suggestion? Feel free to open an [Issue](https://github.com/dimsav/laravel-translatable/issues/new).
