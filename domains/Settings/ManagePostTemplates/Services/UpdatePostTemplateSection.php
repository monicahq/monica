<?php

namespace App\Settings\ManagePostTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use App\Services\BaseService;

class UpdatePostTemplateSection extends BaseService implements ServiceInterface
{
    private PostTemplateSection $postTemplateSection;

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
            'post_template_section_id' => 'required|integer|exists:post_template_sections,id',
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
     * Update a post type section.
     *
     * @param  array  $data
     * @return PostTemplateSection
     */
    public function execute(array $data): PostTemplateSection
    {
        $this->validateRules($data);

        PostTemplate::where('account_id', $data['account_id'])
            ->findOrFail($data['post_template_id']);

        $this->postTemplateSection = PostTemplateSection::where('post_template_id', $data['post_template_id'])
            ->findOrFail($data['post_template_section_id']);

        $this->postTemplateSection->label = $data['label'];
        $this->postTemplateSection->save();

        return $this->postTemplateSection;
    }
}
