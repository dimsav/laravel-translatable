<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class Country extends Eloquent {
    public $timestamps = false;
    public $fillable = array('id','iso');
} 