<?php

namespace App\Domains\Contact\Dav\Services;

use App\Domains\Contact\Dav\VCalendarResource;
use App\Domains\Contact\Dav\VCardResource;
use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetEtag extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'vcard' => 'required_if:vcalendar,null',
            'vcalendar' => 'required_if:vcard,null',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
        ];
    }

    /**
     * Export etag of the VCard.
     */
    public function execute(array $data): string
    {
        $this->validateRules($data);

        if (isset($data['vcalendar'])) {
            $entry = $data['vcalendar'];
            if (! $entry instanceof VCalendarResource) {
                throw new ModelNotFoundException;
            }

            if ($entry->contact->vault_id !== $this->vault->id) {
                throw new ModelNotFoundException;
            }

            return $entry->distant_etag ?? '"'.hash('sha256', $entry->vcalendar).'"';
        } elseif (isset($data['vcard'])) {
            $entry = $data['vcard'];
            if (! $entry instanceof VCardResource) {
                throw new ModelNotFoundException;
            }

            if ($entry->vault_id != $this->vault->id) {
                throw new ModelNotFoundException;
            }

            return $entry->distant_etag ?? '"'.hash('sha256', $entry->vcard).'"';
        } else {
            throw new ModelNotFoundException;
        }
    }
}
