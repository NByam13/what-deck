<?php

namespace Tests\Feature;

use App\Models\CardInstance;
use App\Models\User;
use App\Models\Card;
use App\Models\Collection;
use App\Models\Deck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardInstanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_card_instances()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        CardInstance::factory()->count(3)->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id
        ]);

        $response = $this->getJson('/api/card-instances');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'card_id',
                            'collection_id',
                            'deck_id',
                            'condition',
                            'foil',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    public function test_can_create_card_instance()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        
        $instanceData = [
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'condition' => 'near_mint',
            'foil' => true
        ];

        $response = $this->postJson('/api/card-instances', $instanceData);

        $response->assertStatus(201)
                ->assertJson([
                    'card_id' => $card->id,
                    'collection_id' => $collection->id,
                    'condition' => 'near_mint',
                    'foil' => true
                ]);

        $this->assertDatabaseHas('card_instances', $instanceData);
    }

    public function test_can_show_card_instance()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        $cardInstance = CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id
        ]);

        $response = $this->getJson("/api/card-instances/{$cardInstance->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $cardInstance->id,
                    'card_id' => $card->id,
                    'collection_id' => $collection->id
                ]);
    }

    public function test_can_update_card_instance()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        $cardInstance = CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'condition' => 'near_mint'
        ]);

        $updateData = ['condition' => 'lightly_played'];

        $response = $this->putJson("/api/card-instances/{$cardInstance->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson(['condition' => 'lightly_played']);

        $this->assertDatabaseHas('card_instances', [
            'id' => $cardInstance->id,
            'condition' => 'lightly_played'
        ]);
    }

    public function test_can_delete_card_instance()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        $cardInstance = CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id
        ]);

        $response = $this->deleteJson("/api/card-instances/{$cardInstance->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('card_instances', ['id' => $cardInstance->id]);
    }

    public function test_can_move_card_instance_to_deck()
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

        $response = $this->putJson("/api/card-instances/{$cardInstance->id}/move-to-deck/{$deck->id}");

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

        $response = $this->putJson("/api/card-instances/{$cardInstance->id}/remove-from-deck");

        $response->assertStatus(200)
                ->assertJson(['deck_id' => null]);

        $this->assertDatabaseHas('card_instances', [
            'id' => $cardInstance->id,
            'deck_id' => null
        ]);
    }

    public function test_cannot_move_card_to_deck_with_different_user()
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

        $response = $this->putJson("/api/card-instances/{$cardInstance->id}/move-to-deck/{$deck->id}");

        $response->assertStatus(403)
                ->assertJson(['message' => 'Deck and collection must belong to the same user']);
    }

    public function test_can_filter_card_instances_by_collection()
    {
        $user = User::factory()->create();
        $collection1 = Collection::factory()->create(['user_id' => $user->id]);
        $collection2 = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        
        CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection1->id
        ]);
        CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection2->id
        ]);

        $response = $this->getJson("/api/card-instances?collection_id={$collection1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals($collection1->id, $data[0]['collection_id']);
    }

    public function test_can_filter_available_card_instances()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $deck = Deck::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        
        // Create one available and one assigned instance
        CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'deck_id' => null
        ]);
        CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'deck_id' => $deck->id
        ]);

        $response = $this->getJson('/api/card-instances?available=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertNull($data[0]['deck_id']);
    }

    public function test_validates_card_instance_creation()
    {
        $response = $this->postJson('/api/card-instances', [
            'card_id' => 999,  // Non-existent card
            'collection_id' => 999,  // Non-existent collection
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['card_id', 'collection_id']);
    }

    public function test_validates_condition_values()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        
        $response = $this->postJson('/api/card-instances', [
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'condition' => 'invalid_condition'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['condition']);
    }
}
