<?php

namespace App\Services\Contact\Tag;

use App\Models\Contact\Tag;
use App\Services\BaseService;

class CreateTag extends BaseService
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
            'name' => 'required|string',
        ];
    }

    /**
     * Create a tag.
     *
     * @param array $data
     * @return Tag
     */
    public function execute(array $data) : Tag
    {
        $this->validate($data);

        $array = [
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'name_slug' => str_slug($data['name']),
        ];

        return Tag::create($array);
    }
}
