<?php

namespace App\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Information;
use App\Models\Template;
use App\Services\BaseService;

class AssociateInformationToTemplate extends BaseService implements ServiceInterface
{
    private array $data;
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
            'position' => 'required|integer',
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
     * Associate a template with an information.
     *
     * @param  array  $data
     * @return Template
     */
    public function execute(array $data): Template
    {
        $this->data = $data;
        $this->validate();

        $this->template->informations()->syncWithoutDetaching([
            $this->information->id => ['position' => $data['position']],
        ]);

        return $this->template;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->information = Information::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['information_id']);

        $this->template = Template::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['template_id']);
    }
}
