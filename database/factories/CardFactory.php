<?php

namespace Database\Factories;

use App\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Card>
 */
class CardFactory extends Factory
{
    protected $model = Card::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Creature', 'Instant', 'Sorcery', 'Enchantment', 'Artifact', 'Planeswalker', 'Land'];
        $type = $this->faker->randomElement($types);
        
        $subtypes = [
            'Creature' => ['Human', 'Warrior', 'Goblin', 'Elf', 'Dragon', 'Angel', 'Demon'],
            'Instant' => ['Lightning', 'Counterspell', 'Removal'],
            'Sorcery' => ['Divination', 'Wrath', 'Ramp'],
            'Enchantment' => ['Aura', 'Enchantment'],
            'Artifact' => ['Equipment', 'Vehicle'],
            'Planeswalker' => ['Jace', 'Chandra', 'Liliana'],
            'Land' => ['Plains', 'Island', 'Swamp', 'Mountain', 'Forest']
        ];

        $subtype = $type !== 'Land' ? $this->faker->randomElement($subtypes[$type] ?? []) : null;
        
        return [
            'title' => $this->faker->words(2, true),
            'image_url' => $this->faker->imageUrl(256, 356, 'mtg'),
            'description' => $this->faker->sentence(),
            'cost' => $type !== 'Land' ? $this->faker->randomElement(['R', '1U', '2G', '3', 'WW', '1BR']) : null,
            'type' => $type,
            'subtype' => $subtype,
            'power' => $type === 'Creature' ? $this->faker->numberBetween(0, 10) : null,
            'toughness' => $type === 'Creature' ? $this->faker->numberBetween(1, 10) : null,
        ];
    }

    /**
     * Create a creature card.
     */
    public function creature(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Creature',
            'subtype' => $this->faker->randomElement(['Human', 'Warrior', 'Goblin', 'Elf', 'Dragon']),
            'power' => $this->faker->numberBetween(0, 10),
            'toughness' => $this->faker->numberBetween(1, 10),
        ]);
    }

    /**
     * Create an instant card.
     */
    public function instant(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Instant',
            'subtype' => $this->faker->randomElement(['Lightning', 'Counterspell', 'Removal']),
            'power' => null,
            'toughness' => null,
        ]);
    }
}
