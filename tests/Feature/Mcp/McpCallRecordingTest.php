<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Enums\McpCallResultEnum;
use App\Enums\McpConnectionTypeEnum;
use App\Enums\McpOperationEnum;
use App\Helpers\McpPayload;
use App\Http\Middleware\SetMcpLogSource;
use App\Models\Contact;
use App\Models\Field;
use App\Models\McpCall;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Facades\Mcp;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Mcp\Servers\ServerWithFixtureTools;
use Tests\TestCase;

class McpCallRecordingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Vault $vault;

    private Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('Dundie awards bot')->plainTextToken;
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Dunder Mifflin']);
        $this->contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->user->account_id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);
    }

    #[Test]
    public function it_records_a_read_with_the_vault_and_the_contact_it_reached(): void
    {
        $this->callTool('get-contact', ['vault' => $this->vault->uuid, 'contact' => $this->contact->uuid])
            ->assertOk()
            ->assertJsonPath('result.isError', false);

        $call = McpCall::query()->sole();

        $this->assertSame($this->user->account_id, $call->account_id);
        $this->assertSame($this->user->id, $call->user_id);
        $this->assertSame(McpConnectionTypeEnum::ApiKey, $call->connection_type);
        $this->assertSame('Dundie awards bot', $call->connection_name);
        $this->assertSame('get-contact', $call->tool);
        $this->assertSame(McpOperationEnum::Read, $call->operation);
        $this->assertSame(McpCallResultEnum::Succeeded, $call->result);
        $this->assertSame($this->vault->id, $call->vault_id);
        $this->assertSame('Dunder Mifflin', $call->vault_name);
        $this->assertSame($this->contact->getMorphClass(), $call->subject_type);
        $this->assertSame($this->contact->id, $call->subject_id);
        $this->assertSame('Dwight Schrute', $call->subject_label);
        $this->assertSame('7', $call->json_rpc_id);
        $this->assertSame('127.0.0.1', $call->ip_address);
        $this->assertNotNull($call->duration_in_milliseconds);
        $this->assertMatchesRegularExpression('/\.\d{3}$/', (string) $call->getRawOriginal('started_at'));
        $this->assertStringContainsString($this->contact->uuid, (string) $call->parameters);
        $this->assertStringContainsString('Schrute', (string) $call->response);
        $this->assertNull($call->error_code);
    }

    #[Test]
    public function it_records_a_change_as_an_update(): void
    {
        $this->callTool('update-contact-name', [
            'vault' => $this->vault->uuid,
            'contact' => $this->contact->uuid,
            'first_name' => 'Mose',
        ])->assertOk();

        $call = McpCall::query()->sole();

        $this->assertSame(McpOperationEnum::Update, $call->operation);
        $this->assertSame(McpCallResultEnum::Succeeded, $call->result);
        $this->assertSame($this->contact->id, $call->subject_id);
    }

    #[Test]
    public function it_records_a_refusal_without_naming_what_belongs_to_another_account(): void
    {
        $michael = User::factory()->create();
        $hisVault = Vault::factory()->create(['account_id' => $michael->account_id]);
        $hisContact = Contact::factory()->create(['vault_id' => $hisVault->id, 'account_id' => $michael->account_id]);

        $this->callTool('get-contact', ['vault' => $hisVault->uuid, 'contact' => $hisContact->uuid])
            ->assertOk()
            ->assertJsonPath('result.isError', true);

        $call = McpCall::query()->sole();

        $this->assertSame($this->user->account_id, $call->account_id);
        $this->assertSame(McpCallResultEnum::Refused, $call->result);
        $this->assertSame(-32602, $call->error_code);
        $this->assertSame('That vault does not exist.', $call->error_message);
        $this->assertNull($call->vault_id);
        $this->assertNull($call->vault_name);
        $this->assertNull($call->subject_id);
        $this->assertNull($call->subject_label);
    }

    #[Test]
    public function it_records_parameters_the_tool_rejects(): void
    {
        $this->callTool('update-life-event', [
            'vault' => $this->vault->uuid,
            'life_event' => 'anything',
            'happened_on' => '31/02/1978',
        ])->assertOk()->assertJsonPath('result.isError', true);

        $call = McpCall::query()->sole();

        $this->assertSame(McpCallResultEnum::InvalidParameters, $call->result);
        $this->assertSame(-32602, $call->error_code);
        $this->assertStringContainsString('happened on', (string) $call->error_message);
    }

    #[Test]
    public function it_records_a_tool_that_does_not_exist_as_invalid_parameters(): void
    {
        $this->callTool('fire-toby', [])->assertBadRequest()->assertJsonPath('error.code', -32602);

        $call = McpCall::query()->sole();

        $this->assertSame('fire-toby', $call->tool);
        $this->assertNull($call->operation);
        $this->assertSame(McpCallResultEnum::InvalidParameters, $call->result);
        $this->assertSame(-32602, $call->error_code);
    }

    #[Test]
    public function it_records_an_error_the_server_answered_with_a_status_of_200(): void
    {
        $this->registerFixtureServer();

        $this->callTool('break-unexpectedly', [], '/mcp-fixture')
            ->assertOk()
            ->assertJsonPath('result.isError', true);

        $call = McpCall::query()->sole();

        $this->assertSame(McpCallResultEnum::Error, $call->result);
        $this->assertSame(-32603, $call->error_code);
    }

    #[Test]
    public function it_holds_a_running_call_as_interrupted_until_it_returns(): void
    {
        $this->registerFixtureServer();

        $response = $this->callTool('read-own-call-history', [], '/mcp-fixture')->assertOk();

        $this->assertSame(
            ['rows' => 1, 'result' => 'interrupted', 'duration_in_milliseconds' => null],
            json_decode($response->json('result.content.0.text'), true),
        );
        $this->assertSame(McpCallResultEnum::Succeeded, McpCall::query()->sole()->result);
    }

    #[Test]
    public function it_masks_secrets_and_sensitive_values_before_storing_the_parameters(): void
    {
        $sensitive = Field::factory()->create(['account_id' => $this->user->account_id, 'is_sensitive' => true]);

        $this->callTool('update-life-event', [
            'vault' => $this->vault->uuid,
            'life_event' => 'dundies-2005',
            'password' => 'bears-beets-battlestar',
            'fields' => [$sensitive->id => 'Allergic to nothing but Toby'],
        ])->assertOk();

        $stored = (string) McpCall::query()->sole()->getRawOriginal('parameters');

        $this->assertStringNotContainsString('bears-beets-battlestar', $stored);
        $this->assertStringNotContainsString('Allergic to nothing but Toby', $stored);
        $this->assertSame(2, substr_count($stored, McpPayload::MASK));
    }

    #[Test]
    public function it_cuts_parameters_that_are_too_long_and_says_so(): void
    {
        $this->callTool('get-contact', [
            'vault' => $this->vault->uuid,
            'contact' => $this->contact->uuid,
            'narrative' => str_repeat('That’s what she said. ', 2000),
        ])->assertOk();

        $call = McpCall::query()->sole();

        $this->assertTrue($call->parameters_truncated);
        $this->assertLessThanOrEqual(McpPayload::MAXIMUM_BYTES, strlen((string) $call->parameters));
        $this->assertFalse($call->response_truncated);
    }

    #[Test]
    public function it_records_nothing_for_a_request_without_a_valid_key(): void
    {
        $this->postJson('/mcp', $this->envelope('get-contact', []))->assertUnauthorized();

        $this->withToken('not-a-key')->postJson('/mcp', $this->envelope('get-contact', []))->assertUnauthorized();

        $this->assertSame(0, McpCall::query()->count());
    }

    /** @param  array<string, mixed>  $arguments */
    private function callTool(string $tool, array $arguments, string $uri = '/mcp'): TestResponse
    {
        return $this->withToken($this->token)->postJson($uri, $this->envelope($tool, $arguments));
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function envelope(string $tool, array $arguments): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'tools/call',
            'params' => ['name' => $tool, 'arguments' => $arguments],
        ];
    }

    private function registerFixtureServer(): void
    {
        Mcp::web('mcp-fixture', ServerWithFixtureTools::class)
            ->middleware(['api', 'auth:sanctum,api', SetMcpLogSource::class]);
    }
}
