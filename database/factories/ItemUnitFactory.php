<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ItemUnit> */
class ItemUnitFactory extends Factory
{
    protected $model = ItemUnit::class;

    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'unit_code' => strtoupper(fake()->unique()->bothify('UNT-####')),
            'status' => 'available',
        ];
    }
}
