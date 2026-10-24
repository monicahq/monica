<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Enums\McpCallResultEnum;
use App\Enums\McpOperationEnum;
use App\Enums\TimeFormatEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\PageSize;
use App\Models\McpCall;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

class McpCallsViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly User $user,
        private readonly int|string $size = self::PER_PAGE,
        private readonly ?string $period = null,
        private readonly ?string $connection = null,
        private readonly ?string $result = null,
        private readonly ?string $tool = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    /** @return LengthAwarePaginator<int, McpCall> */
    public function entries(): LengthAwarePaginator
    {
        return once(fn (): LengthAwarePaginator => PageSize::paginate(
            $this->user->account->mcpCalls()
                ->matching($this->period, $this->connection, $this->result, $this->tool)
                ->latest('started_at')
                ->latest('id'),
            $this->size,
        ));
    }

    public function hasAnyCall(): bool
    {
        return once(fn (): bool => $this->user->account->mcpCalls()->exists());
    }

    public function isFiltered(): bool
    {
        return $this->period !== null || $this->connection !== null || $this->result !== null || $this->tool !== null;
    }

    /** @return array{period: ?string, connection: ?string, result: ?string, tool: ?string} */
    public function filters(): array
    {
        return [
            'period' => $this->period,
            'connection' => $this->connection,
            'result' => $this->result,
            'tool' => $this->tool,
        ];
    }

    /** @return array<string, string> */
    public function periodChoices(): array
    {
        return [
            'day' => __('Last 24 hours'),
            'week' => __('Last 7 days'),
            'month' => __('Last 30 days'),
            'all' => __('Whole history'),
        ];
    }

    /** @return array<string, string> */
    public function connectionChoices(): array
    {
        return $this->user->account->mcpCalls()
            ->latest('started_at')
            ->get(['connection_type', 'connection_id', 'connection_name'])
            ->unique(fn (McpCall $call): string => "{$call->connection_type->value}:{$call->connection_id}")
            ->mapWithKeys(fn (McpCall $call): array => [
                "{$call->connection_type->value}:{$call->connection_id}" => $call->connection_name !== '' ? $call->connection_name : __($call->connection_type->label()),
            ])
            ->sort()
            ->all();
    }

    /** @return array<string, string> */
    public function resultChoices(): array
    {
        return collect(McpCallResultEnum::cases())
            ->mapWithKeys(fn (McpCallResultEnum $result): array => [$result->value => __($result->label())])
            ->all();
    }

    /** @return list<string> */
    public function toolChoices(): array
    {
        return $this->user->account->mcpCalls()
            ->distinct()
            ->orderBy('tool')
            ->pluck('tool')
            ->all();
    }

    /**
     * @return list<array{label: string, count: string, rows: list<array{uuid: string, startedAt: CarbonImmutable, time: string, when: string, description: string, connection: string, tool: string, operation: ?string, vault: ?string, duration: ?string, result: string, tone: string, reason: ?string, ipAddress: ?string, parameters: ?string, parametersTruncated: bool, response: ?string, responseTruncated: bool}>}>
     */
    public function days(): array
    {
        $days = [];

        foreach ($this->entries()->items() as $call) {
            $startedAt = $call->started_at->setTimezone($this->user->timezone);
            $days[$startedAt->toDateString()][] = $this->row($call, $startedAt);
        }

        $grouped = [];

        foreach ($days as $date => $rows) {
            $grouped[] = [
                'label' => $this->dayLabel(CarbonImmutable::parse($date, $this->user->timezone)),
                'count' => trans_choice(':count call|:count calls', count($rows)),
                'rows' => $rows,
            ];
        }

        return $grouped;
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    public function retention(): string
    {
        $days = max(1, (int) config('monica.mcp_call_retention_in_days'));

        return trans_choice('Calls are kept for :count day, then removed for good.|Calls are kept for :count days, then removed for good.', $days);
    }

    public function description(McpCall $call): string
    {
        $succeeded = $call->result === McpCallResultEnum::Succeeded;
        $subject = $call->subject_label;

        if ($call->operation === null) {
            return __('Asked for a tool that does not exist');
        }

        if ($subject === null) {
            return match ($call->operation) {
                McpOperationEnum::Read => $succeeded ? __('Read data') : __('Tried to read data'),
                McpOperationEnum::Create => $succeeded ? __('Created a record') : __('Tried to create a record'),
                McpOperationEnum::Update => $succeeded ? __('Changed a record') : __('Tried to change a record'),
                McpOperationEnum::Delete => $succeeded ? __('Deleted a record') : __('Tried to delete a record'),
            };
        }

        return match ($call->operation) {
            McpOperationEnum::Read => $succeeded ? __('Read :subject', ['subject' => $subject]) : __('Tried to read :subject', ['subject' => $subject]),
            McpOperationEnum::Create => $succeeded ? __('Added to :subject', ['subject' => $subject]) : __('Tried to add to :subject', ['subject' => $subject]),
            McpOperationEnum::Update => $succeeded ? __('Changed :subject', ['subject' => $subject]) : __('Tried to change :subject', ['subject' => $subject]),
            McpOperationEnum::Delete => $succeeded ? __('Deleted :subject', ['subject' => $subject]) : __('Tried to delete :subject', ['subject' => $subject]),
        };
    }

    /** @return array{uuid: string, startedAt: CarbonImmutable, time: string, when: string, description: string, connection: string, tool: string, operation: ?string, vault: ?string, duration: ?string, result: string, tone: string, reason: ?string, ipAddress: ?string, parameters: ?string, parametersTruncated: bool, response: ?string, responseTruncated: bool} */
    private function row(McpCall $call, CarbonImmutable $startedAt): array
    {
        return [
            'uuid' => $call->uuid,
            'startedAt' => $startedAt,
            'time' => $this->user->time_format->format($startedAt),
            'when' => __(':date at :time (:timezone)', [
                'date' => $this->writeDate($startedAt),
                'time' => $startedAt->format($this->user->time_format === TimeFormatEnum::TwelveHour ? 'g:i:s.v A' : 'H:i:s.v'),
                'timezone' => $this->user->timezone,
            ]),
            'description' => $this->description($call),
            'connection' => $call->connection_name !== '' ? $call->connection_name : __($call->connection_type->label()),
            'tool' => $call->tool,
            'operation' => $call->operation === null ? null : __($call->operation->label()),
            'vault' => $call->vault_name,
            'duration' => $call->duration_in_milliseconds === null ? null : trans_choice('[0,*]:count ms', $call->duration_in_milliseconds),
            'result' => __($call->result->label()),
            'tone' => $call->result->tone(),
            'reason' => $call->error_message,
            'ipAddress' => $call->ip_address,
            'parameters' => $this->readable($call->parameters, $call->parameters_truncated),
            'parametersTruncated' => $call->parameters_truncated,
            'response' => $this->readable($call->response, $call->response_truncated),
            'responseTruncated' => $call->response_truncated,
        ];
    }

    private function dayLabel(CarbonImmutable $day): string
    {
        $today = CarbonImmutable::now($this->user->timezone)->startOfDay();

        if ($day->equalTo($today)) {
            return __('Today');
        }

        if ($day->equalTo($today->subDay())) {
            return __('Yesterday');
        }

        return $this->writeDate($day);
    }

    private function writeDate(CarbonImmutable $date): string
    {
        return $this->user->date_format?->format($date)
            ?? $date->locale(app()->getLocale())->translatedFormat((string) AvailableLocales::details(app()->getLocale())['date_format']);
    }

    private function readable(?string $json, bool $truncated): ?string
    {
        if ($json === null || $truncated) {
            return $json;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return $json;
        }

        return (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
