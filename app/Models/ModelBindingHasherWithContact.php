<?php

namespace App\Models;

use App\Interfaces\Hashing;
use App\Traits\Hasher;

abstract class ModelBindingHasherWithContact extends ModelBindingWithContact implements Hashing
{
    use Hasher;
}
