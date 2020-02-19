<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

abstract class CachableModel extends Model
{
    use Cachable;
}
