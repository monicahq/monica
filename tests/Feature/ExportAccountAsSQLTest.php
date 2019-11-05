<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Jobs\ExportAccountAsSQL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportAccountAsSQLTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_exports_account_file()
    {
        Storage::fake();
        Storage::fake('local');

        $user = $this->signIn();

        $path = dispatch_now(new ExportAccountAsSQL());

        $this->assertStringStartsWith('exports/', $path);
        $this->assertStringEndsWith('.sql', $path);
        Storage::disk('public')->assertExists($path);
    }
}
