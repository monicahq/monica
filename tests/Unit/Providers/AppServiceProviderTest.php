<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Console\Commands\MakeAdministratorCommand;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Nightwatch\Core;
use Laravel\Nightwatch\Facades\Nightwatch;
use Laravel\Nightwatch\QueryConnectionType;
use Laravel\Nightwatch\Records\Command;
use Laravel\Nightwatch\Records\Exception as ExceptionRecord;
use Laravel\Nightwatch\Records\OutgoingRequest;
use Laravel\Nightwatch\Records\Query;
use Laravel\Nightwatch\Records\Request as RequestRecord;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_purges_a_flag_no_longer_declared_and_keeps_the_declared_ones(): void
    {
        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));

        $pam = User::factory()->create();
        $pam->account->features()->activate('example-capability');
        $pam->account->features()->activate('retired-capability');

        $this->artisan('pennant:purge', ['--except-registered' => true])->assertExitCode(0);

        $this->assertDatabaseMissing('features', ['name' => 'retired-capability']);
        $this->assertDatabaseHas('features', ['name' => 'example-capability']);
    }

    #[Test]
    public function it_remembers_a_browser_for_the_duration_the_instance_sets(): void
    {
        $this->assertSame((int) config('monica.remember_duration_days') * 24 * 60, config('auth.guards.web.remember'));
    }

    #[Test]
    public function it_keeps_the_duration_on_a_guard_built_again(): void
    {
        $this->app['auth']->forgetGuards();

        Auth::guard('web')->login(User::factory()->create(), remember: true);

        $cookie = Cookie::queued(Auth::guard('web')->getRecallerName());

        $this->assertEqualsWithDelta(
            now()->addDays((int) config('monica.remember_duration_days'))->getTimestamp(),
            $cookie->getExpiresTime(),
            60,
        );
    }

    #[Test]
    public function it_registers_the_collation_macro_on_collections(): void
    {
        $this->assertTrue(Collection::hasMacro('sortByCollator'));
    }

    #[Test]
    public function it_orders_a_collection_by_a_string_key_through_the_macro(): void
    {
        $sorted = new Collection([
            ['name' => 'Schrute'],
            ['name' => 'Élodie'],
            ['name' => 'Erin'],
        ])->sortByCollator('name');

        $this->assertSame(['Élodie', 'Erin', 'Schrute'], $sorted->pluck('name')->all());
    }

    #[Test]
    public function it_orders_a_collection_by_a_callable_through_the_macro(): void
    {
        $sorted = new Collection(['Schrute', 'Élodie', 'Erin'])
            ->sortByCollator(fn (string $name): string => $name);

        $this->assertSame(['Élodie', 'Erin', 'Schrute'], $sorted->all());
    }

    #[Test]
    public function it_orders_a_collection_the_other_way_round_through_the_macro(): void
    {
        $sorted = new Collection(['Schrute', 'Élodie', 'Erin'])
            ->sortByCollator(fn (string $name): string => $name, descending: true);

        $this->assertSame(['Schrute', 'Erin', 'Élodie'], $sorted->all());
    }

    #[Test]
    public function it_compares_french_accents_from_the_end_of_the_word_through_the_macro(): void
    {
        $this->app->setLocale('fr_FR');

        $sorted = new Collection(['côté', 'coté', 'côte', 'cote'])
            ->sortByCollator(fn (string $word): string => $word);

        $this->assertSame(['cote', 'côte', 'coté', 'côté'], $sorted->all());
    }

    #[Test]
    public function it_reaches_an_eloquent_collection_and_hands_one_back(): void
    {
        User::factory()->create(['first_name' => 'Zoe']);
        User::factory()->create(['first_name' => 'Élodie']);
        User::factory()->create(['first_name' => 'Erin']);

        $sorted = User::query()->get()->sortByCollator('first_name');

        $this->assertInstanceOf(EloquentCollection::class, $sorted);
        $this->assertSame(['Élodie', 'Erin', 'Zoe'], $sorted->pluck('first_name')->all());
    }

    #[Test]
    public function it_reindexes_the_collection_the_macro_hands_back(): void
    {
        $sorted = new Collection(['Schrute', 'Élodie', 'Erin'])
            ->sortByCollator(fn (string $name): string => $name);

        $this->assertSame([0, 1, 2], $sorted->keys()->all());
    }

    #[Test]
    public function it_files_a_letter_carrying_a_ring_with_its_plain_letter(): void
    {
        $sorted = new Collection(['Scott', 'Ångström', 'Martin'])
            ->sortByCollator(fn (string $name): string => $name);

        $this->assertSame(['Ångström', 'Martin', 'Scott'], $sorted->all());
    }

    #[Test]
    public function it_forwards_the_high_priority_to_the_configured_queue(): void
    {
        config(['monica.queues.high' => 'monica-high']);
        $this->app->getProvider(AppServiceProvider::class)->boot();

        Queue::connection('database')->pushRaw('{}', 'high');

        $this->assertDatabaseHas('jobs', ['queue' => 'monica-high']);
        $this->assertSame('monica-high', Queue::connection('database')->pop('high')?->getQueue());
    }

    #[Test]
    public function it_forwards_work_naming_no_queue_to_the_configured_default_queue(): void
    {
        config(['monica.queues.default' => 'monica-default']);
        $this->app->getProvider(AppServiceProvider::class)->boot();

        Queue::connection('database')->pushRaw('{}');

        $this->assertDatabaseHas('jobs', ['queue' => 'monica-default']);
    }

    #[Test]
    public function it_keeps_the_shipped_name_when_the_setting_is_blank(): void
    {
        config(['monica.queues.high' => '  ']);
        $this->app->getProvider(AppServiceProvider::class)->boot();

        Queue::connection('database')->pushRaw('{}', 'high');

        $this->assertDatabaseHas('jobs', ['queue' => 'high']);
    }

    #[Test]
    public function it_forwards_both_priorities_to_the_default_queue(): void
    {
        config(['monica.queues' => ['high' => 'default', 'low' => 'default']]);
        $this->app->getProvider(AppServiceProvider::class)->boot();

        Queue::connection('database')->pushRaw('{}', 'high');
        Queue::connection('database')->pushRaw('{}', 'low');

        $this->assertNotNull(Queue::connection('database')->pop('default'));
        $this->assertNotNull(Queue::connection('database')->pop('default'));
    }

    #[Test]
    public function it_sends_nightwatch_the_id_of_who_is_signed_in_and_nothing_else(): void
    {
        $michael = User::factory()->create();

        $resolver = $this->app->make(Core::class)->userDetailsResolver;

        $this->assertIsCallable($resolver);
        $this->assertSame(['id' => $michael->id], $resolver($michael));
    }

    #[Test]
    public function it_opens_no_nightwatch_trace_for_the_health_check(): void
    {
        Nightwatch::sample(1.0);

        Event::dispatch(new DiagnosingHealth);

        $this->assertFalse(Nightwatch::sampling());
    }

    #[Test]
    public function it_leaves_the_tables_of_the_framework_out_of_a_nightwatch_trace(): void
    {
        $this->assertTrue($this->nightwatchRejectsQuery('select * from `sessions` where `id` = ? limit 1'));
        $this->assertTrue($this->nightwatchRejectsQuery('insert into "jobs" ("queue", "payload") values (?, ?)'));
        $this->assertTrue($this->nightwatchRejectsQuery('update `cache_locks` set `owner` = ? where `key` = ?'));
        $this->assertTrue($this->nightwatchRejectsQuery('delete from `telescope_entries` where `created_at` < ?'));
        $this->assertFalse($this->nightwatchRejectsQuery('select * from `contacts` where `vault_id` = ?'));
        $this->assertFalse($this->nightwatchRejectsQuery('select * from `features` where `scope` = ?'));
    }

    #[Test]
    public function it_leaves_the_login_throttle_out_of_the_nightwatch_cache_events(): void
    {
        $this->assertTrue($this->nightwatchRejectsCacheKey('michael@dundermifflin.com|127.0.0.1'));
        $this->assertTrue($this->nightwatchRejectsCacheKey('michael@dundermifflin.com|127.0.0.1:timer'));
        $this->assertFalse($this->nightwatchRejectsCacheKey('instance_settings'));
    }

    #[Test]
    public function it_redacts_a_token_a_signature_and_a_search_term_from_a_nightwatch_request_url(): void
    {
        $token = str_repeat('a', 64);
        $signature = str_repeat('b', 64);

        $this->assertSame(
            'https://monica.test/s/***?expires=1&signature=***',
            $this->nightwatchRequest("https://monica.test/s/{$token}?expires=1&signature={$signature}")->url,
        );
        $this->assertSame(
            'https://monica.test/vaults/1/search?query=***&scope=vault',
            $this->nightwatchRequest('https://monica.test/vaults/1/search?query=michael+scott&scope=vault')->url,
        );
        $this->assertSame(
            'https://monica.test/sso/github/callback?code=***&state=abc',
            $this->nightwatchRequest('https://monica.test/sso/github/callback?code=4f2a&state=abc')->url,
        );
    }

    #[Test]
    public function it_drops_the_telegram_secret_header_from_a_nightwatch_request(): void
    {
        $request = $this->nightwatchRequest('https://monica.test/telegram/webhook', [
            'X-Telegram-Bot-Api-Secret-Token' => 'secret',
            'Content-Type' => 'application/json',
        ]);

        $this->assertFalse($request->headers->has('X-Telegram-Bot-Api-Secret-Token'));
        $this->assertSame('application/json', $request->headers->get('Content-Type'));
    }

    #[Test]
    public function it_redacts_the_telegram_bot_token_from_an_outgoing_request_and_an_exception(): void
    {
        $outgoingRequest = new OutgoingRequest(
            method: 'GET',
            url: 'https://api.telegram.org/bot123456:ABC-def_ghi/getWebhookInfo',
            duration: 0,
            requestSize: 0,
            responseSize: 0,
            statusCode: 200,
        );
        $exception = new ExceptionRecord(
            class: ConnectionException::class,
            message: 'cURL error 28: Operation timed out for https://api.telegram.org/bot123456:ABC-def_ghi/getWebhookInfo',
            code: 0,
            file: '',
            line: null,
            handled: true,
        );

        foreach ($this->nightwatchCallbacks('redactOutgoingRequestCallbacks') as $callback) {
            $callback($outgoingRequest);
        }
        foreach ($this->nightwatchCallbacks('redactExceptionCallbacks') as $callback) {
            $callback($exception);
        }

        $this->assertSame('https://api.telegram.org/bot***/getWebhookInfo', $outgoingRequest->url);
        $this->assertSame('cURL error 28: Operation timed out for https://api.telegram.org/bot***/getWebhookInfo', $exception->message);
    }

    #[Test]
    public function it_redacts_an_address_from_a_nightwatch_command_line(): void
    {
        $command = new Command(
            class: MakeAdministratorCommand::class,
            name: 'monica:make-administrator',
            command: 'monica:make-administrator --email=michael@dundermifflin.com',
            exitCode: 0,
            duration: 0,
        );

        foreach ($this->nightwatchCallbacks('redactCommandCallbacks') as $callback) {
            $callback($command);
        }

        $this->assertSame('monica:make-administrator --email=***', $command->command);
    }

    private function nightwatchRejectsQuery(string $sql): bool
    {
        $query = new Query(sql: $sql, file: '', line: 0, duration: 0, connection: 'testing', connectionType: QueryConnectionType::Direct);

        foreach ($this->nightwatchCallbacks('rejectQueryCallbacks') as $callback) {
            if ($callback($query)) {
                return true;
            }
        }

        return false;
    }

    private function nightwatchRejectsCacheKey(string $key): bool
    {
        /** @var list<string> $patterns */
        $patterns = new ReflectionProperty(Core::class, 'rejectCacheKeys')->getValue($this->app->make(Core::class));

        foreach ($patterns as $pattern) {
            if (@preg_match($pattern, $key) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function nightwatchRequest(string $url, array $headers = []): RequestRecord
    {
        $request = new RequestRecord(
            method: 'GET',
            url: $url,
            routeName: '',
            routeMethods: ['GET'],
            routeDomain: '',
            routePath: '',
            routeAction: '',
            ip: '127.0.0.1',
            duration: 0,
            statusCode: 200,
            requestSize: 0,
            responseSize: 0,
            headers: new HeaderBag($headers),
            payload: new InputBag,
            files: new FileBag,
        );

        foreach ($this->nightwatchCallbacks('redactRequestCallbacks') as $callback) {
            $callback($request);
        }

        return $request;
    }

    /**
     * @return list<callable>
     */
    private function nightwatchCallbacks(string $property): array
    {
        /** @var list<callable> $callbacks */
        $callbacks = new ReflectionProperty(Core::class, $property)->getValue($this->app->make(Core::class));

        return $callbacks;
    }
}
