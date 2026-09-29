<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeContactListViewControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'contact_list_grouping',
                'contact_list_sort',
                'contact_list_direction',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_sets_the_view_the_contact_list_opens_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.me.contactListView.update'), [
            'grouping' => 'favourites',
            'sort' => 'last-spoke',
            'direction' => 'desc',
        ]);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.contact_list_grouping', 'favourites');
        $response->assertJsonPath('data.attributes.contact_list_sort', 'last-spoke');
        $response->assertJsonPath('data.attributes.contact_list_direction', 'desc');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'contact_list_grouping' => 'favourites',
            'contact_list_sort' => 'last-spoke',
            'contact_list_direction' => 'desc',
        ]);
    }

    #[Test]
    #[DataProvider('choicesTheProductDoesNotKnow')]
    public function it_refuses_a_choice_the_product_does_not_know(string $field, string $value): void
    {
        $user = User::factory()->create(['contact_list_grouping' => 'names-only']);
        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.me.contactListView.update'), [
            'grouping' => 'favourites',
            'sort' => 'name',
            'direction' => 'asc',
            $field => $value,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($field);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'contact_list_grouping' => 'names-only',
        ]);
    }

    /** @return array<string, array{string, string}> */
    public static function choicesTheProductDoesNotKnow(): array
    {
        return [
            'a grouping nobody offers' => ['grouping', 'beets'],
            'a grouping no longer offered' => ['grouping', 'archived'],
            'a column nobody sorts on' => ['sort', 'shoe-size'],
            'a direction nothing runs in' => ['direction', 'sideways'],
        ];
    }

    #[Test]
    public function it_refuses_a_request_naming_only_part_of_the_view(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('PUT', route('api.v1.me.contactListView.update'), [
            'grouping' => 'favourites',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['sort', 'direction']);
    }
}
