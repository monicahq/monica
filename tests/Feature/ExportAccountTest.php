<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportAccountTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_exports_account_file()
    {
        Storage::fake();
        Storage::fake('local');

        $user = $this->signIn();

        $path = ExportAccountAsSQL::dispatchSync();

        $this->assertStringStartsWith('exports/', $path);
        $this->assertStringEndsWith('.sql', $path);
        Storage::disk('public')->assertExists($path);
    }
}
