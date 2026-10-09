<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Priority;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConfigurationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_create_five_distinct_valid_records_and_one_default(): void
    {
        $categories = Category::factory()->count(5)->create();
        $priorities = Priority::factory()->count(4)->create();
        $priorities->push(Priority::factory()->create(['is_default' => true]));

        $this->assertCount(5, $categories->pluck('name')->unique());
        $this->assertCount(5, $priorities->pluck('name')->unique());
        $this->assertSame(1, Priority::where('is_default', true)->count());
        foreach ($categories as $category) {
            $this->assertTrue($category->fresh()->is_active);
        }
        foreach ($priorities as $priority) {
            $this->assertGreaterThan(0, $priority->fresh()->sla_hours);
            $this->assertGreaterThan(0, $priority->rank);
            $this->assertTrue($priority->is_active);
            $this->assertIsBool($priority->is_default);
        }
    }

    public function test_database_rejects_duplicate_category_names(): void
    {
        $category = Category::factory()->create();
        $this->expectException(QueryException::class);
        Category::factory()->create(['name' => $category->name]);
    }

    public function test_database_rejects_duplicate_priority_names(): void
    {
        $priority = Priority::factory()->create();
        $this->expectException(QueryException::class);
        Priority::factory()->create(['name' => $priority->name]);
    }

    #[DataProvider('invalidPositiveIntegers')]
    public function test_database_rejects_nonpositive_priority_values(string $column, int $value): void
    {
        $this->expectException(QueryException::class);
        Priority::factory()->create([$column => $value]);
    }

    public static function invalidPositiveIntegers(): array
    {
        return [['sla_hours', 0], ['sla_hours', -1], ['rank', 0], ['rank', -1]];
    }
}
