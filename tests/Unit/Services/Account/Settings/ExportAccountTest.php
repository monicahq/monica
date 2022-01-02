<?php

namespace Tests\Unit\Services\Account\Settings;

use Tests\TestCase;
use Mockery\MockInterface;
use App\Jobs\ExportAccount;
use App\Models\Contact\Contact;
use App\Models\Account\ExportJob;
use Illuminate\Support\Facades\Storage;
use App\Notifications\ExportAccountDone;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\AssertableJsonString;
use App\Services\Account\Settings\SqlExportAccount;
use App\Services\Account\Settings\JsonExportAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_exports_account_json()
    {
        Notification::fake();

        Storage::fake();
        $fake = Storage::fake('local');
        $fake->put('temp/test.json', 'null');

        $job = ExportJob::factory()->create();

        $this->mock(JsonExportAccount::class, function (MockInterface $mock) use ($job) {
            $mock->shouldReceive('execute')
                ->once()
                ->with([
                    'account_id' => $job->account_id,
                    'user_id' => $job->user_id,
                ])
                ->andReturn('temp/test.json');
        });

        ExportAccount::dispatchSync($job);
        $job->refresh();

        Storage::disk('public')->assertExists($job->filename);

        Notification::assertSentTo(
            [$job->user], ExportAccountDone::class
        );
    }

    /** @test */
    public function it_exports_account_sql()
    {
        Notification::fake();

        Storage::fake();
        $fake = Storage::fake('local');
        $fake->put('temp/test.sql', 'null');

        $job = ExportJob::factory()->create([
            'type' => 'sql',
        ]);

        $this->mock(SqlExportAccount::class, function (MockInterface $mock) use ($job) {
            $mock->shouldReceive('execute')
                ->once()
                ->with([
                    'account_id' => $job->account_id,
                    'user_id' => $job->user_id,
                ])
                ->andReturn('temp/test.sql');
        });

        ExportAccount::dispatchSync($job);
        $job->refresh();

        Storage::disk('public')->assertExists($job->filename);

        Notification::assertSentTo(
            [$job->user], ExportAccountDone::class
        );
    }

    /** @test */
    public function it_exports_account_file()
    {
        Storage::fake();
        Storage::fake('local');

        $job = ExportJob::factory()->create();
        ExportAccount::dispatchSync($job);

        $job->refresh();

        $this->assertStringStartsWith('exports/', $job->filename);
        $this->assertStringEndsWith('.json', $job->filename);
        Storage::disk('public')->assertExists($job->filename);
    }

    /** @test */
    public function it_exports_account_file_sql()
    {
        Storage::fake();
        Storage::fake('local');

        $job = ExportJob::factory()->create([
            'type' => 'sql',
        ]);
        ExportAccount::dispatchSync($job);

        $job->refresh();

        $this->assertStringStartsWith('exports/', $job->filename);
        $this->assertStringEndsWith('.sql', $job->filename);
        Storage::disk('public')->assertExists($job->filename);
    }

    /** @test */
    public function it_exports_json_file()
    {
        Storage::fake();
        Storage::fake('local');

        $job = ExportJob::factory()->create();
        ExportAccount::dispatchSync($job);

        $job->refresh();

        $this->assertStringStartsWith('exports/', $job->filename);
        $this->assertStringEndsWith('.json', $job->filename);
        Storage::disk('public')->assertExists($job->filename);

        $json = Storage::disk('public')->get($job->filename);
        $test = new AssertableJsonString($json);

        $test->assertStructure([
            'account' => [
                'uuid',
                'created_at',
                'updated_at',
                'data' => [
                    '*' => [
                        'count',
                        'type',
                        'values' => [
                            '*' => [
                                'uuid',
                                'created_at',
                                'updated_at',
                                'properties',
                            ],
                        ],
                    ],
                ],
                'properties' => [
                    'modules' => [
                        '*' => [
                            'key',
                            'translation_key',
                            'created_at',
                            'updated_at',
                            'properties',
                        ],
                    ],
                    'reminder_rules' => [
                        '*' => [
                            'number_of_days_before',
                            'created_at',
                            'updated_at',
                            'properties',
                        ],
                    ],
                ],
                'instance' => [
                    'activity_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'translation_key',
                                'category',
                            ],
                        ],
                    ],
                    'activity_type_categories' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'translation_key',
                            ],
                        ],
                    ],
                    'life_event_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'translation_key',
                                'core_monica_data',
                                'category',
                            ],
                        ],
                    ],
                    'life_event_categories' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'translation_key',
                                'core_monica_data',
                            ],
                        ],
                    ],
                    'contact_field_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'fontawesome_icon',
                                'delible',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_exports_json_file_contacts()
    {
        Storage::fake();
        Storage::fake('local');

        $job = ExportJob::factory()->create();
        factory(Contact::class, 5)->create([
            'account_id' => $job->account->id,
        ]);

        ExportAccount::dispatchSync($job);

        $job->refresh();

        $this->assertStringStartsWith('exports/', $job->filename);
        $this->assertStringEndsWith('.json', $job->filename);
        Storage::disk('public')->assertExists($job->filename);

        $json = Storage::disk('public')->get($job->filename);
        $test = new AssertableJsonString($json);

        $test->assertStructure([
            'account' => [
                'uuid',
                'created_at',
                'updated_at',
                'data' => [
                    '*' => [
                        'count',
                        'type',
                        'values' => [
                            '*' => [
                                'uuid',
                                'created_at',
                                'updated_at',
                                'properties',
                            ],
                        ],
                    ],
                ],
                'properties' => [
                    'modules' => [
                        '*' => [
                            'key',
                            'translation_key',
                            'created_at',
                            'updated_at',
                            'properties',
                        ],
                    ],
                    'reminder_rules' => [
                        '*' => [
                            'number_of_days_before',
                            'created_at',
                            'updated_at',
                            'properties',
                        ],
                    ],
                ],
                'instance' => [
                    'activity_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'translation_key',
                                'category',
                            ],
                        ],
                    ],
                    'activity_type_categories' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'translation_key',
                            ],
                        ],
                    ],
                    'life_event_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'translation_key',
                                'core_monica_data',
                                'category',
                            ],
                        ],
                    ],
                    'life_event_categories' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'translation_key',
                                'core_monica_data',
                            ],
                        ],
                    ],
                    'contact_field_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'fontawesome_icon',
                                'delible',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
