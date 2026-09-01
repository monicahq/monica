<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\Models\EmailSent;
use App\Models\Log;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Docs\ApiDocsSection;
use App\ViewModels\Docs\ApiDocsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The reference says what each endpoint returns, field by field. This calls every
 * one of them and checks that it was telling the truth.
 *
 * The comparison is an equality in both directions, per endpoint: a field the
 * answer carries and the reference does not mention is a caller left guessing,
 * and a field the reference names and no answer carries is a caller building on
 * something that will never arrive. Neither is allowed.
 *
 * Every endpoint arranges its own state, in its own arm, sharing nothing with any
 * other. That is deliberate. One arm standing in for two endpoints would prove
 * that they answer the same way, which is the thing most likely to stop being
 * true, and it would be exactly the shape of mistake this test exists to catch.
 *
 * What it cannot do is read. It compares names, never meanings, so a description
 * that is plausible and wrong passes here. That half is guarded by review.
 */
class ApiDocsReturnFieldsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_documents_every_field_every_endpoint_returns(): void
    {
        $problems = [];

        foreach ($this->endpoints() as $section) {
            $problems = [...$problems, ...$this->check($section)];
        }

        $this->assertSame([], $problems, "The reference and the endpoints disagree:\n\n".implode("\n", $problems));
    }

    /**
     * The loop above proves the fields are right. This proves there are some: an
     * endpoint added later with no field list would be an endpoint the loop skips
     * over quietly, which is the one way this whole check could be made useless.
     */
    #[Test]
    public function every_endpoint_that_answers_with_a_body_documents_its_fields(): void
    {
        $missing = [];
        $unexpected = [];

        foreach ($this->endpoints() as $section) {
            $answersWithNothing = $section->responseStatus === 204;

            if (! $answersWithNothing && ! $section->hasReturnFields()) {
                $missing[] = $section->id;
            }

            if ($answersWithNothing && $section->hasReturnFields()) {
                $unexpected[] = $section->id;
            }
        }

        $this->assertSame([], $missing, 'These endpoints answer with a body and document no fields: '.implode(', ', $missing));
        $this->assertSame([], $unexpected, 'These endpoints answer with no body and document fields anyway: '.implode(', ', $unexpected));
    }

    /**
     * Not looking inside a field is a decision, and it should stay a rare one. It
     * is turned on for exactly one field today, and a second would mean somebody
     * had chosen to stop checking a shape rather than describe it.
     */
    #[Test]
    public function it_leaves_exactly_one_field_unchecked_on_purpose(): void
    {
        $opaque = [];

        foreach ($this->endpoints() as $section) {
            foreach ($section->returnFields as $field) {
                if ($field['opaque'] ?? false) {
                    $opaque[] = $section->id.' '.$field['name'];
                }
            }
        }

        $this->assertSame([
            'logs-index data[].attributes.parameters',
            'logs-show data.attributes.parameters',
        ], $opaque);
    }

    /**
     * @return list<string>
     */
    private function check(ApiDocsSection $section): array
    {
        $documented = array_column($section->returnFields, 'name');

        $response = $this->answer($section->id);

        if ($response === null) {
            return ["{$section->id}: nothing in this test knows how to call it. Add an arm to answer()."];
        }

        if ($response->status() !== $section->responseStatus) {
            return ["{$section->id}: its arm got {$response->status()} where the reference promises {$section->responseStatus}, so there is nothing worth comparing. Fix the arm.".PHP_EOL.$response->content()];
        }

        // An endpoint that answers with nothing has no JSON to decode, and asking
        // for it anyway is an error rather than an empty answer.
        $body = $response->content() === '' ? [] : ($response->json() ?? []);

        $actual = $this->paths($body, $section);

        // A list that came back empty has no record to look inside, so every field
        // of a record would go unchecked and this would pass while proving nothing
        // about them. Said once, and on its own, because the fifteen rows it would
        // otherwise report as missing are noise around the one thing to fix.
        foreach ($documented as $field) {
            if (str_ends_with($field, '[]') && ! in_array($field, $actual, true)) {
                return ["{$section->id}: `{$field}` stands for a record in a list, and the list came back empty, so nothing inside a record was checked. Arrange at least one record in its arm."];
            }
        }

        $problems = [];

        foreach (array_diff($actual, $documented) as $field) {
            $problems[] = "{$section->id}: the answer carries `{$field}` and the reference does not mention it.";
        }

        foreach (array_diff($documented, $actual) as $field) {
            $problems[] = "{$section->id}: the reference documents `{$field}` and the answer does not carry it.";
        }

        return $problems;
    }

    /**
     * Every path the answer carries.
     *
     * An object contributes its own path and then its children. A list of records
     * contributes its own path, a `[]` row standing for one of them, and then the
     * paths of the first one. A scalar, and a null, contribute only themselves.
     *
     * The walk stops at a field the reference marked as one it does not look
     * inside, because such a field holds whatever something else put there and
     * its keys are not the reference's to promise.
     *
     * @param  array<array-key, mixed>  $answer
     * @return list<string>
     */
    private function paths(array $answer, ApiDocsSection $section): array
    {
        $opaque = array_column(array_filter($section->returnFields, fn (array $field): bool => $field['opaque'] ?? false), 'name');

        return $this->walk($answer, '', $opaque);
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @param  list<string>  $opaque
     * @return list<string>
     */
    private function walk(array $value, string $prefix, array $opaque): array
    {
        $paths = [];

        foreach ($value as $key => $child) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            $paths[] = $path;

            if (in_array($path, $opaque, true) || ! is_array($child) || $child === []) {
                continue;
            }

            if (array_is_list($child)) {
                $paths[] = "{$path}[]";

                if (is_array($child[0])) {
                    $paths = [...$paths, ...$this->walk($child[0], "{$path}[]", $opaque)];
                }

                continue;
            }

            $paths = [...$paths, ...$this->walk($child, $path, $opaque)];
        }

        return $paths;
    }

    /**
     * @return list<ApiDocsSection>
     */
    private function endpoints(): array
    {
        $sections = [];

        foreach (new ApiDocsViewModel()->groups() as $group) {
            if ($group['guide']) {
                continue;
            }

            foreach ($group['sections'] as $section) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * One arm per endpoint. Each arranges what it needs and calls the thing.
     *
     * They all run inside one test and so share one database, which is why no two
     * of them write the same email address.
     *
     * A list arm creates at least one record, or the fields of a row would go
     * unverified and this test would pass while proving nothing about them.
     */
    private function answer(string $id): ?TestResponse
    {
        Queue::fake();

        return match ($id) {
            'status-show' => $this->json('GET', '/api/v1/status'),

            'register-store' => $this->json('POST', '/api/v1/register', [
                'first_name' => 'Michael',
                'last_name' => 'Scott',
                'email' => 'michael@dundermifflin.test',
                'password' => 'a-very-strong-password',
                'password_confirmation' => 'a-very-strong-password',
                'terms' => true,
            ]),

            'login-store' => $this->loggingIn(),

            // Signing out revokes the token it was called with, so this one needs a
            // real token rather than the stand in Sanctum::actingAs mints.
            'logout-destroy' => $this->withHeader('Authorization', 'Bearer '.User::factory()->create()->createToken('Dundie awards bot')->plainTextToken)
                ->json('DELETE', '/api/v1/logout'),

            'me-show' => $this->asSomebody(fn (): TestResponse => $this->json('GET', '/api/v1/me')),

            'me-update' => $this->asSomebody(fn (User $user): TestResponse => $this->json('PUT', '/api/v1/me', [
                'first_name' => 'Michael',
                'last_name' => 'Scott',
                'email' => 'jim@dundermifflin.test',
            ])),

            'api-keys-index' => $this->asSomebody(function (User $user): TestResponse {
                $user->createToken('Dundie awards bot');

                return $this->json('GET', '/api/v1/api-keys');
            }),

            'api-keys-show' => $this->asSomebody(function (User $user): TestResponse {
                $key = $user->createToken('Dundie awards bot');

                return $this->json('GET', "/api/v1/api-keys/{$key->accessToken->id}");
            }),

            'api-keys-create' => $this->asSomebody(fn (): TestResponse => $this->json('POST', '/api/v1/api-keys', [
                'name' => 'Dundie awards bot',
            ])),

            'api-keys-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $key = $user->createToken('Dundie awards bot');

                return $this->json('DELETE', "/api/v1/api-keys/{$key->accessToken->id}");
            }),

            'logs-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->aLog($user);

                return $this->json('GET', '/api/v1/logs');
            }),

            'logs-show' => $this->asSomebody(function (User $user): TestResponse {
                $log = $this->aLog($user);

                return $this->json('GET', "/api/v1/logs/{$log->id}");
            }),

            'emails-sent-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->anEmail($user);

                return $this->json('GET', '/api/v1/emails-sent');
            }),

            'emails-sent-show' => $this->asSomebody(function (User $user): TestResponse {
                $email = $this->anEmail($user);

                return $this->json('GET', "/api/v1/emails-sent/{$email->id}");
            }),

            'vaults-index' => $this->asSomebody(function (User $user): TestResponse {
                Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', '/api/v1/vaults');
            }),

            'vaults-show' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}");
            }),

            'vaults-create' => $this->asSomebody(fn (): TestResponse => $this->json('POST', '/api/v1/vaults', [
                'name' => 'Dunder Mifflin',
            ])),

            'vaults-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
                    'name' => 'Scranton',
                    'description' => null,
                    'colour' => '#9a3f2b',
                ]);
            }),

            'vaults-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}");
            }),

            default => null,
        };
    }

    /**
     * @param  callable(User): TestResponse  $call
     */
    private function asSomebody(callable $call): TestResponse
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $call($user);
    }

    private function loggingIn(): TestResponse
    {
        User::factory()->create([
            'email' => 'dwight@schrutefarms.test',
            'password' => Hash::make('a-very-strong-password'),
        ]);

        return $this->json('POST', '/api/v1/login', [
            'email' => 'dwight@schrutefarms.test',
            'password' => 'a-very-strong-password',
        ]);
    }

    /**
     * A log entry that carries something in its parameters, so that the field the
     * reference does not look inside is exercised rather than assumed.
     */
    private function aLog(User $user): Log
    {
        return Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'parameters' => ['name' => 'Dunder Mifflin'],
        ]);
    }

    /**
     * An email that arrived, so that the moments it carries are numbers rather
     * than nothing on both of the endpoints that describe it.
     */
    private function anEmail(User $user): EmailSent
    {
        return EmailSent::factory()->delivered()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);
    }
}
