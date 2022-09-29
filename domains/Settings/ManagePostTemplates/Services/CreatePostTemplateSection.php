<?php

namespace App\Settings\ManagePostTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use App\Models\Template;
use App\Services\BaseService;

class CreatePostTemplateSection extends BaseService implements ServiceInterface
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
            'label' => 'required|string|max:255',
            'can_be_deleted' => 'required|boolean',
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
     * Create a post type section.
     *
     * @param  array  $data
     * @return PostTemplateSection
     */
    public function execute(array $data): PostTemplateSection
    {
        $this->validateRules($data);

        PostTemplate::where('account_id', $data['account_id'])
            ->where('id', $data['post_template_id'])
            ->firstOrFail();

        // determine the new position of the template page
        $newPosition = PostTemplateSection::where('post_template_id', $data['post_template_id'])
            ->max('position');
        $newPosition++;

        $this->postTemplateSection = PostTemplateSection::create([
            'post_template_id' => $data['post_template_id'],
            'label' => $data['label'],
            'position' => $newPosition,
            'can_be_deleted' => $this->valueOrTrue($data, 'can_be_deleted'),
        ]);

        return $this->postTemplateSection;
    }
}
