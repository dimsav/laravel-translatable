<?php

use Dimsav\Translatable\Translatable;

class Country extends Translatable {
    public $timestamps = false;
    public $fillable = array('id','iso');
} 