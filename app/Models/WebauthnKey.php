<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelWebauthn\Models\WebauthnKey as BaseWebauthnKey;

class WebauthnKey extends BaseWebauthnKey
{
    /**
     * Create a new WebauthnKey instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->mergeFillable(['used_at']);
        $this->setVisible(array_merge($this->getVisible(), ['used_at']));
        $this->mergeCasts(['used_at' => 'datetime']);
    }

    /**
     * Get the user record associated with the key.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
