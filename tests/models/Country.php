<?php namespace Dimsav\Translatable\Test\Model;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Country extends Eloquent {

    use Translatable;

    public $translatedAttributes = array('name');

    /**
     * Add your translated attributes here if you want
     * fill them with mass assignment
     *
     * @var array
     */
    public $fillable = array('iso','name');

    /**
     * Column containing the locale in the translation table.
     * Defaults to 'locale'
     *
     * @var string
     */
    public $localeKey = 'locale';

}