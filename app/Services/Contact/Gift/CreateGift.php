<?php

namespace App\Services\Contact\Gift;

use App\Models\Contact\Gift;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class CreateGift extends BaseService
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
     * Create a tag.
     *
     * @param array $data
     * @return Gift
     */
    public function execute(array $data): Gift
    {
        $this->validate($data);

        Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        if (isset($data['recipient_id'])) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['recipient_id']);
        }

        $array = [
            'account_id' => $data['account_id'],
            'contact_id' => $data['contact_id'],
            'name' => $data['name'],
            'status' => $data['status'],
            'comment' => $this->nullOrvalue($data, 'comment'),
            'url' => $this->nullOrvalue($data, 'url'),
            'amount' => $this->nullOrvalue($data, 'amount'),
            'date' => $this->nullOrvalue($data, 'date'),
            'recipient' => $this->nullOrvalue($data, 'recipient_id'),
        ];

        if (Auth::check()) {
            $array['currency_id'] = Auth::user()->currency->id;
        }

        return Gift::create($array);
    }
}
