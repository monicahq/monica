<?php

namespace App\Services\Account\Settings;

use App\Models\User\User;
use Illuminate\Support\Str;
use App\Services\BaseService;
use function Safe\json_encode;
use App\Models\Account\Account;
use Illuminate\Support\Facades\Storage;
use App\ExportResources\Account\Account as AccountResource;

class JsonExportAccount extends BaseService
{
    /** @var string */
    protected $tempFileName;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Export account as Json.
     *
     * @param  array  $data
     * @return string
     */
    public function execute(array $data): string
    {
        $this->validate($data);

        $user = User::findOrFail($data['user_id']);

        $this->tempFileName = 'temp/'.Str::random(40).'.json';

        $this->writeExport($data, $user);

        return $this->tempFileName;
    }

    /**
     * Export data in temp file.
     *
     * @param  array  $data
     * @param  User  $user
     */
    private function writeExport(array $data, User $user)
    {
        $result = [];
        $result['version'] = '1.0-preview.1';
        $result['app_version'] = config('monica.app_version');
        $result['export_date'] = now();
        $result['url'] = config('app.url');
        $result['exported_by'] = $user->uuid;
        $result['account'] = $this->exportAccount($data);

        $this->writeToTempFile(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Write to a temp file.
     *
     * @return void
     */
    private function writeToTempFile(string $sql)
    {
        Storage::disk('local')
            ->append($this->tempFileName, $sql);
    }

    /**
     * Export the Account table.
     *
     * @param  array  $data
     * @return mixed
     */
    private function exportAccount(array $data)
    {
        $account = Account::find($data['account_id']);

        $exporter = new AccountResource($account);

        return $exporter->resolve();
    }
}
