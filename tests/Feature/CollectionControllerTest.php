<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\User;
use App\Models\Card;
use App\Models\CardInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_collections()
    {
        $user = User::factory()->create();
        Collection::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/collections');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'user_id',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    public function test_can_create_collection()
    {
        $user = User::factory()->create();
        $collectionData = [
            'user_id' => $user->id,
            'name' => 'My First Collection',
            'description' => 'This is my favorite collection'
        ];

        $response = $this->postJson('/api/collections', $collectionData);

        $response->assertStatus(201)
                ->assertJson([
                    'name' => 'My First Collection',
                    'user_id' => $user->id
                ]);

        $this->assertDatabaseHas('collections', $collectionData);
    }

    public function test_can_show_collection()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/collections/{$collection->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'user_id' => $user->id
                ]);
    }

    public function test_can_update_collection()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $updateData = ['name' => 'Updated Collection Name'];

        $response = $this->putJson("/api/collections/{$collection->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson(['name' => 'Updated Collection Name']);

        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'name' => 'Updated Collection Name'
        ]);
    }

    public function test_can_delete_empty_collection()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/collections/{$collection->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
    }

    public function test_cannot_delete_collection_with_card_instances()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        CardInstance::factory()->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id
        ]);

        $response = $this->deleteJson("/api/collections/{$collection->id}");

        $response->assertStatus(400);
        $this->assertDatabaseHas('collections', ['id' => $collection->id]);
    }

    public function test_can_get_collection_card_instances()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $user->id]);
        $card = Card::factory()->create();
        CardInstance::factory()->count(3)->create([
            'card_id' => $card->id,
            'collection_id' => $collection->id
        ]);

        $response = $this->getJson("/api/collections/{$collection->id}/card-instances");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }
}
