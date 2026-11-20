<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\MirrorsAnApiCapability;
use App\Models\Administrator;
use App\Models\User;
use App\ViewModels\Docs\McpDocsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Server\Tool;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class McpCatalogueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_capability_names_an_api_capability_that_exists(): void
    {
        $unmirrored = [];

        foreach ($this->registeredTools() as $class) {
            $tool = app($class);

            if (! in_array(MirrorsAnApiCapability::class, class_uses_recursive($class), true)) {
                $unmirrored[] = "{$class} names no API capability at all";

                continue;
            }

            if (! Route::has($tool->mirrors())) {
                $unmirrored[] = "{$class} names [{$tool->mirrors()}], which no route answers to";
            }
        }

        $this->assertSame([], $unmirrored);
    }

    #[Test]
    public function every_capability_is_a_tool_the_server_offers(): void
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);
        $user->withAccessToken(PersonalAccessToken::findToken($user->createToken('Every scope', ['*', 'administration'])->plainTextToken));

        $offered = MonicaServer::actingAs($user)->tools();

        $offered->assertRegistered($this->registeredTools());
    }

    #[Test]
    public function it_lists_every_capability_on_one_page(): void
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);
        $key = $user->createToken('Every scope', ['*', 'administration']);

        $response = $this->withToken($key->plainTextToken)->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertOk();
        $response->assertJsonCount(count($this->registeredTools()), 'result.tools');
        $response->assertJsonMissingPath('result.nextCursor');

        foreach ($response->json('result.tools') as $tool) {
            foreach (['readOnlyHint', 'destructiveHint', 'openWorldHint'] as $hint) {
                $this->assertIsBool($tool['annotations'][$hint] ?? null, "{$tool['name']} must declare {$hint}");
            }
        }
    }

    #[Test]
    public function the_catalogue_covers_every_tool_class_that_exists(): void
    {
        $onDisk = collect(glob(app_path('Mcp/Tools/*.php')))
            ->map(fn (string $path): string => 'App\\Mcp\\Tools\\'.basename($path, '.php'))
            ->filter(fn (string $class): bool => is_subclass_of($class, Tool::class))
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing($onDisk, $this->registeredTools());
    }

    #[Test]
    public function every_tool_that_reads_says_so_and_every_tool_that_deletes_warns(): void
    {
        $mislabelled = [];

        foreach ($this->registeredTools() as $class) {
            $tool = app($class);
            $annotations = $tool->annotations();

            // These reads also record a visit or an export in the account, beyond the MCP audit trail.
            $recordsActivity = in_array($tool->name(), ['get-contact', 'get-contact-markdown'], true);

            if (preg_match('/^(get|list|search|export)-/', $tool->name()) && ! $recordsActivity && ($annotations['readOnlyHint'] ?? false) !== true) {
                $mislabelled[] = "{$tool->name()} only reads but is not marked read only";
            }

            if (preg_match('/^(delete|remove)-/', $tool->name()) && ($annotations['destructiveHint'] ?? false) !== true) {
                $mislabelled[] = "{$tool->name()} deletes but is not marked destructive";
            }
        }

        $this->assertSame([], $mislabelled);
    }

    #[Test]
    public function annotations_describe_mutations_and_external_effects_not_just_tool_names(): void
    {
        // Columns: read-only, destructive, open-world. A recorded transaction never sends money.
        $expected = [
            'list-vaults' => [true, false, false],
            'search-records' => [true, false, false],
            'export-possession-type' => [true, false, false],
            'get-contact' => [false, true, false],
            'get-contact-markdown' => [false, false, false],
            'create-contact' => [false, false, false],
            'create-knowledge-note' => [false, false, false],
            'create-possession-transaction' => [false, false, false],
            'import-possession-type' => [false, false, false],
            'update-contact-name' => [false, true, false],
            'update-contact-name-representations' => [false, true, false],
            'update-possession-field' => [false, true, false],
            'replace-life-event-type-fields' => [false, true, false],
            'set-possession-values' => [false, true, false],
            'move-possession' => [false, true, false],
            'delete-group' => [false, true, false],
            'restore-group' => [false, false, false],
            'publish-template-version' => [false, true, false],
            'answer-reminder-occurrence' => [false, true, false],
            'answer-group-reminder-occurrence' => [false, true, false],
            'create-contact-reminder' => [false, true, true],
            'create-group-reminder' => [false, true, true],
            'update-contact-reminder' => [false, true, true],
            'update-group-reminder' => [false, true, true],
            'create-support-request' => [false, true, true],
            'add-support-message' => [false, true, true],
            'send-support-reply' => [false, true, true],
            'submit-feedback' => [false, true, true],
            'add-support-note' => [false, false, false],
            'change-support-request-status' => [false, true, false],
            'solve-support-request' => [false, true, false],
        ];

        foreach ($this->registeredTools() as $class) {
            $tool = app($class);

            if (! isset($expected[$tool->name()])) {
                continue;
            }

            $annotations = $tool->toArray()['annotations'];

            $this->assertSame($expected[$tool->name()], [
                $annotations['readOnlyHint'] ?? null,
                $annotations['destructiveHint'] ?? null,
                $annotations['openWorldHint'] ?? null,
            ], $tool->name());

            unset($expected[$tool->name()]);
        }

        $this->assertSame([], $expected, 'Every annotation example must refer to a registered tool.');
    }

    #[Test]
    public function every_parameter_taking_an_id_names_the_tool_that_gives_it(): void
    {
        $catalogue = McpDocsViewModel::catalogue();
        $tools = array_keys($catalogue);
        $silent = [];

        foreach ($catalogue as $tool) {
            foreach ($tool['params'] as $param) {
                if (! str_starts_with($param['description'], 'The id')) {
                    continue;
                }

                if (! collect($tools)->contains(fn (string $name): bool => str_contains($param['description'], $name))) {
                    $silent[] = "{$tool['name']}.{$param['name']}";
                }
            }
        }

        $this->assertSame([], $silent);
    }

    /** @return array<int, class-string<Tool>> */
    private function registeredTools(): array
    {
        /** @var array<int, class-string<Tool>> $tools */
        $tools = new ReflectionClass(MonicaServer::class)->getDefaultProperties()['tools'];

        return $tools;
    }
}
