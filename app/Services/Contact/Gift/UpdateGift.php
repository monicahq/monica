<?php

namespace App\Services\Contact\Gift;

use App\Models\Contact\Gift;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateGift extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'gift_id' => 'required|integer|exists:gifts,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'name' => 'required|string|max:255',
            'status' => [
                'required',
                Rule::in([
                    'idea',
                    'offered',
                    'received',
                ]),
            ],
            'comment' => 'string|max:1000000|nullable',
            'url' => 'string|max:1000000|nullable',
            'amount' => 'numeric|nullable',
            'date' => 'date|nullable',
            'recipient_id' => 'integer|nullable|exists:contacts,id',
        ];
    }

    /**
     * Update a gift.
     *
     * @param  array  $data
     * @return Gift
     */
    public function execute(array $data): Gift
    {
        $this->validate($data);

        $gift = Gift::where('account_id', $data['account_id'])
                    ->findOrFail((int) $data['gift_id']);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $contact->throwInactive();

        if (isset($data['recipient_id'])) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['recipient_id']);
        }

        $array = [
            'contact_id' => $data['contact_id'],
            'name' => $data['name'],
            'status' => $data['status'],
            'comment' => $this->nullOrvalue($data, 'comment'),
            'url' => $this->nullOrvalue($data, 'url'),
            'amount' => $this->nullOrvalue($data, 'amount'),
            'date' => $this->nullOrvalue($data, 'date'),
        ];

        if (Auth::check()) {
            $array['currency_id'] = Auth::user()->currency->id;
        }

        $gift->update($array);

        return tap($gift, function ($gift) use ($data): void {
            $gift->recipient = $this->nullOrvalue($data, 'recipient_id');
            $gift->save();
        });
    }
}
