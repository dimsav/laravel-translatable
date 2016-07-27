<?php

namespace Approached\Translatable\Test\Model;

use Approached\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class CountryGuarded extends Eloquent
{
    use Translatable;

    public $table = 'countries';
    protected $fillable = [];
    protected $guarded = ['*'];

    public $translatedAttributes = ['name'];

    public $translationModel = 'Approached\Translatable\Test\Model\CountryTranslation';
    public $translationForeignKey = 'country_id';
}
