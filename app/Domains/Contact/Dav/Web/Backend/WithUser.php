<?php

namespace App\Domains\Contact\Dav\Web\Backend;

use App\Models\User;

trait WithUser
{
    protected User $user;

    public function withUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
