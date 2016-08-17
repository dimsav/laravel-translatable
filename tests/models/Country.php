<?php

namespace Dimsav\Translatable\Test\Model;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Country extends Eloquent
{
    use Translatable;

    /**
     * Array with the fields translated in the Translation table.
     *
     * @var array
     */
    public $translatedAttributes = ['name'];

    /**
     * Set $translationModel if you want to overwrite the convention
     * for the name of the translation Model. Use full namespace if applied.
     *
     * The convention is to add "Translation" to the name of the class extending Translatable.
     * Example: Country => CountryTranslation
     */
    public $translationModel;

    /**
     * This is the foreign key used to define the translation relationship.
     * Set this if you want to overwrite the laravel default for foreign keys.
     *
     * @var
     */
    public $translationForeignKey;

    /**
     * Add your translated attributes here if you want
     * fill them with mass assignment.
     *
     * @var array
     */
    public $fillable = ['code'];

    /**
     * The database field being used to define the locale parameter in the translation model.
     * Defaults to 'locale'.
     *
     * @var string
     */
    public $localeKey;
}
