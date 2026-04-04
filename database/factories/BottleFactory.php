<?php

namespace Database\Factories;

use App\Models\Bottle;
use Illuminate\Database\Eloquent\Factories\Factory;

class BottleFactory extends Factory
{
    protected $model = Bottle::class;

    public function definition(): array
    {
        return [
            'supplier_name' => 'Eden Botanicals',
            'supplier_url' => 'http://www.edenbotanicals.com',
            'batch_code' => 'AB1234',
            'method' => 'steam_distilled',
            'plant_part' => 'leaves',
            'origin_country' => 'Morocco',
            'distillation_date' => '2021-01-30',
            'purchase_date' => '2025-03-01',
            'volume_ml' => 10,
            'density' => 0.912,
            'price' => 4.99,
            'notes' => 'test notes',
            'is_finished' => false,
        ];
    }
}
