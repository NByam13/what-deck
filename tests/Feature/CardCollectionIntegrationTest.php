<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Card;
use App\Models\Collection;
use App\Models\Deck;
use App\Models\CardInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardCollectionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_complete_card_collection_workflow()
    {
        // 1. Create a user
        $user = User::factory()->create(['name' => 'Test User']);

        // 2. Create a card template (Lightning Bolt)
        $cardData = [
            'title' => 'Lightning Bolt',
            'description' => 'Lightning Bolt deals 3 damage to any target.',
            'cost' => 'R',
            'type' => 'Instant',
            'subtype' => 'Lightning',
        ];
        $response = $this->postJson('/api/cards', $cardData);
        $response->assertStatus(201);
        $card = Card::find($response->json('id'));

        // 3. Create a collection for the user
        $collectionData = [
            'user_id' => $user->id,
            'name' => 'My Main Collection',
            'description' => 'My primary card collection'
        ];
        $response = $this->postJson('/api/collections', $collectionData);
        $response->assertStatus(201);
        $collection = Collection::find($response->json('id'));

        // 4. Add two instances of Lightning Bolt to the collection
        $instanceData = [
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'condition' => 'near_mint',
            'foil' => false
        ];

        // Create first instance
        $response = $this->postJson('/api/card-instances', $instanceData);
        $response->assertStatus(201);
        $instance1 = CardInstance::find($response->json('id'));

        // Create second instance (foil)
        $instanceData['foil'] = true;
        $response = $this->postJson('/api/card-instances', $instanceData);
        $response->assertStatus(201);
        $instance2 = CardInstance::find($response->json('id'));

        // 5. Verify collection has both instances
        $response = $this->getJson("/api/collections/{$collection->id}/card-instances");
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        // 6. Create a deck
        $deckData = [
            'user_id' => $user->id,
            'name' => 'Lightning Deck',
            'description' => 'A deck focused on lightning spells',
            'format' => 'Standard'
        ];
        $response = $this->postJson('/api/decks', $deckData);
        $response->assertStatus(201);
        $deck = Deck::find($response->json('id'));

        // 7. Add one Lightning Bolt instance to the deck
        $response = $this->postJson("/api/decks/{$deck->id}/add-card-instance/{$instance1->id}");
        $response->assertStatus(200);
        $this->assertEquals($deck->id, $response->json('deck_id'));

        // 8. Verify deck has the card instance
        $response = $this->getJson("/api/decks/{$deck->id}/card-instances");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // 9. Verify the card instance knows it's in the deck
        $response = $this->getJson("/api/card-instances/{$instance1->id}");
        $response->assertStatus(200);
        $this->assertEquals($deck->id, $response->json('deck_id'));

        // 10. Verify the other instance is still available (not in deck)
        $response = $this->getJson("/api/card-instances/{$instance2->id}");
        $response->assertStatus(200);
        $this->assertNull($response->json('deck_id'));

        // 11. Try to add the same instance to another deck (should fail)
        $anotherDeck = Deck::factory()->create(['user_id' => $user->id]);
        $response = $this->postJson("/api/decks/{$anotherDeck->id}/add-card-instance/{$instance1->id}");
        $response->assertStatus(400);
        $response->assertJson(['message' => 'Card instance is already assigned to a deck']);

        // 12. Remove card instance from deck
        $response = $this->deleteJson("/api/decks/{$deck->id}/remove-card-instance/{$instance1->id}");
        $response->assertStatus(200);
        $this->assertNull($response->json('deck_id'));

        // 13. Verify deck is now empty
        $response = $this->getJson("/api/decks/{$deck->id}/card-instances");
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));

        // 14. Show detailed card information with all instances
        $response = $this->getJson("/api/cards/{$card->id}");
        $response->assertStatus(200);
        $cardData = $response->json();
        $this->assertEquals('Lightning Bolt', $cardData['title']);
        $this->assertCount(2, $cardData['card_instances']);

        $this->assertTrue(true, 'Complete card collection workflow test passed!');
    }

    public function test_user_cannot_add_card_instance_from_different_user_to_deck()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $collection1 = Collection::factory()->create(['user_id' => $user1->id]);
        $deck2 = Deck::factory()->create(['user_id' => $user2->id]);
        
        $card = Card::factory()->create();
        $instance = CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection1->id
        ]);

        $response = $this->postJson("/api/decks/{$deck2->id}/add-card-instance/{$instance->id}");
        
        $response->assertStatus(403)
                ->assertJson(['message' => 'Deck and card instance must belong to the same user']);
    }
}
