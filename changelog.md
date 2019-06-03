# Changelog

## v10.0.0
- Add `Dimsav\Translatable\Locales` helper class [#574](https://github.com/dimsav/laravel-translatable/pull/574)
- Fix `getRelationKey()` [#575](https://github.com/dimsav/laravel-translatable/pull/575)

## v9.5.0
- Add `isEmptyTranslatableAttribute()` method to allow custom empty attribute decision logic [#576](https://github.com/dimsav/laravel-translatable/pull/576)

## v9.4.0
- Add Laravel 5.8 support [#550](https://github.com/dimsav/laravel-translatable/pull/550) & [#556](https://github.com/dimsav/laravel-translatable/pull/556)

## v9.3.0
- Fix n+1 queries when updating non-translated model attributes [#533](https://github.com/dimsav/laravel-translatable/pull/533)

## v9.2.0
- Add support for Laravel 5.7 [#518](https://github.com/dimsav/laravel-translatable/pull/518)

## v9.1.0
- Do not call get translation with fallback locale and fallback enabled [#502](https://github.com/dimsav/laravel-translatable/pull/502)
- Allow `translateOrDefault()` and `translateOrNew()` to default to user app locale [#500](https://github.com/dimsav/laravel-translatable/pull/500)
- Change autoload translations behavior on runtime [#501](https://github.com/dimsav/laravel-translatable/pull/501)
- Use fallback in `attributesToArray()` [#503](https://github.com/dimsav/laravel-translatable/pull/503)
- Added `orderByTranslation()` scope [#504](https://github.com/dimsav/laravel-translatable/pull/504)
- Example in doc for locale filtering in `whereTranslation()` scope [#487](https://github.com/dimsav/laravel-translatable/pull/487)
- Fire saving event in every case [#457](https://github.com/dimsav/laravel-translatable/pull/457)
- Allow to change default translation model namespace from config file [#508](https://github.com/dimsav/laravel-translatable/pull/508)

## v9.0.0
- Added support for Laravel 5.6 [#435](https://github.com/dimsav/laravel-translatable/pull/435)

## v8.1.0
- Fixed error when fallback not available. [#422](https://github.com/dimsav/laravel-translatable/pull/422)
- Fixed withTranslation query scope performance [#417](https://github.com/dimsav/laravel-translatable/pull/417)
- Fixed fallback for country-based locales [#417](https://github.com/dimsav/laravel-translatable/pull/417)
- Fixed empty attribute values [#410](https://github.com/dimsav/laravel-translatable/pull/410)

## v8.0.0
- Added support for Laravel 5.5 [#394](https://github.com/dimsav/laravel-translatable/pull/394)

## v7.3.0
- Added compatibility with custom db connections. [#366](https://github.com/dimsav/laravel-translatable/pull/366)

## v7.2.1
- Fixed delete events not fired for translations. [#361](https://github.com/dimsav/laravel-translatable/pull/361)

## v7.2.0
- Added `replicateWithTranslations()`. [#346](https://github.com/dimsav/laravel-translatable/pull/346)
- Added `orWhereTranslation()` and `orWhereTranslationLike()` scopes. [#338](https://github.com/dimsav/laravel-translatable/pull/338)
- Added support for laravel auto-discovery. [#359](https://github.com/dimsav/laravel-translatable/pull/359)
- Added tag for publishing the config file. [#360](https://github.com/dimsav/laravel-translatable/pull/360)

## v7.1.0
- Added fallback per attribute. [#348](https://github.com/dimsav/laravel-translatable/pull/348)
- Added `getTranslationsArray()` [#347](https://github.com/dimsav/laravel-translatable/pull/347)
- Fixed filling 'property:locale' format was not validating the locale. [#356](https://github.com/dimsav/laravel-translatable/pull/356)

## v7.0.0
- Added compatibility with Laravel v5.4.
- Added default locale per model. [#271](https://github.com/dimsav/laravel-translatable/pull/271)

## v6.1.0
- Filling a model now supports using the 'property:locale' format in keys. [#314](https://github.com/dimsav/laravel-translatable/pull/314) For example: `$country->fill(['name:en' => 'Belgium'])`  
- Added config to skip translations in `toArray()` for better performance when needed. [#315](https://github.com/dimsav/laravel-translatable/pull/315)

## v6.0.1
- Fix issue when trying to fetch a translation with a country based locale [#264](https://github.com/dimsav/laravel-translatable/pull/264)

## v6.0.0
- Translated fillable properties should only be defined in the translation model.
  - To update from version 5, move all the fillable properties belonging to a translation to the corresponding translation models. 
- Added `deleteTranslations()` method for conveniently deleting translations

## v5.6.1
- Added support for Lumen without Facades [#259](https://github.com/dimsav/laravel-translatable/pull/259) 
- Added support for Model accessors [#257](https://github.com/dimsav/laravel-translatable/pull/257) 
- Updated code style and added [styleci](https://styleci.io/) to enforce it

## v5.6.0
- Added scope `notTranslatedIn()` [#235](https://github.com/dimsav/laravel-translatable/pull/235)

## v5.5.1
- Fixed a bug in locale fallback on `toArray()`

## v5.5.0
- Added Laravel 5.2 support
- Dropped Laravel 5.0 support
- Added scope `whereTranslationLike()` [#183](https://github.com/dimsav/laravel-translatable/pull/183)
- Fire 'updated' event when saving translations. [#190](https://github.com/dimsav/laravel-translatable/pull/190)
- `setAttribute()` returns the model itself, which is now the default in eloquent. [#201](https://github.com/dimsav/laravel-translatable/issues/201)

## v5.4
- Added compatibility with custom primary key [#174](https://github.com/dimsav/laravel-translatable/issues/174)

## v5.3
- Added `whereTranslation()` scope [#168](https://github.com/dimsav/laravel-translatable/issues/168)

## v5.2
- Added option to override default locale [#158](https://github.com/dimsav/laravel-translatable/issues/158) 
- Added default value in `translatedIn()` scope [#148](https://github.com/dimsav/laravel-translatable/issues/148)
- Added new scope `withTranslation()]` to decrease the number of mysql calls made.
- Added [documentation](https://github.com/dimsav/laravel-translatable/blob/0715f46613769570b65b97ac9ffec10f9bf06d8d/readme.md#available-scopes) about scopes.

## v5.1.2
- Fixed db in tests is dropped and recreated to make tests more stable
- Fixed bug when using syntax `$country->{'name:en'}` and locale doesn't exist [#150](https://github.com/dimsav/laravel-translatable/issues/150)
- Method isTranslationAttribute() is now public [#151](https://github.com/dimsav/laravel-translatable/issues/151)

## v5.1.1
- Fixed compatibility with Lumen [#121](https://github.com/dimsav/laravel-translatable/issues/121)
- Fixed making an attribute on a translatable model hidden does not hide it [#133](https://github.com/dimsav/laravel-translatable/issues/133)

## v5.1.0
- Added mutator/accessor translations using the format `$country->{'name:de'}` thanks to [@barryvdh](https://github.com/barryvdh)
- Added documentation in readme file

## v5.0.1
- Applied PSR-2 code style.

## v5.0.0
- Laravel 5 ready
- Added configuration option for returning fallback translations

## v4.5.0
- Added scope to list translated attributes in the current locale.
- Force fire "saved" event when the original model is not saved, but the translation is [#85](https://github.com/dimsav/laravel-translatable/issues/85)

## v4.4.0
- Drops support for laravel 4.0.
- Compatible with laravel 4.1 and laravel 4.2.
- External config file.
- Fallback issue fixed.
- Added translated and translatedIn scopes.
- Changed behavior: getting non existing translations with `getTranslation()` used to return objects, now `null` is returned.
- Translated attributes now shown when converting `toArray()` or `toJson()`.
- Fixed bug: `fill()` created empty translations even when translated attributes were not fillable.
- Added option to make translated attributes always fillable.

## v4.3.0
- The `Translation` class suffix default can be overridden in the app config. See [7ecc0a75d](https://github.com/dimsav/laravel-translatable/commit/7ecc0a75dfcec58ebf694e0a7feb686294b49847)
- The `app.fallback_locale` setting can be overridden in each model separately. See [#33](https://github.com/dimsav/laravel-translatable/pull/33)
- Fallback translation is not returned if it is not defined.

## v4.2.0
- Fallback locale now is taken from `app.fallback_locale` config key.

## v4.1.1
- Fixed issue with saving translations, caused by the update of the laravel core.

## v4.1.0
- Added fallback to default locale if translations is missing. [#23](https://github.com/dimsav/laravel-translatable/issues/23)
- Added travis environment for laravel 4.2.

## v4.0.0
- Removed syntax `$model->en->name` because conflicts may happen if the model has a property named `en`. See [#18](https://github.com/dimsav/laravel-translatable/issues/18).
- Added method `hasTranslation($locale)`. See [#19](https://github.com/dimsav/laravel-translatable/issues/19).

## v3.0.0
- Fixed bug [#7](https://github.com/dimsav/laravel-translatable/issues/7). Model's Translations were deleted when the model delete failed.

## v2.0.0
- Translatable is now a trait and can be used as add-on to your models.
- 100% code coverage

## v1.0.0
- Initial version
- Translatable is a class extending Eloquent
- 96% code coverage
