<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Collection;
use App\Models\Card;
use App\Models\CardInstance;
use App\Services\MoxfieldImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MoxfieldImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->collection = Collection::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function can_import_moxfield_csv_via_service()
    {
        $csvContent = $this->getSampleMoxfieldCsv();
        
        $service = new MoxfieldImportService();
        $stats = $service->import($csvContent, $this->collection);

        $this->assertEquals(4, $stats['processed']);
        $this->assertEquals(4, $stats['cards_created']); // All different cards (different editions/collector numbers)
        $this->assertEquals(0, $stats['cards_found']); // No pre-existing cards
        $this->assertEquals(4, $stats['instances_created']);
        $this->assertEmpty($stats['errors']);

        // Verify cards were created
        $this->assertDatabaseHas('cards', [
            'title' => 'Aatchik, Emerald Radian',
            'edition' => 'dft',
            'collector_number' => '187'
        ]);

        $this->assertDatabaseHas('cards', [
            'title' => 'Abhorrent Overlord',
            'edition' => 'pths',
            'collector_number' => '75★'
        ]);

        // Verify card instances were created
        $this->assertDatabaseHas('card_instances', [
            'collection_id' => $this->collection->id,
            'condition' => 'near_mint',
            'foil' => false,
            'language' => 'English',
            'alter' => false,
            'proxy' => false
        ]);

        $this->assertDatabaseHas('card_instances', [
            'collection_id' => $this->collection->id,
            'condition' => 'near_mint',
            'foil' => true,
            'language' => 'English'
        ]);
    }

    /** @test */
    public function can_import_moxfield_csv_via_api()
    {
        $csvContent = $this->getSampleMoxfieldCsv();
        
        // Create a temporary file
        $file = UploadedFile::fake()->createWithContent('moxfield_export.csv', $csvContent);

        $response = $this->postJson("/api/collections/{$this->collection->id}/import/moxfield", [
            'csv_file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'stats' => [
                    'processed',
                    'cards_created',
                    'cards_found',
                    'instances_created',
                    'errors'
                ]
            ]);

        $data = $response->json();
        $this->assertEquals(4, $data['stats']['processed']);
        $this->assertEquals(4, $data['stats']['instances_created']);
        $this->assertEmpty($data['stats']['errors']);
    }

    /** @test */
    public function handles_duplicate_cards_correctly()
    {
        // Pre-create one of the cards that will be in the CSV
        $existingCard = Card::factory()->create([
            'title' => 'Abrade',
            'edition' => 'blc',
            'collector_number' => '191'
        ]);

        $csvContent = $this->getSampleMoxfieldCsv();
        
        $service = new MoxfieldImportService();
        $stats = $service->import($csvContent, $this->collection);

        // Should find the existing card and not create a duplicate
        $this->assertEquals(1, $stats['cards_found']);
        $this->assertEquals(3, $stats['cards_created']); // 4 total - 1 found = 3 created
        
        // Verify no duplicate cards
        $this->assertEquals(1, Card::where('title', 'Abrade')->where('edition', 'blc')->count());
    }

    /** @test */
    public function validates_csv_format()
    {
        $invalidCsv = "Invalid,CSV,Format\n1,2,3";
        
        $file = UploadedFile::fake()->createWithContent('invalid.csv', $invalidCsv);

        $response = $this->postJson("/api/collections/{$this->collection->id}/import/moxfield", [
            'csv_file' => $file
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid Moxfield CSV format'
            ]);
    }

    /** @test */
    public function requires_valid_file_upload()
    {
        $response = $this->postJson("/api/collections/{$this->collection->id}/import/moxfield", [
            'csv_file' => 'not-a-file'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['csv_file']);
    }

    /** @test */
    public function handles_parsing_errors_gracefully()
    {
        $csvWithErrors = $this->getCsvWithErrors();
        
        $service = new MoxfieldImportService();
        $stats = $service->import($csvWithErrors, $this->collection);

        $this->assertNotEmpty($stats['errors']);
        $this->assertGreaterThan(0, $stats['processed']);
    }

    /** @test */
    public function can_get_supported_import_formats()
    {
        $response = $this->getJson('/api/import/formats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'formats' => [
                    '*' => [
                        'name',
                        'description',
                        'endpoint',
                        'required_columns',
                        'optional_columns',
                        'file_requirements'
                    ]
                ]
            ]);

        $data = $response->json();
        $this->assertEquals('Moxfield', $data['formats'][0]['name']);
        $this->assertContains('Count', $data['formats'][0]['required_columns']);
        $this->assertContains('Name', $data['formats'][0]['required_columns']);
    }

    /** @test */
    public function parses_tags_correctly()
    {
        $csvContent = '"Count","Name","Edition","Condition","Language","Foil","Tags","Collector Number","Alter","Proxy","Purchase Price"' . "\n" .
                      '"1","Test Card","tst","Near Mint","English","","red,burn,instant","123","False","False","5.00"';
        
        $service = new MoxfieldImportService();
        $stats = $service->import($csvContent, $this->collection);

        $cardInstance = CardInstance::where('collection_id', $this->collection->id)->first();
        $this->assertEquals(['red', 'burn', 'instant'], $cardInstance->tags);
        $this->assertEquals(5.00, $cardInstance->purchase_price);
    }

    /** @test */
    public function handles_empty_optional_fields()
    {
        $csvContent = '"Count","Name","Edition","Condition","Language","Foil","Tags","Collector Number","Alter","Proxy","Purchase Price"' . "\n" .
                      '"1","Test Card","tst","Near Mint","English","","","123","False","False",""';
        
        $service = new MoxfieldImportService();
        $stats = $service->import($csvContent, $this->collection);

        $cardInstance = CardInstance::where('collection_id', $this->collection->id)->first();
        $this->assertNull($cardInstance->tags);
        $this->assertNull($cardInstance->purchase_price);
        $this->assertFalse($cardInstance->alter);
        $this->assertFalse($cardInstance->proxy);
    }

    protected function getSampleMoxfieldCsv(): string
    {
        return '"Count","Tradelist Count","Name","Edition","Condition","Language","Foil","Tags","Last Modified","Collector Number","Alter","Proxy","Purchase Price"' . "\n" .
               '"1","1","Aatchik, Emerald Radian","dft","Near Mint","English","","","2025-02-08 04:14:24.963000","187","False","False",""' . "\n" .
               '"1","1","Abhorrent Overlord","pths","Near Mint","English","foil","","2024-12-12 16:02:09.093000","75★","False","False",""' . "\n" .
               '"1","1","Abrade","blc","Near Mint","English","","","2025-02-14 15:29:16.310000","191","False","False",""' . "\n" .
               '"1","1","Abrade","inr","Near Mint","English","","","2025-01-27 14:51:10.930000","311","False","False",""';
    }

    protected function getCsvWithErrors(): string
    {
        return '"Count","Tradelist Count","Name","Edition","Condition","Language","Foil","Tags","Last Modified","Collector Number","Alter","Proxy","Purchase Price"' . "\n" .
               '"0","1","Invalid Count Card","dft","Near Mint","English","","","2025-02-08 04:14:24.963000","187","False","False",""' . "\n" .
               '"1","1","","dft","Near Mint","English","","","2025-02-08 04:14:24.963000","187","False","False",""' . "\n" .
               '"1","1","Valid Card","dft","Near Mint","English","","","2025-02-08 04:14:24.963000","187","False","False",""';
    }
}
