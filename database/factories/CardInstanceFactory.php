<?php

namespace Database\Factories;

use App\Models\CardInstance;
use App\Models\Card;
use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CardInstance>
 */
class CardInstanceFactory extends Factory
{
    protected $model = CardInstance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'collection_id' => Collection::factory(),
            'deck_id' => null, // Most instances start unassigned
            'condition' => $this->faker->randomElement(['mint', 'near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged']),
            'foil' => $this->faker->boolean(20), // 20% chance of being foil
        ];
    }

    /**
     * Create a foil card instance.
     */
    public function foil(): static
    {
        return $this->state(fn (array $attributes) => [
            'foil' => true,
        ]);
    }

    /**
     * Create a near mint card instance.
     */
    public function nearMint(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'near_mint',
        ]);
    }
}
