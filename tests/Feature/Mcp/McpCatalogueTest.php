<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\MirrorsAnApiCapability;
use App\Models\Administrator;
use App\Models\User;
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

            if (preg_match('/^(get|list)-/', $tool->name()) && ($annotations['readOnlyHint'] ?? false) !== true) {
                $mislabelled[] = "{$tool->name()} only reads but is not marked read only";
            }

            if (preg_match('/^(delete|remove)-/', $tool->name()) && ($annotations['destructiveHint'] ?? false) !== true) {
                $mislabelled[] = "{$tool->name()} deletes but is not marked destructive";
            }
        }

        $this->assertSame([], $mislabelled);
    }

    /** @return array<int, class-string<Tool>> */
    private function registeredTools(): array
    {
        /** @var array<int, class-string<Tool>> $tools */
        $tools = new ReflectionClass(MonicaServer::class)->getDefaultProperties()['tools'];

        return $tools;
    }
}
