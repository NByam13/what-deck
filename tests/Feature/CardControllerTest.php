<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use App\Models\Collection;
use App\Models\CardInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_cards()
    {
        // Create test cards
        Card::factory()->count(3)->create();

        $response = $this->getJson('/api/cards');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'type',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    public function test_can_create_card()
    {
        $cardData = [
            'title' => 'Lightning Bolt',
            'description' => 'Lightning Bolt deals 3 damage to any target.',
            'cost' => 'R',
            'type' => 'Instant',
            'subtype' => 'Lightning',
        ];

        $response = $this->postJson('/api/cards', $cardData);

        $response->assertStatus(201)
                ->assertJson([
                    'title' => 'Lightning Bolt',
                    'type' => 'Instant',
                    'cost' => 'R'
                ]);

        $this->assertDatabaseHas('cards', $cardData);
    }

    public function test_can_show_card()
    {
        $card = Card::factory()->create();

        $response = $this->getJson("/api/cards/{$card->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $card->id,
                    'title' => $card->title,
                    'type' => $card->type
                ]);
    }

    public function test_can_update_card()
    {
        $card = Card::factory()->create();
        $updateData = ['title' => 'Updated Card Title'];

        $response = $this->putJson("/api/cards/{$card->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson(['title' => 'Updated Card Title']);

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'title' => 'Updated Card Title'
        ]);
    }

    public function test_can_delete_card_without_instances()
    {
        $card = Card::factory()->create();

        $response = $this->deleteJson("/api/cards/{$card->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_cannot_delete_card_with_instances()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id
        ]);

        $response = $this->deleteJson("/api/cards/{$card->id}");

        $response->assertStatus(400);
        $this->assertDatabaseHas('cards', ['id' => $card->id]);
    }

    public function test_can_filter_cards_by_type()
    {
        Card::factory()->create(['type' => 'Creature']);
        Card::factory()->create(['type' => 'Instant']);

        $response = $this->getJson('/api/cards?type=Creature');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('Creature', $data[0]['type']);
    }
}
