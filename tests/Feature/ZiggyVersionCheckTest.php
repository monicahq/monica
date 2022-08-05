<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class ZiggyVersionCheckTest extends TestCase
{
    /**
     * @test
     */
    public function it_ckecks_ziggy_version_are_same()
    {
        //# Get composer ziggy version
        exec('composer show tightenco/ziggy --format=json', $composer);
        $composerJson = json_decode(implode('', $composer));

        $composerVersion = Str::of($composerJson->versions[0])->trim('v');

        //# Get yarn ziggy version
        exec('yarn list --pattern ziggy-js --depth=0 --json --non-interactive --no-progress', $yarn);
        $yarnJson = json_decode(implode('', $yarn));

        $yarnVersion = Str::of($yarnJson->data->trees[0]->name)->replace('ziggy-js@', '');

        $this->assertEquals($composerVersion, $yarnVersion);
    }
}
