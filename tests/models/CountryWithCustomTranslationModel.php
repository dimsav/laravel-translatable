<?php

namespace Dimsav\Translatable\Test\Model;

use Dimsav\Translatable\Translatable;

class CountryWithCustomTranslationModel extends Country
{
    use Translatable;

    public $table = 'countries';
    public $translationForeignKey = 'country_id';
    public $translationModel = 'Dimsav\Translatable\Test\Model\CountryTranslation';
}
