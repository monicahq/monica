<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\BaseService;

class CreateTemplate extends BaseService implements ServiceInterface
{
    private Template $template;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'name' => 'nullable|string|max:255',
            'name_translation_key' => 'nullable|string|max:255',
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
     * Create a template.
     */
    public function execute(array $data): Template
    {
        $this->validateRules($data);

        $this->template = Template::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'] ?? null,
            'name_translation_key' => $data['name_translation_key'] ?? null,
            'can_be_deleted' => $data['can_be_deleted'] ?? true,
        ]);

        // A template has at least one page: the Contact information page.
        $request = [
            'account_id' => $data['account_id'],
            'author_id' => $data['author_id'],
            'template_id' => $this->template->id,
            'name_translation_key' => trans_key('Contact information'),
            'can_be_deleted' => false,
            'type' => TemplatePage::TYPE_CONTACT,
        ];
        (new CreateTemplatePage)->execute($request);

        return $this->template;
    }
}
