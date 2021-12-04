<?php

namespace App\Features\Account\ManageLabels\Services;

use App\Models\User;
use App\Models\Label;
use Illuminate\Support\Str;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class CreateLabel extends BaseService implements ServiceInterface
{
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
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
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Create a label.
     *
     * @param array $data
     * @return Label
     */
    public function execute(array $data): Label
    {
        $this->validateRules($data);

        $label = Label::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'description' => $this->valueOrNull($data, 'description'),
            'slug' => Str::slug($data['name'], '-'),
        ]);

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'label_created',
            'objects' => json_encode([
                'label_name' => $label->name,
            ]),
        ]);

        return $label;
    }
}
