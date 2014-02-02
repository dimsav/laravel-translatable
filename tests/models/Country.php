<?php namespace Dimsav\Translatable\Test\Model;

use Dimsav\Translatable\Translatable;

class Country extends Translatable {
    public $timestamps = false;
    public $fillable = array('id','iso');
    public $translatedAttributes = array('name');
} 