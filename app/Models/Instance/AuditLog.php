<?php

namespace App\Models\Instance;

use App\Models\User\User;
use function Safe\json_decode;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'author_id',
        'about_contact_id',
        'author_name',
        'action',
        'objects',
        'should_appear_on_dashboard',
        'audited_at',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'audited_at',
    ];

    /**
     * Get the Account record associated with the audit log.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the User record associated with the audit log.
     *
     * @return BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Contact record associated with the audit log.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'about_contact_id');
    }

    /**
     * Get the JSON object.
     *
     * @return array
     * @param mixed $value
     */
    public function getObjectAttribute($value)
    {
        return json_decode($this->objects);
    }
}
