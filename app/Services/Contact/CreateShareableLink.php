<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact;

use Carbon\Carbon;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateShareableLink extends BaseService
{
    private $structure = [
        'account_id',
        'contact_id',
    ];

    /**
     * Create a link that can be sent to an external person so he can update
     * the contact information.
     * The link expires 48h after creation.
     *
     * @param array $data
     * @return string
     */
    public function execute(array $data): string
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new \Exception('Missing parameters');
        }

        try {
            $contact = Contact::where('account_id', $data['account_id'])
                        ->where($data['contact_id']);
        } catch (ModelNotFoundException $e) {
            throw $e;
        }

        $link = Str::random(240);
        $contact->shareable_link = $link;
        $contact->share_expire_at = Carbon::now()->addDays(2);
        $contact->save();

        return $link;
    }
}
