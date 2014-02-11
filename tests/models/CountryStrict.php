<?php namespace Dimsav\Translatable\Test\Model;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class CountryStrict extends Eloquent {

    use Translatable;

    /**
     * @var array Attributes of the translation object
     */
    public $translatedAttributes = array('name');

    /**
     * @var string Class containing the translation
     */
    public $translationModel = 'Dimsav\Translatable\Test\Model\CountryTranslation';

    /**
     * @var string Foreign key for the translation relationship
     */
    public $translationForeignKey = 'country_id';

    /**
     * Column containing the locale in the translation table.
     * Defaults to 'locale'
     *
     * @var string
     */
    public $localeKey = 'locale';

    public $table = 'countries';

    /**
     * Add your translated attributes here if you want
     * fill them with mass assignment
     *
     * @var array
     */
    public $fillable = array('iso');

    protected $softDelete = true;

}