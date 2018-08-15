<?php

namespace App\Models;

use App\Traits\Hasher;
use App\Interfaces\Hashing;

abstract class ModelBindingHasherWithContact extends ModelBindingWithContact implements Hashing
{
    use Hasher;
}
