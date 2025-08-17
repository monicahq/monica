<?php

namespace App\Domains\Contact\Dav\Jobs;

use App\Domains\Contact\Dav\Services\GetEtag;
use App\Domains\Contact\Dav\Services\ImportVCard;
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
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'uri' => 'required|string',
            'etag' => 'nullable|string',
            'external' => 'nullable|boolean',
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
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update or create a contact using the VCard data.
     */
    public function execute(array $data): void
    {
        $this->data = $data;

        $this->validateRules($data);

        $this->withLocale($this->author->preferredLocale(), function () {
            $newtag = $this->updateCard($this->data['uri'], $this->data['card']);

            if ($newtag !== null && ($etag = Arr::get($this->data, 'etag')) !== null && $newtag !== $etag) {
                Log::channel('database')->warning(__CLASS__.' '.__FUNCTION__." wrong etag when updating contact. Expected [$etag], got [$newtag]", [
                    'contacturl' => $this->data['uri'],
                    'carddata' => $this->data['card'],
                ]);
            }
        });
    }

    /**
     * Update the contact with the carddata.
     */
    private function updateCard(string $uri, mixed $card): ?string
    {
        try {
            $result = app(ImportVCard::class)->execute([
                'account_id' => $this->author->account_id,
                'author_id' => $this->author->id,
                'vault_id' => $this->vault->id,
                'entry' => $card,
                'etag' => Arr::get($this->data, 'etag'),
                'uri' => $uri,
                'external' => Arr::get($this->data, 'external', false),
                'behaviour' => ImportVCard::BEHAVIOUR_REPLACE,
            ]);

            if (! Arr::has($result, 'error')) {
                return app(GetEtag::class)->execute([
                    'account_id' => $this->author->account_id,
                    'author_id' => $this->author->id,
                    'vault_id' => $this->vault->id,
                    'vcard' => $result['entry'],
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('database')->error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                'uri' => $uri,
                'carddata' => $card,
                $e,
            ]);
            throw $e;
        }

        return null;
    }
}
