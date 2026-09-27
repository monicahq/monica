<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderAnswerEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;

class ReminderAnswerSummary
{
    /** @var array{remembered: int, forgotten: int, waiting: int}|null */
    private ?array $counts = null;

    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
    ) {}

    public function hasAny(): bool
    {
        return $this->total() > 0;
    }

    public function total(): int
    {
        return array_sum($this->counts());
    }

    public function rememberedPercentage(): int
    {
        return $this->percentageOf($this->counts()['remembered']);
    }

    public function forgottenPercentage(): int
    {
        return $this->percentageOf($this->counts()['forgotten']);
    }

    public function waitingPercentage(): int
    {
        return $this->total() === 0 ? 0 : max(0, 100 - $this->rememberedPercentage() - $this->forgottenPercentage());
    }

    public function totalLine(): string
    {
        return trans_choice(
            'of :count reminder in the last 12 months|of :count reminders in the last 12 months',
            $this->total(),
        );
    }

    public function chartLabel(): string
    {
        return __(':remembered% remembered, :forgotten% forgot, :waiting% waiting for an answer', [
            'remembered' => $this->rememberedPercentage(),
            'forgotten' => $this->forgottenPercentage(),
            'waiting' => $this->waitingPercentage(),
        ]);
    }

    private function percentageOf(int $count): int
    {
        return $this->total() === 0 ? 0 : (int) round($count / $this->total() * 100);
    }

    /** @return array{remembered: int, forgotten: int, waiting: int} */
    private function counts(): array
    {
        if ($this->counts !== null) {
            return $this->counts;
        }

        $today = CarbonImmutable::now($this->user->timezone)->startOfDay();

        $rules = Reminder::withTrashed()
            ->where('account_id', $this->contact->account_id)
            ->where('remindable_type', $this->contact->getMorphClass())
            ->where('remindable_id', $this->contact->id)
            ->select('id');

        $occurrences = ReminderOccurrence::query()
            ->whereIn('reminder_id', $rules)
            ->whereNull('cancelled_at')
            ->whereDate('local_date', '>', $today->subYear()->toDateString())
            ->whereDate('local_date', '<=', $today->toDateString())
            ->get(['answer']);

        return $this->counts = [
            'remembered' => $occurrences->where('answer', ReminderAnswerEnum::Remembered)->count(),
            'forgotten' => $occurrences->where('answer', ReminderAnswerEnum::Forgotten)->count(),
            'waiting' => $occurrences->whereNull('answer')->count(),
        ];
    }
}
