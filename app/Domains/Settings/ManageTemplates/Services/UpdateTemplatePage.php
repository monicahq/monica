<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\TemplatePage;
use App\Services\BaseService;
use Illuminate\Support\Str;

class UpdateTemplatePage extends BaseService implements ServiceInterface
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
            'template_page_id' => 'required|integer|exists:template_pages,id',
            'name' => 'required|string|max:255',
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
     * Update a template page.
     */
    public function execute(array $data): TemplatePage
    {
        $this->validateRules($data);

        $template = $this->account()->templates()
            ->findOrFail($data['template_id']);

        $this->templatePage = $template->pages()
            ->findOrFail($data['template_page_id']);

        $this->templatePage->name = $data['name'];
        $this->templatePage->slug = Str::slug($data['name'], '-', language: currentLang());
        $this->templatePage->save();

        return $this->templatePage;
    }
}
