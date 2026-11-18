<?php

namespace App\Domains\BlackBookd\ManageDatingPipeline\Services;

use App\Interfaces\ServiceInterface;
use App\Models\BlackBookdDatingPipeline;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GetQuietDatingPipelines extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'days' => 'required|integer|between:1,365',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'vault_must_belong_to_account', 'author_must_be_in_vault'];
    }

    /**
     * @return Collection<int, BlackBookdDatingPipeline>
     */
    public function execute(array $data): Collection
    {
        $this->validateRules($data);
        $cutoff = Carbon::now()->subDays($data['days']);

        return $this->vault->blackbookdDatingPipelines()
            ->with('contact')
            ->where('boundary_held_do_not_reengage', false)
            ->whereNotIn('stage', [BlackBookdDatingPipeline::STAGE_ENDED, BlackBookdDatingPipeline::STAGE_PAUSED])
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('last_message_at')->orWhere('last_message_at', '<=', $cutoff);
            })
            ->orderBy('last_message_at')
            ->get();
    }
}