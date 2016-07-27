<?php

namespace Approached\Translatable\Test\Model;

use Approached\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Vegetable extends Eloquent
{
    use Translatable;

    protected $primaryKey = 'vegetable_identity';

    public $translatedAttributes = ['name'];
}
