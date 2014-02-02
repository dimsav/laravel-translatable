<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class CountryTranslation extends Eloquent {
    public $timestamps = false;
    public $fillable = array('country_id', 'name', 'locale');
} 