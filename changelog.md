## Version History

### v. 8.1

- Fixed error when fallback not available. #422
- Fixed withTranslation query scope performance #417
- Fixed fallback for country-based locales #417
- Fixed empty attribute values #410

### v. 8.0

- Added support for Laravel 5.5 #394

### v. 7.3

- Added compatibility with custom db connections. #366

### v. 7.2.1

- Fixed delete events not fired for translations. #361

### v. 7.2

- Added replicateWithTranslations(). #346
- Added orWhereTranslation and orWhereTranslationLike scopes. #338
- Added support for laravel auto-discovery. #359
- Added tag for publishing the config file. #360

### v. 7.1

- Added fallback per attribute. #348
- Added getTranslationsArray() #347
- Fixed filling 'property:locale' format was not validating the locale. #356

### v. 7

- Added compatibility with Laravel v5.4.
- Added default locale per model. #271

### v. 6.1

- Filling a model now supports using the 'property:locale' format in keys. #314 For example: 
```
$country->fill(['name:en' => 'Belgium']);
```  
- Added config to skip translations in toArray() for better performance when needed. #315

### v. 6.0.1

- Fix issue when trying to fetch a translation with a country based locale #264

### v. 6.0

- Translated fillable properties should only be defined in the translation model.
  - To update from version 5, move all the fillable properties belonging to a translation to the corresponding translation models. 
- Added deleteTranslations() method for conveniently deleting translations

### v. 5.6.1

- Added support for Lumen without Facades #259 
- Added support for Model accessors #257 
- Updated code style and added [styleci](https://styleci.io/) to enforce it

### v. 5.6

- Added scope notTranslatedIn() [#235](https://github.com/dimsav/laravel-translatable/pull/235)

### v. 5.5.1

- Fixed a bug in locale fallback on toArray()

### v. 5.5

- Added Laravel 5.2 support
- Dropped Laravel 5.0 support
- Added scope whereTranslationLike() [#183](https://github.com/dimsav/laravel-translatable/pull/183)
- Fire 'updated' event when saving translations. [#190](https://github.com/dimsav/laravel-translatable/pull/190)
- setAttribute() returns the model itself, which is now the default in eloquent. [#201](https://github.com/dimsav/laravel-translatable/issues/201)

### v. 5.4

- Added compatibility with custom primary key [#174](https://github.com/dimsav/laravel-translatable/issues/174)

### v. 5.3

- Added whereTranslation() scope [#168](https://github.com/dimsav/laravel-translatable/issues/168)

### v. 5.2

- Added option to override default locale [#158](https://github.com/dimsav/laravel-translatable/issues/158) 
- Added default value in translatedIn() scope [#148](https://github.com/dimsav/laravel-translatable/issues/148)
- Added new scope [withTranslation()](https://github.com/dimsav/laravel-translatable/blob/384844af32928e41a09451aded8d5aa490d3c99f/src/Translatable/Translatable.php#L449-L458) (including [tests](https://github.com/dimsav/laravel-translatable/blob/c6c57e5d265a3b3ba2a882f073900fd8300ae5c6/tests/ScopesTest.php#L56-L74)) to decrease the number of mysql calls made.
- Added [documentation](https://github.com/dimsav/laravel-translatable/blob/0715f46613769570b65b97ac9ffec10f9bf06d8d/readme.md#available-scopes) about scopes.

### v. 5.1.2

- Fixed db in tests is dropped and recreated to make tests more stable (https://github.com/dimsav/laravel-translatable/commit/3cc29a21c27726a2d14463b3ec0d55c26487eb58)
- Fixed bug when using syntax `$country->{'name:en'}` and locale doesn't exist [#150](https://github.com/dimsav/laravel-translatable/issues/150)
- Method isTranslationAttribute() is now public [#151](https://github.com/dimsav/laravel-translatable/issues/151)

### v. 5.1.1

- Fixed compatibility with Lumen [#121](https://github.com/dimsav/laravel-translatable/issues/121)
- Fixed making an attribute on a translatable model hidden does not hide it [#133](https://github.com/dimsav/laravel-translatable/issues/133)

### v. 5.1

- Added mutator/accessor translations using the format `$country->{'name:de'}` thanks to @barryvdh 
- Added documentation in readme file

### v. 5.0.1

- Applied PSR-2 code style.

### v. 5.0

- Laravel 5 ready
- Added configuration option for returning fallback translations

### v. 4.5

- Added scope to list translated attributes in the current locale.
- Force fire "saved" event when the original model is not saved, but the translation is [#85](https://github.com/dimsav/laravel-translatable/issues/85)

### v. 4.4

- Drops support for laravel 4.0.
- Compatible with laravel 4.1 and laravel 4.2.
- External config file.
- Fallback issue fixed.
- Added translated and translatedIn scopes.
- Changed behavior: getting non existing translations with `getTranslation()` used to return objects, now `null` is returned.
- Translated attributes now shown when converting `toArray()` or `toJson()`.
- Fixed bug: fill() created empty translations even when translated attributes were not fillable.
- Added option to make translated attributes always fillable.

### v. 4.3

- The `Translation` class suffix default can be overridden in the app config. See [7ecc0a75d](https://github.com/dimsav/laravel-translatable/commit/7ecc0a75dfcec58ebf694e0a7feb686294b49847)
- The `app.fallback_locale` setting can be overridden in each model separately. See [#33](https://github.com/dimsav/laravel-translatable/pull/33)
- Fallback translation is not returned if it is not defined.

### v. 4.2

- Fallback locale now is taken from `app.fallback_locale` config key.

### v. 4.1.1

- Fixed issue with saving translations, caused by the update of the laravel core.

### v. 4.1
- Added [fallback](https://github.com/dimsav/laravel-translatable/issues/23) to default locale if translations is missing.
- Added travis environment for laravel 4.2.

### v. 4.0
- Removed syntax `$model->en->name` because conflicts may happen if the model has a property named `en`. See [#18](https://github.com/dimsav/laravel-translatable/issues/18).
- Added method `hasTranslation($locale)`. See [#19](https://github.com/dimsav/laravel-translatable/issues/19).

### v. 3.0
- Fixed bug [#7](https://github.com/dimsav/laravel-translatable/issues/7). Model's Translations were deleted when the model delete failed.

### v. 2.0
- Translatable is now a trait and can be used as add-on to your models.
- 100% code coverage

### v. 1.0
- Initial version
- Translatable is a class extending Eloquent
- 96% code coverage
