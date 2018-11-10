<?php

namespace App\Services\Contact\Tag;

use App\Models\Contact\Tag;
use App\Services\BaseService;

class DestroyTag extends BaseService
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
            'tag_id' => 'required|integer',
        ];
    }

    /**
     * Destroy a tag.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $tag = Tag::where('account_id', $data['account_id'])
            ->findOrFail($data['tag_id']);

        $tag->contacts()->detach();

        $tag->delete();

        return true;
    }
}
