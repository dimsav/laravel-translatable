<?php

namespace Dimsav\Translatable\Test\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;

class CountryTranslationGuarded extends Eloquent
{
    public $timestamps = false;
    public $table = 'country_translations';

    protected $fillable = [];
}
