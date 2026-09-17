<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityTypeFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_an_empty_layout_for_a_type_nobody_has_given_fields_to(): void
    {
        $user = User::factory()->create();
        $type = $this->activityType($user);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/activity-types/{$type->uuid}/fields");

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_writes_a_layout_and_reads_it_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = $this->activityType($user);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/fields", [
            'groups' => [
                [
                    'name' => 'Where',
                    'fields' => [
                        ['label' => 'Venue', 'type' => 'text', 'is_required' => true],
                    ],
                ],
            ],
            'fields' => [
                ['label' => 'Notes', 'type' => 'textarea'],
            ],
        ])->assertOk();

        $response = $this->json('GET', "/api/v1/activity-types/{$type->uuid}/fields");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                [
                    'type',
                    'id',
                    'attributes' => ['position', 'title', 'fields'],
                ],
            ],
        ]);

        $labels = collect($response->json('data'))
            ->flatMap(fn (array $section): array => array_column($section['attributes']['fields'], 'attributes'))
            ->pluck('label')
            ->all();

        $this->assertContains('Venue', $labels);
        $this->assertContains('Notes', $labels);
    }

    #[Test]
    public function it_files_a_grouped_field_under_the_group_it_was_sent_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = $this->activityType($user);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/fields", [
            'groups' => [
                ['name' => 'Where', 'fields' => [['label' => 'Venue', 'type' => 'text']]],
                ['name' => 'Who paid', 'fields' => [['label' => 'Payer', 'type' => 'text']]],
            ],
        ])->assertOk();

        $response = $this->json('GET', "/api/v1/activity-types/{$type->uuid}/fields");

        $named = collect($response->json('data'))
            ->mapWithKeys(fn (array $section): array => [
                $section['attributes']['title'] => array_column(
                    array_column($section['attributes']['fields'], 'attributes'),
                    'label',
                ),
            ])
            ->all();

        $this->assertSame(['Venue'], $named['Where']);
        $this->assertSame(['Payer'], $named['Who paid']);
    }

    #[Test]
    public function it_keeps_a_field_the_caller_sent_back_under_its_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = $this->activityType($user);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/fields", [
            'fields' => [['label' => 'Venue', 'type' => 'text']],
        ])->assertOk();

        $key = $this->json('GET', "/api/v1/activity-types/{$type->uuid}/fields")
            ->json('data.0.attributes.fields.0.attributes.key');

        $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/fields", [
            'fields' => [['key' => $key, 'label' => 'Place', 'type' => 'text']],
        ])->assertOk();

        $response = $this->json('GET', "/api/v1/activity-types/{$type->uuid}/fields");

        $response->assertJsonPath('data.0.attributes.fields.0.attributes.key', $key);
        $response->assertJsonPath('data.0.attributes.fields.0.attributes.label', 'Place');
    }

    #[Test]
    public function it_refuses_a_kind_of_field_the_product_does_not_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = $this->activityType($user);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/fields", [
            'fields' => [['label' => 'Venue', 'type' => 'text']],
        ])->assertOk();

        $response = $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/fields", [
            'fields' => [['label' => 'Venue', 'type' => 'not-a-kind-of-field']],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['fields.0.type']);

        $labels = $this->json('GET', "/api/v1/activity-types/{$type->uuid}/fields")
            ->json('data.0.attributes.fields.0.attributes.label');

        $this->assertSame('Venue', $labels);
    }

    #[Test]
    public function it_refuses_a_group_without_a_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = $this->activityType($user);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/fields", [
            'groups' => [['fields' => [['label' => 'Venue', 'type' => 'text']]]],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['groups.0.name']);
    }

    #[Test]
    public function it_returns_not_found_when_reading_the_fields_of_a_type_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $type = $this->activityType($other);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/activity-types/{$type->uuid}/fields")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_when_writing_the_fields_of_a_type_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $type = $this->activityType($other);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/fields", [
            'fields' => [['label' => 'Venue', 'type' => 'text']],
        ])->assertNotFound();
    }

    private function activityType(User $user): ActivityType
    {
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        return ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
        ]);
    }
}
