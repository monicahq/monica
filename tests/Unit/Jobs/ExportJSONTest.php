<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ExportAccountAsJson;
use App\Jobs\ExportAccountAsSQL;
use App\Models\User\User;
use Tests\FeatureTestCase;

class ExportJSONTest extends FeatureTestCase
{
    public function test_json_header()
    {
        /** @var User $user */
        $user = factory(User::class)->create([]);
        $exportJob = new ExportAccountAsJson();

        $json_output = '';

        $this->invokePrivateMethod($exportJob, 'generateJsonHeader', [
            'json_output' => &$json_output,
            'user' => $user,
        ]);

        // Check generated string for meta attributes
        $this->assertStringContainsString('"username": "'.$user->first_name.' '.$user->last_name.'",', $json_output);
        $this->assertStringContainsString('"filename": "', $json_output);
        $this->assertStringContainsString("exported", $json_output);

        // Check that JSON is decodable (this requires adjusting the output slightly)
        $json_output = trim($json_output, ', \t\n\r').'}';

        $json_result = json_decode($json_output);
        $this->assertObjectHasAttribute('export_meta', $json_result);
        $this->assertObjectHasAttribute('username', $json_result->export_meta);
    }

    public function test_empty_table_export()
    {
        /** @var User $user */
        $user = factory(User::class)->create([]);
        $exportJob = new ExportAccountAsJson();

        $json_output = '';

        $this->invokePrivateMethod($exportJob, 'processTable', [
            'json_output' => &$json_output,
            'account' => $user->account(),
            'tableName' => 'activities'
        ]);

        $this->assertEquals("\n  \"activities\": [],", $json_output);
    }

    public function test_blacklisted_table_export()
    {
        /** @var User $user */
        $user = factory(User::class)->create([]);
        $exportJob = new ExportAccountAsJson();

        $json_output = '';
        $tableName = ExportAccountAsSQL::IGNORED_TABLES[array_rand(ExportAccountAsSQL::IGNORED_TABLES)];

        $this->invokePrivateMethod($exportJob, 'processTable', [
            'json_output' => &$json_output,
            'account' => $user->account(),
            'tableName' => $tableName,
        ]);

        $this->assertEquals('', $json_output);
    }
}
