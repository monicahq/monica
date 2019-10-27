<?php

namespace App\Models;

use App\Interfaces\Hashing;
use App\Traits\Hasher;

abstract class ModelBindingHasher extends ModelBinding implements Hashing
{
    use Hasher;
}
