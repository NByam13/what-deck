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
            'language' => $this->faker->randomElement(['English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Japanese', 'Korean', 'Russian', 'Chinese Simplified']),
            'tags' => $this->faker->optional(30)->randomElements(['red', 'burn', 'instant', 'creature', 'artifact', 'legendary', 'rare'], $this->faker->numberBetween(1, 3)),
            'purchase_price' => $this->faker->optional(40)->randomFloat(2, 0.50, 100.00),
            'alter' => $this->faker->boolean(5), // 5% chance of being altered
            'proxy' => $this->faker->boolean(2), // 2% chance of being a proxy
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
