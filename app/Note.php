<?php

namespace App;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    /**
     * Get the description of a note.
     * @return string
     */
    public function getBody()
    {
        if (is_null($this->body)) {
            return null;
        }

        return $this->body;
    }

    /**
     * Gets the activity date for this note.
     *
     * @param  string $locale
     * @return string
     */
    public function getCreatedAt($locale)
    {
        return DateHelper::getShortDate($this->created_at, $locale);
    }

    /**
     * Gets the content of the activity and formats it for the email.
     * @return string
     */
    public function getContent()
    {
        return wordwrap($this->getBody(), 75);
    }
}
