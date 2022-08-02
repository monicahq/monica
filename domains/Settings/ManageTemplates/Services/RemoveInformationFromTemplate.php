<?php

namespace App\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Information;
use App\Models\Template;
use App\Services\BaseService;

class RemoveInformationFromTemplate extends BaseService implements ServiceInterface
{
    private Template $template;

    private Information $information;

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
            'template_id' => 'required|integer|exists:templates,id',
            'information_id' => 'required|integer|exists:information,id',
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
     * Remove an information from a template.
     *
     * @param  array  $data
     * @return Template
     */
    public function execute(array $data): Template
    {
        $this->validateRules($data);

        $this->information = Information::where('account_id', $data['account_id'])
            ->findOrFail($data['information_id']);

        $this->template = Template::where('account_id', $data['account_id'])
            ->findOrFail($data['template_id']);

        $this->template->informations()->toggle([
            $this->information->id,
        ]);

        return $this->template;
    }
}
