Laravel-Translatable
====================

A Laravel package for translatable models.
This package offers easy management of models containing attributes in many languages.

* [Demo](#what-is-this-package-doing)
* [Installation](#installation)
* [Laravel versions](#laravel-versions)


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
    'en'  => array('name'=>'Greece'),
    'fr'  => array('name'=>'Grèce'),
  );

  $country = Country::create($data);
  
  echo $country->fr->name; // Grèce
```

Please note that deleting an instance will delete the translations, while soft-deleting the instance will not delete the translations.

## Installation

Todo

## Laravel versions

Both Laravel versions `4.0` and `4.1` play nice with the package.

