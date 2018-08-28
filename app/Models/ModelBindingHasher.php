<?php

namespace App\Models;

use App\Traits\Hasher;
use App\Interfaces\Hashing;

abstract class ModelBindingHasher extends ModelBinding implements Hashing
{
    use Hasher;
}
