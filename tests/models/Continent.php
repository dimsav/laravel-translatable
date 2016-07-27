<?php

namespace Approached\Translatable\Test\Model;

use Approached\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * A test class that has no required properties.
 */
class Continent extends Eloquent
{
    use Translatable;

    public $translatedAttributes = ['name'];
}
