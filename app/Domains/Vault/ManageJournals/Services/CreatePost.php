<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Post;
use App\Models\PostSection;
use App\Models\PostTemplate;
use App\Services\BaseService;

class CreatePost extends BaseService implements ServiceInterface
{
    private array $data;

    private Post $post;

    private PostTemplate $postTemplate;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'journal_id' => 'required|integer|exists:journals,id',
            'post_template_id' => 'required|integer|exists:post_templates,id',
            'title' => 'nullable|string|max:255',
            'published' => 'required|boolean',
            'written_at' => 'nullable|date_format:Y-m-d',
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
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Create a post.
     */
    public function execute(array $data): Post
    {
        $this->data = $data;

        $this->validate();
        $this->create();
        $this->createPostSections();

        return $this->post;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->vault->journals()
            ->findOrfail($this->data['journal_id']);

        $this->postTemplate = $this->account()->postTemplates()
            ->findOrFail($this->data['post_template_id']);
    }

    private function create(): void
    {
        if (! is_null($this->data['written_at'])) {
            $writtenAt = $this->data['written_at'];
        } else {
            $writtenAt = now();
        }

        $this->post = Post::create([
            'journal_id' => $this->data['journal_id'],
            'title' => $this->valueOrNull($this->data, 'title'),
            'published' => $this->data['published'],
            'written_at' => $writtenAt,
        ]);
    }

    /**
     * Once the post is created, we also create post sections for this post.
     * The post sections are defined by the post template that was chosen upon
     * the creation of the post
     * All these post sections will be blank until the user fills them.
     */
    private function createPostSections(): void
    {
        $postTemplateSections = $this->postTemplate->postTemplateSections()
            ->orderBy('position')
            ->get();

        foreach ($postTemplateSections as $postTemplateSection) {
            PostSection::create([
                'post_id' => $this->post->id,
                'position' => $postTemplateSection->position,
                'label' => $postTemplateSection->label,
            ]);
        }
    }
}
