<?php

namespace App;

use Auth;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $dates = ['completed_at'];

    /**
     * Get the account record associated with the task.
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress(Builder $query)
    {
        return $query->where('status', 'inprogress');
    }

    public function getTitle()
    {
        if (is_null($this->title)) {
            return null;
        }

        return $this->title;
    }

    public function getDescription()
    {
        if (is_null($this->description)) {
            return null;
        }

        return $this->description;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Change the status of the task from in progress to complete, or the other
     * way around.
     */
    public function toggle()
    {
        if ($this->status == 'completed') {
            $this->status = 'inprogress';
        } else {
            $this->status = 'completed';
        }
        $this->save();
    }
}
