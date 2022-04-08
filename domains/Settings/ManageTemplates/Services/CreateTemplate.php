<?php

namespace App\Settings\ManageTemplates\Services;

use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class CreateTemplate extends BaseService implements ServiceInterface
{
    private Template $template;

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
            'name' => 'required|string|max:255',
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
     * Create a template.
     *
     * @param  array  $data
     * @return Template
     */
    public function execute(array $data): Template
    {
        $this->validateRules($data);

        $this->template = Template::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
        ]);

        // A template has at least one page: the Contact information page.
        $request = [
            'account_id' => $data['account_id'],
            'author_id' => $data['author_id'],
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_contact_information'),
            'can_be_deleted' => false,
            'type' => TemplatePage::TYPE_CONTACT,
        ];
        $this->templatePageContact = (new CreateTemplatePage)->execute($request);

        return $this->template;
    }
}
