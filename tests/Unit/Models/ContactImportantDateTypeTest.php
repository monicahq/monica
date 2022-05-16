<?php

namespace Tests\Unit\Models;

use App\Models\ContactImportantDateType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
