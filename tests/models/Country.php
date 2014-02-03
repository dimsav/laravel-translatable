<?php namespace Dimsav\Translatable\Test\Model;

use Dimsav\Translatable\Translatable;

class Country extends Translatable {

    public $translatedAttributes = array('name');

    /**
     * Add your translatable attributes here if you want
     * save them with mass assignment
     */
    public $fillable = array('iso','name');
} 