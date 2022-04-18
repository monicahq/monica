<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ContactImportantDateType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactImportantDateTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $type = ContactImportantDateType::factory()->create();

        $this->assertTrue($type->vault()->exists());
    }
}
