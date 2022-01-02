<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Jobs\ExportAccount;
use App\Models\Account\ExportJob;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\AssertableJsonString;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportAccountTest extends FeatureTestCase
{
    use DatabaseTransactions;

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

        $user = $this->signIn();

        factory(Contact::class, 5)->create([
            'account_id' => $user->account->id,
        ]);

        $job = ExportJob::factory()->create([
            'account_id' => $user->account->id,
            'user_id' => $user->id,
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
