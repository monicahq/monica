<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\TemplatePage;
use App\Services\BaseService;
use Illuminate\Support\Str;

class CreateTemplatePage extends BaseService implements ServiceInterface
{
    private TemplatePage $templatePage;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'template_id' => 'required|integer|exists:templates,id',
            'name' => 'nullable|string|max:255',
            'name_translation_key' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'can_be_deleted' => 'nullable|boolean',
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
     * Create a template page.
     */
    public function execute(array $data): TemplatePage
    {
        $this->validateRules($data);

        $template = $this->account()->templates()
            ->findOrFail($data['template_id']);

        // determine the new position of the template page
        $newPosition = $template->pages()
            ->max('position');
        $newPosition++;

        $this->templatePage = TemplatePage::create([
            'template_id' => $data['template_id'],
            'name' => $data['name'] ?? null,
            'name_translation_key' => $data['name_translation_key'] ?? null,
            'slug' => Str::slug($data['name'] ?? $data['name_translation_key'], '-', language: currentLang()),
            'type' => $this->valueOrNull($data, 'type'),
            'position' => $newPosition,
            'can_be_deleted' => $this->valueOrTrue($data, 'can_be_deleted'),
        ]);

        return $this->templatePage;
    }
}
