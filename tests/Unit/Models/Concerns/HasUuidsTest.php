<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\HasUuids;
use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HasUuidsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_uuid_as_the_column_to_fill_rather_than_the_key(): void
    {
        $this->assertSame(['uuid'], new File()->uniqueIds());
    }

    #[Test]
    public function it_writes_a_uuid_and_leaves_the_primary_key_a_number(): void
    {
        $file = File::factory()->create();

        $this->assertIsInt($file->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $file->uuid,
        );
    }

    #[Test]
    public function it_gives_every_record_a_uuid_of_its_own(): void
    {
        $files = File::factory()->count(3)->create();

        $this->assertCount(3, $files->pluck('uuid')->unique());
    }

    #[Test]
    #[DataProvider('modelsThatUseIt')]
    public function every_model_that_uses_it_keeps_a_number_for_a_key_and_has_a_uuid_column(string $model): void
    {
        $instance = new $model;

        $this->assertSame(['uuid'], $instance->uniqueIds());
        $this->assertTrue($instance->getIncrementing());
        $this->assertSame('int', $instance->getKeyType());
        $this->assertTrue(Schema::hasColumn($instance->getTable(), 'uuid'));
    }

    /**
     * Every model that uses the trait, read off the folder rather than listed
     * here, so a model added tomorrow is covered without anybody remembering to
     * come back. A data provider runs before the application does, which is why
     * the path is built by hand instead of with `app_path()`.
     *
     * @return array<string, array{string}>
     */
    public static function modelsThatUseIt(): array
    {
        $models = [];

        foreach (glob(dirname(__DIR__, 4).'/app/Models/*.php') as $file) {
            $model = 'App\\Models\\'.basename($file, '.php');

            if (! is_subclass_of($model, Model::class)) {
                continue;
            }

            if (in_array(HasUuids::class, class_uses_recursive($model), true)) {
                $models[class_basename($model)] = [$model];
            }
        }

        return $models;
    }
}
