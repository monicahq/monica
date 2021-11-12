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
        $result['account'] = $this->exportAccount($data);

        // $this->exportAddress($data);
        // $this->exportCompany($data);

        // $this->exportContactFieldType($data); // config

        // $this->exportConversation($data);
        // $this->exportDays($data);
        // $this->exportEmotionCall($data);
        // $this->exportEntries($data);
        // $this->exportInvitation($data);
        // $this->exportJournalEntry($data);
        // $this->exportLifeEventCategory($data);
        // $this->exportLifeEventType($data);
        // $this->exportLifeEvent($data);
        // $this->exportMessage($data);
        // $this->exportMetaDataLoveRelationship($data);
        // $this->exportModule($data);
        // $this->exportNote($data);
        // $this->exportOccupation($data);
        // $this->exportPet($data);
        // $this->exportPlace($data);
        // $this->exportRecoveryCode($data);
        // $this->exportRelationTypeGroup($data);
        // $this->exportRelationType($data);
        // $this->exportRelationship($data);
        // $this->exportReminderOutbox($data);
        // $this->exportReminderRule($data);
        // $this->exportReminderSent($data);
        // $this->exportReminder($data);
        // $this->exportSpecialDate($data);
        // $this->exportTag($data);
        // $this->exportTask($data);
        // $this->exportTermUser($data);
        // $this->exportWeather($data);
        // $this->exportContactPhoto($data);
        // $this->exportAuditLogs($data);

        $this->writeToTempFile(json_encode($result, JSON_PRETTY_PRINT));
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
