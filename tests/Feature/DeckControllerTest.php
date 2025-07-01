<?php

namespace Tests\Feature;

use App\Models\Deck;
use App\Models\User;
use App\Models\Card;
use App\Models\Collection;
use App\Models\CardInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeckControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_decks()
    {
        $user = User::factory()->create();
        Deck::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/decks');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'user_id',
                            'format',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    public function test_can_create_deck()
    {
        $user = User::factory()->create();
        $deckData = [
            'user_id' => $user->id,
            'name' => 'Lightning Deck',
            'description' => 'A fast burn deck',
            'format' => 'Standard'
        ];

        $response = $this->postJson('/api/decks', $deckData);

        $response->assertStatus(201)
                ->assertJson([
                    'name' => 'Lightning Deck',
                    'format' => 'Standard',
                    'user_id' => $user->id
                ]);

        $this->assertDatabaseHas('decks', $deckData);
    }

    public function test_can_show_deck()
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/decks/{$deck->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $deck->id,
                    'name' => $deck->name,
                    'user_id' => $user->id
                ]);
    }

    public function test_can_update_deck()
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->create(['user_id' => $user->id]);
        $updateData = ['name' => 'Updated Deck Name'];

        $response = $this->putJson("/api/decks/{$deck->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson(['name' => 'Updated Deck Name']);

        $this->assertDatabaseHas('decks', [
            'id' => $deck->id,
            'name' => 'Updated Deck Name'
        ]);
    }

    public function test_can_delete_deck()
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/decks/{$deck->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('decks', ['id' => $deck->id]);
    }

    public function test_can_add_card_instance_to_deck()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $deck = Deck::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        $cardInstance = CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'deck_id' => null
        ]);

        $response = $this->postJson("/api/decks/{$deck->id}/add-card-instance/{$cardInstance->id}");

        $response->assertStatus(200)
                ->assertJson(['deck_id' => $deck->id]);

        $this->assertDatabaseHas('card_instances', [
            'id' => $cardInstance->id,
            'deck_id' => $deck->id
        ]);
    }

    public function test_can_remove_card_instance_from_deck()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $deck = Deck::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        $cardInstance = CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'deck_id' => $deck->id
        ]);

        $response = $this->deleteJson("/api/decks/{$deck->id}/remove-card-instance/{$cardInstance->id}");

        $response->assertStatus(200)
                ->assertJson(['deck_id' => null]);

        $this->assertDatabaseHas('card_instances', [
            'id' => $cardInstance->id,
            'deck_id' => null
        ]);
    }

    public function test_cannot_add_card_instance_from_different_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $collection = Collection::factory()->create(['user_id' => $user1->id]);
        $deck = Deck::factory()->create(['user_id' => $user2->id]);
        $card = Card::factory()->create();
        $cardInstance = CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id
        ]);

        $response = $this->postJson("/api/decks/{$deck->id}/add-card-instance/{$cardInstance->id}");

        $response->assertStatus(403)
                ->assertJson(['message' => 'Deck and card instance must belong to the same user']);
    }

    public function test_cannot_add_already_assigned_card_instance()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $deck1 = Deck::factory()->create(['user_id' => $user->id]);
        $deck2 = Deck::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        $cardInstance = CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'deck_id' => $deck1->id
        ]);

        $response = $this->postJson("/api/decks/{$deck2->id}/add-card-instance/{$cardInstance->id}");

        $response->assertStatus(400)
                ->assertJson(['message' => 'Card instance is already assigned to a deck']);
    }

    public function test_can_filter_decks_by_format()
    {
        $user = User::factory()->create();
        Deck::factory()->create(['user_id' => $user->id, 'format' => 'Standard']);
        Deck::factory()->create(['user_id' => $user->id, 'format' => 'Modern']);

        $response = $this->getJson('/api/decks?format=Standard');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('Standard', $data[0]['format']);
    }

    public function test_can_get_deck_card_instances()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $deck = Deck::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        CardInstance::factory()->count(3)->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'deck_id' => $deck->id
        ]);

        $response = $this->getJson("/api/decks/{$deck->id}/card-instances");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }
}
