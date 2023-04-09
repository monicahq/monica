<?php

namespace App\Domains\Settings\ManagePostTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\PostTemplateSection;
use App\Services\BaseService;

class DestroyPostTemplateSection extends BaseService implements ServiceInterface
{
    private PostTemplateSection $postTemplateSection;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'post_template_id' => 'required|integer|exists:post_templates,id',
            'post_template_section_id' => 'required|integer|exists:post_template_sections,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Destroy a post type section.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $postTemplate = $this->account()->postTemplates()
            ->findOrFail($data['post_template_id']);

        $this->postTemplateSection = $postTemplate->postTemplateSections()
            ->findOrFail($data['post_template_section_id']);

        $this->postTemplateSection->delete();
    }
}
