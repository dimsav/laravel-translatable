Laravel-Translatable
====================

[![Total Downloads](https://poser.pugx.org/dimsav/laravel-translatable/downloads.svg)](https://packagist.org/packages/dimsav/laravel-translatable)
[![Build Status](https://travis-ci.org/dimsav/laravel-translatable.svg?branch=v4.3)](https://travis-ci.org/dimsav/laravel-translatable)
[![Code Coverage](https://scrutinizer-ci.com/g/dimsav/laravel-translatable/badges/coverage.png?s=da6f88287610ff41bbfaf1cd47119f4333040e88)](https://scrutinizer-ci.com/g/dimsav/laravel-translatable/)
[![Latest Stable Version](http://img.shields.io/packagist/v/dimsav/laravel-translatable.svg)](https://packagist.org/packages/dimsav/laravel-translatable)
[![License](https://poser.pugx.org/dimsav/laravel-translatable/license.svg)](https://packagist.org/packages/dimsav/laravel-translatable)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/c105358a-3211-47e8-b662-94aa98d1eeee/mini.png)](https://insight.sensiolabs.com/projects/c105358a-3211-47e8-b662-94aa98d1eeee)

This is a Laravel package for translatable models. Its goal is to remove the complexity in retrieving and storing multilingual model instances. With this package you write less code, as the translations are being fetched/saved when you fetch/save your instance.

If you want to store translations of your models into the database, this package is for you.

* [Demo](#what-is-this-package-doing)
* [Installation](#installation-in-4-steps)
* [Support](#support)
* [FAQ](#faq)

## Laravel compatibility

 Laravel  | Translatable
:---------|:----------
 5.0.x    | 5.x
 4.2.x    | 4.4.x
 4.1.x    | 4.4.x
 4.0.x    | 4.3.x


## Demo

Getting translated attributes

```php
  $country = Country::where('code', '=', 'gr')->first();
  echo $country->translate('en')->name; // Greece
  
  App::setLocale('en');
  echo $country->name;     // Greece

  App::setLocale('de');
  echo $country->name;     // Griechenland
```

Saving translated attributes

```php
  $country = Country::where('code', '=', 'gr')->first();
  echo $country->translate('en')->name; // Greece
  
  $country->translate('en')->name = 'abc';
  $country->save();
  
  $country = Country::where('code', '=', 'gr')->first();
  echo $country->translate('en')->name; // abc
```

Filling multiple translations

```php
  $data = array(
    'code' => 'gr',
    'en'  => array('name' => 'Greece'),
    'fr'  => array('name' => 'Grèce'),
  );

  $country = Country::create($data);
  
  echo $country->translate('fr')->name; // Grèce
```

## Installation in 4 steps

### Step 1: Install package

Add the package in your composer.json by executing the command.

```bash
composer require dimsav/laravel-translatable
```

Next, add the service provider to `app/config/app.php`

```
'Dimsav\Translatable\TranslatableServiceProvider',
```

### Step 2: Migrations

In this example, we want to translate the model `Country`. We will need an extra table `country_translations`:

```php
Schema::create('countries', function(Blueprint $table)
{
    $table->increments('id');
    $table->string('code');
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

### Step 3: Models

1. The translatable model `Country` should [use the trait](http://www.sitepoint.com/using-traits-in-php-5-4/) `Dimsav\Translatable\Translatable`. 
2. The convention for the translation model is `CountryTranslation`.


```php
// models/Country.php
class Country extends Eloquent {
    
    use \Dimsav\Translatable\Translatable;
    
    public $translatedAttributes = array('name');
    protected $fillable = ['code', 'name'];

}

// models/CountryTranslation.php
class CountryTranslation extends Eloquent {

    public $timestamps = false;
    protected $fillable = ['name'];

}
```

The array `$translatedAttributes` contains the names of the fields being translated in the "Translation" model.

### Step 4: Configuration

Laravel 4.*
```bash
php artisan config:publish dimsav/laravel-translatable
```

Laravel 5.*
```bash
php artisan vendor:publish 
```

With this command, initialize the configuration and modify the created file, located under `app/config/packages/dimsav/laravel-translatable/translatable.php`.

*Note: There isn't any restriction for the format of the locales. Feel free to use whatever suits you better, like "eng" instead of "en", or "el" instead of "gr".  The important is to define your locales and stick to them.*


## FAQ

#### I need help!

Got any question or suggestion? Feel free to open an [Issue](https://github.com/dimsav/laravel-translatable/issues/new).

#### I want to help!

You are awesome! Watched the repo and reply to the issues. You will help offering a great experience to the users of the package. `#communityWorks`

#### Is this compatible with Ardent?

Translatable is fully compatible with all kinds of Eloquent extensions, including Ardent. If you need help to implement Translatable with these extensions, see this [example](https://gist.github.com/dimsav/9659552).

#### Why do I get a mysql error while running the migrations?

If you see the following mysql error:

```
[Illuminate\Database\QueryException]
SQLSTATE[HY000]: General error: 1005 Can't create table 'my_database.#sql-455_63'
  (errno: 150) (SQL: alter table `country_translations` 
  add constraint country_translations_country_id_foreign foreign key (`country_id`) 
  references `countries` (`id`) on delete cascade)
```

Then your tables have the MyISAM engine which doesn't allow foreign key constraints. MyISAM was the default engine for mysql versions older than 5.5. Since [version 5.5](http://dev.mysql.com/doc/refman/5.5/en/innodb-default-se.html), tables are created using the InnoDB storage engine by default.

##### How to fix

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

#### Can I use translation fallbacks?

If you want to fallback to a default translation if a translation has not been found, you can specify that on the model using `$model->useTranslationFallback = true`.

```php
App::make('config')->set('translatable.fallback_locale', 'en');

$country = Country::create(['code' => 'gr']);
$country->translate('en')->name = 'Greece';
$country->useTranslationFallback = true;

$country->translate('de')->locale; // en
$country->translate('de')->name; // Greece
```

You can also overwrite `useTranslationFallback` with a second parameter on `translate()`, so new translations can be created or existing ones used. Without the second parameter, `translate('de')` would return the fallback translation.

```php
App::make('config')->set('translatable.fallback_locale', 'en');

$country = Country::create(['code' => 'gr']);
$country->useTranslationFallback = true;
$country->translate('en', false)->name = 'Greece';
$country->translate('de', false)->name = 'Griechenland';

$country->translate('de')->locale; // de
$country->translate('de')->name; // Griechenland
```

When using `fill`, this is done automatically for you:

```php
$country = new Country;
$country->useTranslationFallback = true;
$country->fill([
  'code' => 'gr',
  'en' => ['name' => 'Greece'],
  'de' => ['name' => 'Griechenland'],
]);

$country->translate('de')->locale; // de
$country->translate('de')->name; // Griechenland
```