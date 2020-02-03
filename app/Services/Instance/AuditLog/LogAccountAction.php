<?php

namespace App\Services\Instance\AuditLog;

use App\Services\BaseService;
use App\Models\Instance\AuditLog;

class LogAccountAction extends BaseService
{
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
            'author_name' => 'required|string|max:255',
            'audited_at' => 'required|date',
            'action' => 'required|string|max:255',
            'objects' => 'required|json',
        ];
    }

    /**
     * Log an action that happened in an account.
     *
     * @param array $data
     * @return AuditLog
     */
    public function execute(array $data): AuditLog
    {
        $this->validate($data);

        return AuditLog::create([
            'account_id' => $data['account_id'],
            'author_id' => $data['author_id'],
            'author_name' => $data['author_name'],
            'audited_at' => $data['audited_at'],
            'action' => $data['action'],
            'objects' => $data['objects'],
        ]);
    }
}
