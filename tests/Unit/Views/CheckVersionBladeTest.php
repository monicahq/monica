<?php

namespace Tests\Unit\Views;

use Tests\TestCase;
use App\Models\Instance\Instance;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CheckVersionBladeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_escapes_release_notes_fetched_from_the_version_server()
    {
        config(['monica.check_version' => true]);
        config(['monica.app_version' => '2.9.0']);

        Instance::all()->each(function ($instance) {
            $instance->delete();
        });

        $instance = factory(Instance::class)->create([
            'latest_version' => '3.1.0',
            'number_of_versions_since_current_version' => 2,
            'latest_release_notes' => '<script>alert(document.cookie)</script>',
        ]);

        $html = view('partials.check', ['instance' => $instance])->render();

        $this->assertStringNotContainsString('<script>alert(document.cookie)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(document.cookie)&lt;/script&gt;', $html);
    }
}
