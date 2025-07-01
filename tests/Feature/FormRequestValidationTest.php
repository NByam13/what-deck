<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Card;
use App\Models\Collection;
use App\Models\Deck;
use App\Models\CardInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_creation_validation()
    {
        // Test missing required fields
        $response = $this->postJson('/api/cards', []);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title', 'type']);

        // Test invalid data types and lengths
        $response = $this->postJson('/api/cards', [
            'title' => str_repeat('a', 300), // Too long
            'image_url' => 'not-a-url',
            'cost' => str_repeat('a', 100), // Too long
            'type' => '', // Empty
            'power' => -1, // Negative
            'toughness' => 'not-a-number',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'title', 'image_url', 'cost', 'type', 'power', 'toughness'
                ]);
    }

    public function test_collection_creation_validation()
    {
        // Test missing required fields
        $response = $this->postJson('/api/collections', []);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id', 'name']);

        // Test invalid user_id
        $response = $this->postJson('/api/collections', [
            'user_id' => 999, // Non-existent user
            'name' => str_repeat('a', 300), // Too long
            'description' => str_repeat('a', 1100), // Too long
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id', 'name', 'description']);
    }

    public function test_deck_creation_validation()
    {
        $user = User::factory()->create();
        
        // Test missing required fields
        $response = $this->postJson('/api/decks', []);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id', 'name']);

        // Test invalid format
        $response = $this->postJson('/api/decks', [
            'user_id' => $user->id,
            'name' => str_repeat('a', 300), // Too long
            'description' => str_repeat('a', 1100), // Too long
            'format' => 'InvalidFormat', // Not in allowed list
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'description', 'format']);
    }

    public function test_deck_valid_formats()
    {
        $user = User::factory()->create();
        
        $validFormats = [
            'Standard', 'Modern', 'Legacy', 'Vintage', 
            'Commander', 'Pioneer', 'Historic', 'Pauper', 'Limited'
        ];

        foreach ($validFormats as $format) {
            $response = $this->postJson('/api/decks', [
                'user_id' => $user->id,
                'name' => "Test {$format} Deck",
                'format' => $format
            ]);

            $response->assertStatus(201);
        }
    }

    public function test_card_instance_creation_validation()
    {
        // Test missing required fields
        $response = $this->postJson('/api/card-instances', []);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['card_id', 'collection_id']);

        // Test non-existent card and collection
        $response = $this->postJson('/api/card-instances', [
            'card_id' => 999,
            'collection_id' => 999,
            'condition' => 'invalid_condition',
            'foil' => 'not-boolean',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'card_id', 'collection_id', 'condition', 'foil'
                ]);
    }

    public function test_card_instance_valid_conditions()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        
        $validConditions = [
            'mint', 'near_mint', 'lightly_played', 
            'moderately_played', 'heavily_played', 'damaged'
        ];

        foreach ($validConditions as $condition) {
            $response = $this->postJson('/api/card-instances', [
                'card_id' => $card->id,
                'collection_id' => $collection->id,
                'condition' => $condition,
                'foil' => true
            ]);

            $response->assertStatus(201);
        }
    }

    public function test_validation_error_messages_are_descriptive()
    {
        $response = $this->postJson('/api/cards', [
            'title' => '',
            'power' => -1
        ]);

        $response->assertStatus(422);
        
        $errors = $response->json('errors');
        
        // Verify we get descriptive error messages
        $this->assertStringContainsString('title', $errors['title'][0] ?? '');
        $this->assertStringContainsString('Power', $errors['power'][0] ?? '');
    }
}
