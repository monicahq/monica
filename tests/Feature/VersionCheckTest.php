<?php

namespace Tests\Feature;

use App\Instance;
use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VersionCheckTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * If an instance sets `version_check` env variable to false, the command
     * should exit with 0.
     *
     * @return void
     */
    public function test_check_version_set_to_false_disables_the_check()
    {
        putenv('CHECK_VERSION=false');

        // reload the environment as we've changed the ENV variable
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        $resultCommand = $this->artisan('monica:ping');
        $this->assertEquals(0, $resultCommand);
    }
}
