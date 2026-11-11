<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Item> */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'code' => strtoupper(fake()->unique()->bothify('???-###')),
            'name' => 'Alat '.fake()->unique()->word(),
            'summary' => fake()->sentence(4),
            'description' => fake()->sentence(12),
            'specs' => ['Merek' => 'Contoh'],
            'checklist_template' => ['Unit lengkap', 'Tanpa goresan'],
            'location' => 'Ruang Sarpras',
            'max_loan_days' => 7,
            'max_qty_per_loan' => 2,
            'min_stock' => 1,
            'status' => 'active',
        ];
    }

    /** Buat alat beserta N unit fisik berstatus available. */
    public function withUnits(int $count): static
    {
        return $this->afterCreating(function (Item $item) use ($count) {
            for ($i = 1; $i <= $count; $i++) {
                ItemUnit::factory()->create([
                    'item_id' => $item->id,
                    'unit_code' => sprintf('%s-%02d', $item->code, $i),
                ]);
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
