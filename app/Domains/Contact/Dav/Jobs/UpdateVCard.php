<?php

namespace App\Domains\Contact\Dav\Jobs;

use App\Domains\Contact\Dav\Services\GetEtag;
use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Interfaces\ServiceInterface;
use App\Services\QueuableService;
use Closure;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Localizable;

class UpdateVCard extends QueuableService implements ServiceInterface
{
    use Batchable, Localizable;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'uri' => 'required|string',
            'etag' => 'nullable|string',
            'card' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! is_string($value) && ! is_resource($value)) {
                        $fail($attribute.' must be a string or a resource.');
                    }
                },
            ],
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
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
     * Update or create a contact using the VCard data.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data): void
    {
        if (! $this->batching()) {
            return;
        }

        $this->validateRules($data);

        $this->withLocale($this->author->preferredLocale(), function () {
            $newtag = $this->updateCard($this->data['uri'], $this->data['card']);

            if (($etag = Arr::get($this->data, 'etag')) !== null && $newtag !== $etag) {
                Log::warning(__CLASS__.' '.__FUNCTION__.' wrong etag when updating contact. Expected '.$etag.', get '.$newtag, [
                    'contacturl' => $this->data['uri'],
                    'carddata' => $this->data['card'],
                ]);
            }
        });
    }

    /**
     * Update the contact with the carddata.
     *
     * @param  string  $cardUri
     * @param  string  $cardData
     * @return string|null
     */
    private function updateCard($cardUri, $cardData): ?string
    {
        $backend = app(CardDAVBackend::class, ['user' => $this->author]);

        $contactId = null;
        if ($cardUri) {
            $contactObject = $backend->getObject($this->vault->uuid, $cardUri);

            if ($contactObject) {
                $contactId = $contactObject->id;
            }
        }

        try {
            $result = app(ImportVCard::class)
                ->execute([
                    'account_id' => $this->author->account_id,
                    'author_id' => $this->author->id,
                    'vault_id' => $this->vault->id,
                    'contact_id' => $contactId,
                    'entry' => $cardData,
                    'etag' => Arr::get($this->data, 'etag'),
                    'behaviour' => ImportVCard::BEHAVIOUR_REPLACE,
                ]);

            if (! Arr::has($result, 'error')) {
                return app(GetEtag::class)->execute([
                    'account_id' => $this->author->account_id,
                    'author_id' => $this->author->id,
                    'vault_id' => $this->vault->id,
                    'contact_id' => $result['contact_id'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                'contacturl' => $cardUri,
                'contact_id' => $contactId,
                'carddata' => $cardData,
                $e,
            ]);
            throw $e;
        }

        return null;
    }
}
