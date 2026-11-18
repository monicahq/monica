<?php

namespace App\Domains\BlackBookd\ManageDatingPipeline\Services;

use App\Interfaces\ServiceInterface;
use App\Models\BlackBookdDatingPipeline;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GetWeeklyDatingRoundup extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'quiet_days' => 'nullable|integer|between:1,365',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'vault_must_belong_to_account', 'author_must_be_in_vault'];
    }

  /**
     * @return array<string, Collection<int, BlackBookdDatingPipeline>>
     */
    public function execute(array $data): array
    {
        $this->validateRules($data);
        $quietDays = $data['quiet_days'] ?? 7;
        $cutoff = Carbon::now()->subDays($quietDays);

        $pipelines = $this->vault->blackbookdDatingPipelines()->with('contact')->get();

        $active = $pipelines->filter(function (BlackBookdDatingPipeline $p) use ($cutoff) {
            return $p->isActive()
                && $p->last_message_at !== null
                && $p->last_message_at->greaterThan($cutoff);
        })->values();

        $quiet = $pipelines->filter(function (BlackBookdDatingPipeline $p) use ($cutoff) {
            return $p->isActive()
                && ($p->last_message_at === null || $p->last_message_at->lessThanOrEqualTo($cutoff));
        })->values();

        $longTerm = $pipelines->filter(function (BlackBookdDatingPipeline $p) {
            return $p->long_term_fit_score !== null
                && $p->long_term_fit_score >= 7
                && ! $p->boundary_held_do_not_reengage
                && $p->stage !== BlackBookdDatingPipeline::STAGE_ENDED;
        })->values();

        $letGo = $pipelines->filter(function (BlackBookdDatingPipeline $p) {
            return $p->boundary_held_do_not_reengage
                || $p->stage === BlackBookdDatingPipeline::STAGE_ENDED;
        })->values();

        return [
            'active' => $active,
            'quiet' => $quiet,
            'long_term_potential' => $longTerm,
            'let_go' => $letGo,
        ];
    }
}