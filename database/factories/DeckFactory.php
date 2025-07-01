<?php

namespace Database\Factories;

use App\Models\Deck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deck>
 */
class DeckFactory extends Factory
{
    protected $model = Deck::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true) . ' Deck',
            'description' => $this->faker->sentence(),
            'format' => $this->faker->randomElement(['Standard', 'Modern', 'Legacy', 'Vintage', 'Commander', 'Pioneer']),
        ];
    }

    /**
     * Create a Commander deck.
     */
    public function commander(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => 'Commander',
            'name' => $this->faker->words(2, true) . ' Commander',
        ]);
    }

    /**
     * Create a Standard deck.
     */
    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => 'Standard',
        ]);
    }
}
