<?php

namespace App\Services;

use App\Models\Card;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

class ScryfallImportService
{
    protected int $batchSize = 1000;
    protected int $processed = 0;
    protected int $created = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected int $errors = 0;
    protected array $errorLog = [];
    
    // Rate limiting for Scryfall API compliance (50-100ms between requests)
    protected static float $lastRequestTime = 0;
    protected int $rateLimitMs = 100; // 100ms = 10 requests per second max

    public function __construct()
    {
        // Increase memory limit and execution time for large imports
        ini_set('memory_limit', '2G');
        set_time_limit(0);
    }

    /**
     * Import cards from Scryfall bulk data file or URL
     */
    public function import(string $source, array $options = []): array
    {
        $this->resetCounters();
        
        Log::info('Starting Scryfall import', ['source' => $source]);
        
        try {
            // Determine if source is URL or file path
            $isUrl = filter_var($source, FILTER_VALIDATE_URL);
            
            if ($isUrl) {
                return $this->importFromUrl($source, $options);
            } else {
                return $this->importFromFile($source, $options);
            }
        } catch (Exception $e) {
            Log::error('Scryfall import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Import from URL (downloads and processes streaming)
     */
    protected function importFromUrl(string $url, array $options = []): array
    {
        Log::info('Downloading Scryfall data', ['url' => $url]);
        
        // Create a temporary file for the download
        $tempFile = tempnam(sys_get_temp_dir(), 'scryfall_');
        
        try {
            // Download with progress tracking
            $this->downloadFile($url, $tempFile);
            
            // Process the downloaded file
            $result = $this->importFromFile($tempFile, $options);
            
            return $result;
        } finally {
            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Import from local file
     */
    protected function importFromFile(string $filePath, array $options = []): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        Log::info('Processing Scryfall file', [
            'file' => $filePath,
            'size' => number_format(filesize($filePath) / 1024 / 1024, 2) . ' MB'
        ]);

        // Use JSON Machine for streaming JSON parsing
        $items = Items::fromFile($filePath, [
            'decoder' => new ExtJsonDecoder(true) // Use ext-json for better performance
        ]);

        $batch = [];
        $batchCount = 0;

        DB::beginTransaction();
        
        try {
            foreach ($items as $cardData) {
                $this->processed++;
                
                // Process individual card
                $processedCard = $this->processCard($cardData, $options);
                
                if ($processedCard) {
                    $batch[] = $processedCard;
                    $batchCount++;
                    
                    // Process batch when it reaches the batch size
                    if ($batchCount >= $this->batchSize) {
                        $this->processBatch($batch);
                        $batch = [];
                        $batchCount = 0;
                        
                        // Log progress periodically
                        if ($this->processed % 10000 === 0) {
                            Log::info('Import progress', $this->getStats());
                        }
                    }
                }
            }
            
            // Process remaining batch
            if (!empty($batch)) {
                $this->processBatch($batch);
            }
            
            DB::commit();
            
            $stats = $this->getStats();
            Log::info('Scryfall import completed', $stats);
            
            return $stats;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process individual card data from Scryfall
     */
    protected function processCard(array $cardData, array $options = []): ?array
    {
        try {
            // Skip non-card objects or unwanted layouts
            if (($cardData['object'] ?? '') !== 'card') {
                $this->skipped++;
                return null;
            }

            // Skip certain layouts if specified
            $skipLayouts = $options['skip_layouts'] ?? ['token', 'emblem', 'planar', 'scheme', 'vanguard'];
            if (in_array($cardData['layout'] ?? '', $skipLayouts)) {
                $this->skipped++;
                return null;
            }

            // Map Scryfall data to our database structure
            return $this->mapScryfallData($cardData);
            
        } catch (Exception $e) {
            $this->errors++;
            $this->errorLog[] = [
                'card_id' => $cardData['id'] ?? 'unknown',
                'card_name' => $cardData['name'] ?? 'unknown',
                'error' => $e->getMessage()
            ];
            
            Log::warning('Error processing card', [
                'card_id' => $cardData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Map Scryfall data to our database structure
     */
    protected function mapScryfallData(array $data): array
    {
        // Handle card faces for double-faced cards
        $name = $data['name'] ?? '';
        $manaCost = $data['mana_cost'] ?? '';
        $oracleText = $data['oracle_text'] ?? '';
        $typeLine = $data['type_line'] ?? '';
        $power = $data['power'] ?? null;
        $toughness = $data['toughness'] ?? null;
        $flavorText = $data['flavor_text'] ?? '';

        // For double-faced cards, use the front face data
        if (isset($data['card_faces']) && is_array($data['card_faces']) && !empty($data['card_faces'])) {
            $frontFace = $data['card_faces'][0];
            $manaCost = $frontFace['mana_cost'] ?? $manaCost;
            $oracleText = $frontFace['oracle_text'] ?? $oracleText;
            $typeLine = $frontFace['type_line'] ?? $typeLine;
            $power = $frontFace['power'] ?? $power;
            $toughness = $frontFace['toughness'] ?? $toughness;
            $flavorText = $frontFace['flavor_text'] ?? $flavorText;
        }

        return [
            // Original fields (maintain backward compatibility)
            'title' => $name,
            'image_url' => $data['image_uris']['normal'] ?? null,
            'image' => $data['image_uris']['normal'] ?? null, // Duplicate for compatibility
            'description' => $oracleText,
            'cost' => $manaCost,
            'type' => $this->parseMainType($typeLine),
            'subtype' => $this->parseSubtype($typeLine),
            'power' => is_numeric($power) ? (int) $power : null,
            'toughness' => is_numeric($toughness) ? (int) $toughness : null,
            'edition' => $data['set_name'] ?? null,
            'collector_number' => $data['collector_number'] ?? null,

            // Scryfall Identifiers
            'scryfall_id' => $data['id'] ?? null,
            'oracle_id' => $data['oracle_id'] ?? null,
            'multiverse_ids' => $data['multiverse_ids'] ?? null,
            'mtgo_id' => $data['mtgo_id'] ?? null,
            'mtgo_foil_id' => $data['mtgo_foil_id'] ?? null,
            'arena_id' => $data['arena_id'] ?? null,
            'tcgplayer_id' => $data['tcgplayer_id'] ?? null,
            'tcgplayer_etched_id' => $data['tcgplayer_etched_id'] ?? null,
            'cardmarket_id' => $data['cardmarket_id'] ?? null,

            // Core Game Data
            'mana_cost' => $manaCost,
            'cmc' => $data['cmc'] ?? null,
            'oracle_text' => $oracleText,
            'flavor_text' => $flavorText,
            'type_line' => $typeLine,

            // Colors and Identity
            'colors' => $data['colors'] ?? null,
            'color_identity' => $data['color_identity'] ?? null,
            'color_indicator' => $data['color_indicator'] ?? null,
            'keywords' => $data['keywords'] ?? null,
            'produced_mana' => $data['produced_mana'] ?? null,

            // Stats and Attributes
            'loyalty' => $data['loyalty'] ?? null,
            'defense' => $data['defense'] ?? null,
            'hand_modifier' => $data['hand_modifier'] ?? null,
            'life_modifier' => $data['life_modifier'] ?? null,

            // Legalities and Rankings
            'legalities' => $data['legalities'] ?? null,
            'edhrec_rank' => $data['edhrec_rank'] ?? null,
            'penny_rank' => $data['penny_rank'] ?? null,

            // Set and Printing Info
            'set' => $data['set'] ?? null,
            'set_id' => $data['set_id'] ?? null,
            'set_name' => $data['set_name'] ?? null,
            'set_type' => $data['set_type'] ?? null,
            'rarity' => $data['rarity'] ?? null,
            'released_at' => isset($data['released_at']) ? date('Y-m-d', strtotime($data['released_at'])) : null,
            'lang' => $data['lang'] ?? 'en',

            // Visual and Physical Properties
            'image_uris' => $data['image_uris'] ?? null,
            'layout' => $data['layout'] ?? null,
            'highres_image' => $data['highres_image'] ?? false,
            'image_status' => $data['image_status'] ?? null,
            'border_color' => $data['border_color'] ?? null,
            'frame' => $data['frame'] ?? null,
            'frame_effects' => $data['frame_effects'] ?? null,
            'security_stamp' => $data['security_stamp'] ?? null,
            'watermark' => $data['watermark'] ?? null,

            // Artist Information
            'artist' => $data['artist'] ?? null,
            'artist_ids' => $data['artist_ids'] ?? null,
            'illustration_id' => $data['illustration_id'] ?? null,

            // Boolean Flags
            'reserved' => $data['reserved'] ?? false,
            'foil' => $data['foil'] ?? false,
            'nonfoil' => $data['nonfoil'] ?? true,
            'oversized' => $data['oversized'] ?? false,
            'promo' => $data['promo'] ?? false,
            'reprint' => $data['reprint'] ?? false,
            'variation' => $data['variation'] ?? false,
            'digital' => $data['digital'] ?? false,
            'full_art' => $data['full_art'] ?? false,
            'textless' => $data['textless'] ?? false,
            'booster' => $data['booster'] ?? true,
            'story_spotlight' => $data['story_spotlight'] ?? false,
            'game_changer' => $data['game_changer'] ?? false,

            // Additional Data
            'finishes' => $data['finishes'] ?? null,
            'games' => $data['games'] ?? null,
            'promo_types' => $data['promo_types'] ?? null,
            'prices' => $data['prices'] ?? null,
            'purchase_uris' => $data['purchase_uris'] ?? null,
            'related_uris' => $data['related_uris'] ?? null,
            'variation_of' => $data['variation_of'] ?? null,
            'card_back_id' => $data['card_back_id'] ?? null,

            // URIs
            'scryfall_uri' => $data['scryfall_uri'] ?? null,
            'uri' => $data['uri'] ?? null,
            'rulings_uri' => $data['rulings_uri'] ?? null,
            'prints_search_uri' => $data['prints_search_uri'] ?? null,
        ];
    }

    /**
     * Process a batch of cards
     */
    protected function processBatch(array $batch): void
    {
        foreach ($batch as $cardData) {
            // Check if card already exists (by scryfall_id)
            $existingCard = Card::where('scryfall_id', $cardData['scryfall_id'])->first();
            
            if ($existingCard) {
                // Update existing card
                $existingCard->update($cardData);
                $this->updated++;
            } else {
                // Create new card
                Card::create($cardData);
                $this->created++;
            }
        }
    }

    /**
     * Parse main type from type line
     */
    protected function parseMainType(string $typeLine): ?string
    {
        if (strpos($typeLine, '—') !== false) {
            return trim(explode('—', $typeLine)[0]);
        }
        return $typeLine ?: null;
    }

    /**
     * Parse subtype from type line
     */
    protected function parseSubtype(string $typeLine): ?string
    {
        if (strpos($typeLine, '—') !== false) {
            $parts = explode('—', $typeLine);
            return isset($parts[1]) ? trim($parts[1]) : null;
        }
        return null;
    }

    /**
     * Make a rate-limited HTTP request to Scryfall API with proper headers
     */
    protected function makeScryfallRequest(string $url, array $options = []): array
    {
        // Enforce rate limiting (Scryfall requirement: 50-100ms between requests)
        $timeSinceLastRequest = (microtime(true) - self::$lastRequestTime) * 1000;
        if ($timeSinceLastRequest < $this->rateLimitMs) {
            usleep(($this->rateLimitMs - $timeSinceLastRequest) * 1000);
        }
        
        // Required headers per Scryfall API documentation
        $response = Http::withHeaders([
            'User-Agent' => config('app.name', 'Laravel') . '/1.0 (MTG Collection API)',
            'Accept' => 'application/json',
        ])
        ->timeout(30)
        ->get($url);
        
        self::$lastRequestTime = microtime(true);
        
        if (!$response->successful()) {
            throw new Exception("Scryfall API request failed: {$response->status()} - {$response->body()}");
        }
        
        return $response->json();
    }

    /**
     * Download file with proper headers and rate limiting
     */
    protected function downloadFile(string $url, string $destination): void
    {
        // Enforce rate limiting before download
        $timeSinceLastRequest = (microtime(true) - self::$lastRequestTime) * 1000;
        if ($timeSinceLastRequest < $this->rateLimitMs) {
            usleep(($this->rateLimitMs - $timeSinceLastRequest) * 1000);
        }

        $response = Http::withHeaders([
            'User-Agent' => config('app.name', 'Laravel') . '/1.0 (MTG Collection API)',
            'Accept' => 'application/json, application/octet-stream',
        ])
        ->timeout(600) // 10 minutes for large file downloads
        ->sink($destination)
        ->get($url);
        
        self::$lastRequestTime = microtime(true);

        if (!$response->successful()) {
            throw new Exception("Failed to download file from {$url}: {$response->status()}");
        }
    }

    /**
     * Get bulk data download URL from Scryfall API
     */
    public function getBulkDataUrl(string $type = 'default_cards'): string
    {
        $data = $this->makeScryfallRequest('https://api.scryfall.com/bulk-data');

        if (!isset($data['data'])) {
            throw new Exception('Invalid bulk data response from Scryfall');
        }

        foreach ($data['data'] as $bulkData) {
            if ($bulkData['type'] === $type) {
                return $bulkData['download_uri'];
            }
        }

        throw new Exception("Bulk data type '{$type}' not found");
    }

    /**
     * Get all available bulk data types from Scryfall
     */
    public function getBulkDataInfo(): array
    {
        $data = $this->makeScryfallRequest('https://api.scryfall.com/bulk-data');

        if (!isset($data['data'])) {
            throw new Exception('Invalid bulk data response from Scryfall');
        }

        return $data['data'];
    }

    /**
     * Reset counters for new import
     */
    protected function resetCounters(): void
    {
        $this->processed = 0;
        $this->created = 0;
        $this->updated = 0;
        $this->skipped = 0;
        $this->errors = 0;
        $this->errorLog = [];
    }

    /**
     * Get import statistics
     */
    public function getStats(): array
    {
        return [
            'processed' => $this->processed,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'error_log' => $this->errorLog,
            'success_rate' => $this->processed > 0 ? round(($this->created + $this->updated) / $this->processed * 100, 2) : 0
        ];
    }

    /**
     * Set batch size for processing
     */
    public function setBatchSize(int $size): self
    {
        $this->batchSize = $size;
        return $this;
    }
} 