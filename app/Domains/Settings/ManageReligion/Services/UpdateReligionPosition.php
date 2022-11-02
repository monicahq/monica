<?php

namespace App\Domains\Settings\ManageReligion\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Religion;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class UpdateReligionPosition extends BaseService implements ServiceInterface
{
    private Religion $religion;

    private int $pastPosition;

    private array $data;

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
            'religion_id' => 'required|integer|exists:religions,id',
            'new_position' => 'required|integer',
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
     * Update the gift occasion's position.
     *
     * @param  array  $data
     * @return Religion
     */
    public function execute(array $data): Religion
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->religion;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->religion = Religion::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['religion_id']);

        $this->pastPosition = DB::table('religions')
            ->where('id', $this->religion->id)
            ->select('position')
            ->first()->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        DB::table('religions')
            ->where('id', $this->religion->id)
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        DB::table('religions')
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        DB::table('religions')
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
