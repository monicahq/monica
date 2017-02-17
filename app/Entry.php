<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    protected $table = 'entries';

    public function getPost()
    {
        if (is_null($this->post)) {
            return null;
        }

        return decrypt($this->post);
    }

    public function getTitle()
    {
        if (is_null($this->title)) {
            return null;
        }

        return decrypt($this->title);
    }
}
