<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Security;

use App\Enums\McpOperationEnum;
use App\Models\McpCall;
use App\Models\User;
use App\ViewModels\Settings\Account\Security\McpCallsViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpCallsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_groups_the_calls_by_day_in_the_time_zone_of_the_user_newest_first(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-23 15:00:00', 'UTC'));
        $jim = User::factory()->create(['timezone' => 'America/New_York']);

        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'subject_label' => 'Pam Beesly', 'started_at' => '2026-10-23 14:00:00']);
        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'subject_label' => 'Dwight Schrute', 'started_at' => '2026-10-23 02:00:00']);
        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'subject_label' => 'Angela Martin', 'started_at' => '2026-10-23 03:30:00']);
        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'subject_label' => 'Kevin Malone', 'started_at' => '2026-10-19 12:00:00']);

        $days = new McpCallsViewModel(user: $jim)->days();

        $this->assertStringContainsString('Pam Beesly', $days[0]['rows'][0]['description']);
        $this->assertStringContainsString('Angela Martin', $days[1]['rows'][0]['description']);
        $this->assertStringContainsString('Dwight Schrute', $days[1]['rows'][1]['description']);
        $this->assertStringContainsString('2026', $days[2]['label']);
        $this->assertSame('22:00', $days[1]['rows'][1]['time']);
    }

    #[Test]
    public function it_leaves_out_the_calls_of_another_account(): void
    {
        $jim = User::factory()->create();
        $michael = User::factory()->create();

        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id]);
        McpCall::factory()->create(['account_id' => $michael->account_id, 'user_id' => $michael->id]);

        $viewModel = new McpCallsViewModel(user: $jim);

        $this->assertSame(1, $viewModel->entries()->total());
        $this->assertSame($jim->account_id, $viewModel->entries()->items()[0]->account_id);
    }

    #[Test]
    public function it_keeps_the_name_of_a_key_that_was_revoked(): void
    {
        $jim = User::factory()->create();
        $key = $jim->createToken('Prank calendar bot');

        McpCall::factory()->create([
            'account_id' => $jim->account_id,
            'user_id' => $jim->id,
            'connection_id' => (string) $key->accessToken->getKey(),
            'connection_name' => 'Prank calendar bot',
        ]);

        $key->accessToken->delete();

        $this->assertSame('Prank calendar bot', new McpCallsViewModel(user: $jim)->days()[0]['rows'][0]['connection']);
    }

    #[Test]
    public function it_describes_a_call_by_its_operation_its_outcome_and_what_it_reached(): void
    {
        $viewModel = new McpCallsViewModel(user: User::factory()->create());

        $this->assertStringContainsString('Dwight Schrute', $viewModel->description(McpCall::factory()->make()));
        $this->assertStringContainsString('Toby Flenderson', $viewModel->description(McpCall::factory()->make(['operation' => McpOperationEnum::Delete, 'subject_label' => 'Toby Flenderson'])));
    }

    #[Test]
    public function it_spells_out_what_a_row_unfolds_into(): void
    {
        $jim = User::factory()->create(['timezone' => 'Europe/Paris']);

        McpCall::factory()->refused()->create([
            'account_id' => $jim->account_id,
            'user_id' => $jim->id,
            'parameters' => '{"vault":"dunder-mifflin"}',
            'started_at' => '2026-10-23 12:32:07.412',
        ]);

        $row = new McpCallsViewModel(user: $jim)->days()[0]['rows'][0];

        $this->assertSame('warning', $row['tone']);
        $this->assertSame("{\n    \"vault\": \"dunder-mifflin\"\n}", $row['parameters']);
        $this->assertNull($row['response']);
        $this->assertSame('58 ms', $row['duration']);
        $this->assertStringContainsString('14:32:07.412', $row['when']);
        $this->assertStringContainsString('Europe/Paris', $row['when']);
    }

    #[Test]
    public function it_shows_a_cut_payload_as_it_was_kept(): void
    {
        $jim = User::factory()->create();

        McpCall::factory()->create([
            'account_id' => $jim->account_id,
            'user_id' => $jim->id,
            'response' => '{"narrative":"Michael said that',
            'response_truncated' => true,
        ]);

        $row = new McpCallsViewModel(user: $jim)->days()[0]['rows'][0];

        $this->assertSame('{"narrative":"Michael said that', $row['response']);
        $this->assertTrue($row['responseTruncated']);
    }

    #[Test]
    public function it_lists_only_the_calls_matching_the_filters(): void
    {
        $jim = User::factory()->create();

        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id]);
        McpCall::factory()->refused()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'tool' => 'delete-life-event']);

        $viewModel = new McpCallsViewModel(user: $jim, result: 'refused');

        $this->assertTrue($viewModel->isFiltered());
        $this->assertSame(1, $viewModel->entries()->total());
        $this->assertSame('delete-life-event', $viewModel->entries()->items()[0]->tool);
        $this->assertTrue($viewModel->hasAnyCall());
        $this->assertFalse(new McpCallsViewModel(user: $jim)->isFiltered());
    }

    #[Test]
    public function it_offers_the_connections_and_tools_the_account_has_actually_used(): void
    {
        $jim = User::factory()->create();
        $michael = User::factory()->create();

        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'connection_id' => '14', 'connection_name' => 'Dundie awards bot', 'tool' => 'list-vaults']);
        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'connection_id' => '14', 'connection_name' => 'Dundie awards bot']);
        McpCall::factory()->overOAuth()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'connection_id' => 'c1', 'tool' => 'list-vaults']);
        McpCall::factory()->create(['account_id' => $michael->account_id, 'user_id' => $michael->id, 'connection_name' => 'Prison Mike', 'tool' => 'delete-contact-reminder']);

        $viewModel = new McpCallsViewModel(user: $jim);

        $this->assertSame(['oauth_client:c1' => 'Claude', 'api_key:14' => 'Dundie awards bot'], $viewModel->connectionChoices());
        $this->assertSame(['get-contact', 'list-vaults'], $viewModel->toolChoices());
        $this->assertSame(['day', 'week', 'month', 'all'], array_keys($viewModel->periodChoices()));
        $this->assertCount(5, $viewModel->resultChoices());
    }
}
