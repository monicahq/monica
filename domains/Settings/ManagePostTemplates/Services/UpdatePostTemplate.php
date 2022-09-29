<?php

namespace App\Settings\ManagePostTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\PostTemplate;
use App\Services\BaseService;

class UpdatePostTemplate extends BaseService implements ServiceInterface
{
    private PostTemplate $postTemplate;

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
            'post_template_id' => 'required|integer|exists:post_templates,id',
            'label' => 'required|string|max:255',
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
     * Update a post type.
     *
     * @param  array  $data
     * @return PostTemplate
     */
    public function execute(array $data): PostTemplate
    {
        $this->validateRules($data);

        $this->postTemplate = PostTemplate::where('account_id', $data['account_id'])
            ->findOrFail($data['post_template_id']);

        $this->postTemplate->label = $data['label'];
        $this->postTemplate->save();

        return $this->postTemplate;
    }
}
