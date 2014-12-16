## Version History

### v. 4.4

 * External config file
 * Fallback issue fixed
 * Added translated and translatedIn scopes
 * Changed behavior: getting non existing translations with `getTranslation()` used to return objects, now `null` is returned.
 * Translated attributes now shown when converting toArray() or toJson().
 * Fixed bug: fill() created empty translations even when translated attributes were not fillable
 * Added option to make translated attributes always fillable

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
