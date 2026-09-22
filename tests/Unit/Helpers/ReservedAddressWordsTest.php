<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\ContactGroupingEnum;
use App\Helpers\ReservedAddressWords;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservedAddressWordsTest extends TestCase
{
    #[Test]
    public function it_reserves_every_fixed_word_a_vault_address_could_be_taken_for(): void
    {
        foreach ($this->fixedSegmentsAfter('vaults') as $segment) {
            $this->assertContains(
                $segment,
                ReservedAddressWords::forVault(),
                "A route fixes `vaults/{$segment}`, so a vault named that way is unreachable until the word is reserved."
            );
        }
    }

    #[Test]
    public function it_reserves_every_fixed_word_a_contact_address_could_be_taken_for(): void
    {
        foreach ($this->fixedSegmentsAfter('contacts') as $segment) {
            $this->assertContains(
                $segment,
                ReservedAddressWords::forContact(),
                "A route fixes `contacts/{$segment}`, so a contact named that way is unreachable until the word is reserved."
            );
        }
    }

    #[Test]
    public function it_reads_the_routes_rather_than_a_copy_of_them(): void
    {
        $this->assertContains('new', $this->fixedSegmentsAfter('vaults'));
        $this->assertContains('favourites', $this->fixedSegmentsAfter('contacts'));
        $this->assertContains('names-only', $this->fixedSegmentsAfter('contacts'));
    }

    #[Test]
    public function it_reserves_every_grouping_the_contact_list_answers_to(): void
    {
        foreach (ContactGroupingEnum::values() as $grouping) {
            $this->assertContains($grouping, ReservedAddressWords::forContact());
        }

        $this->assertContains('all', ReservedAddressWords::forContact());
    }

    /**
     * Every word a route nails down in the place a record's own address would sit.
     *
     * A literal segment counts, and so does a parameter the route constrains to a list of literals,
     * which is how the contact list takes its four groupings in the very place a contact's address
     * goes. A literal may carry a hyphen, and `names-only` did until this read it.
     *
     * @return list<string>
     */
    private function fixedSegmentsAfter(string $marker): array
    {
        $segments = [];

        foreach (Route::getRoutes() as $route) {
            $parts = explode('/', $route->uri());
            $position = array_search($marker, $parts, true);

            if ($position === false || ! isset($parts[$position + 1])) {
                continue;
            }

            $next = $parts[$position + 1];

            if (! str_starts_with($next, '{')) {
                $segments[] = $next;

                continue;
            }

            $constraint = $route->wheres[trim($next, '{}?')] ?? null;

            if (is_string($constraint) && preg_match('/^[a-z][a-z-]*(\|[a-z][a-z-]*)*$/', $constraint) === 1) {
                $segments = [...$segments, ...explode('|', $constraint)];
            }
        }

        return array_values(array_unique($segments));
    }
}
