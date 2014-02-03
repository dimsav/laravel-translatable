<?php namespace Dimsav\Translatable\Test\Model;

use Dimsav\Translatable\Translatable;

class CountryStrict extends Translatable {

    public $table = 'countries';
    public $fillable = array('iso');
    public $translatedAttributes = array('name');
    public $translationModel = 'Dimsav\Translatable\Test\Model\CountryTranslation';
    public $translationForeignKey = 'country_id';
    protected $softDelete = true;

}