<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PaginatorHelper;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaginatorHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_an_array_containing_everything_needed_for_a_pagination()
    {
        $vault = Vault::factory()->create([]);
        Contact::factory()->count(3)->create(['vault_id' => $vault->id]);

        $contacts = Contact::where('vault_id', $vault->id)
            ->paginate(1);

        $this->assertEquals(
            [
                'count' => 1,
                'currentPage' => 1,
                'firstItem' => 1,
                'firstPageUrl' => config('app.url').'?page=1',
                'hasMorePages' => true,
                'lastItem' => 1,
                'lastPage' => 3,
                'lastPageUrl' => config('app.url').'?page=3',
                'links' => [
                    [
                        'url' => null,
                        'label' => '❮ Previous',
                        'active' => false,
                    ],
                    [
                        'url' => config('app.url').'?page=1',
                        'label' => '1',
                        'active' => true,
                    ],
                    [
                        'url' => config('app.url').'?page=2',
                        'label' => '2',
                        'active' => false,
                    ],
                    [
                        'url' => config('app.url').'?page=3',
                        'label' => '3',
                        'active' => false,
                    ],
                    [
                        'url' => config('app.url').'?page=2',
                        'label' => 'Next ❯',
                        'active' => false,
                    ],
                ],
                'nextPageUrl' => config('app.url').'?page=2',
                'onFirstPage' => true,
                'path' => config('app.url'),
                'perPage' => 1,
                'previousPageUrl' => null,
                'total' => 3,
            ],
            PaginatorHelper::getData($contacts)
        );
    }
}
