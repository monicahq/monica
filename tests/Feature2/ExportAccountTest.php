<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use Illuminate\Support\Carbon;
use App\Models\Account\ExportJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportAccountTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_create_export_job_json()
    {
        config(['queue.default' => 'database']);

        $user = $this->signin();

        $response = $this->json('POST', '/settings/exportToJson');

        $response->assertStatus(302);

        $this->assertDatabaseHas('export_jobs', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'type' => ExportJob::JSON,
            'status' => ExportJob::EXPORT_TODO,
        ]);
    }

    /** @test */
    public function it_create_export_job_sql()
    {
        config(['queue.default' => 'database']);

        $user = $this->signin();

        $response = $this->json('POST', '/settings/exportToSql');

        $response->assertStatus(302);

        $this->assertDatabaseHas('export_jobs', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'type' => ExportJob::SQL,
            'status' => ExportJob::EXPORT_TODO,
        ]);
    }

    /** @test */
    public function it_delete_old_export()
    {
        config(['queue.default' => 'database']);

        $user = $this->signin();

        Carbon::setTestNow(Carbon::create(2022, 1, 1, 0, 0, 0));
        $exportJob = ExportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'status' => ExportJob::EXPORT_DONE,
        ]);

        Carbon::setTestNow(Carbon::create(2022, 1, 2, 0, 0, 0));
        ExportJob::factory()->count(4)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'status' => ExportJob::EXPORT_DONE,
        ]);

        $response = $this->json('POST', '/settings/exportToJson');

        $response->assertStatus(302);

        $this->assertDatabaseMissing('export_jobs', [
            'id' => $exportJob->id,
        ]);
    }
}
