<?php

namespace App\Services;

use App\Models\Card;
use App\Models\CardInstance;
use App\Models\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class MoxfieldImportService
{
    protected array $stats = [
        'processed' => 0,
        'cards_created' => 0,
        'cards_found' => 0,
        'instances_created' => 0,
        'errors' => [],
    ];

    protected array $conditionMapping = [
        'Mint' => 'mint',
        'Near Mint' => 'near_mint',
        'Lightly Played' => 'lightly_played',
        'Moderately Played' => 'moderately_played',
        'Heavily Played' => 'heavily_played',
        'Damaged' => 'damaged',
    ];

    /**
     * Import cards from Moxfield CSV content.
     */
    public function import(string $csvContent, Collection $collection): array
    {
        $this->resetStats();

        try {
            DB::beginTransaction();

            $rows = $this->parseCsv($csvContent);
            
            foreach ($rows as $index => $row) {
                try {
                    $this->processRow($row, $collection, $index + 1);
                } catch (Exception $e) {
                    $this->addError($index + 1, "Error processing row: " . $e->getMessage());
                    Log::error("Moxfield import error on row " . ($index + 1), [
                        'error' => $e->getMessage(),
                        'row' => $row,
                    ]);
                }
            }

            DB::commit();
            
            Log::info('Moxfield import completed', $this->stats);
            
        } catch (Exception $e) {
            DB::rollBack();
            $this->addError(0, "Critical import error: " . $e->getMessage());
            Log::error('Moxfield import failed', [
                'error' => $e->getMessage(),
                'stats' => $this->stats,
            ]);
        }

        return $this->stats;
    }

    /**
     * Parse CSV content into array of rows.
     */
    protected function parseCsv(string $csvContent): array
    {
        $lines = explode("\n", trim($csvContent));
        $header = null;
        $rows = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $data = str_getcsv($line);
            
            if ($header === null) {
                $header = $data;
                continue;
            }

            if (count($data) === count($header)) {
                $rows[] = array_combine($header, $data);
            }
        }

        return $rows;
    }

    /**
     * Process a single CSV row.
     */
    protected function processRow(array $row, Collection $collection, int $rowNumber): void
    {
        $this->stats['processed']++;

        // Extract required fields
        $count = (int) $row['Count'];
        $name = trim($row['Name']);
        $edition = trim($row['Edition']);
        $condition = trim($row['Condition']);
        $language = trim($row['Language']);
        $foil = strtolower(trim($row['Foil'])) === 'foil';
        $tags = $this->parseTags($row['Tags'] ?? '');
        $collectorNumber = trim($row['Collector Number']);
        $alter = strtolower(trim($row['Alter'] ?? '')) === 'true';
        $proxy = strtolower(trim($row['Proxy'] ?? '')) === 'true';
        $purchasePrice = $this->parsePurchasePrice($row['Purchase Price'] ?? '');

        // Validate required fields
        if (empty($name)) {
            throw new Exception("Card name is required");
        }

        if ($count <= 0) {
            throw new Exception("Count must be greater than 0");
        }

        // Find or create the card
        $card = $this->findOrCreateCard($name, $edition, $collectorNumber);

        // Create card instances
        $mappedCondition = $this->mapCondition($condition);
        
        for ($i = 0; $i < $count; $i++) {
            $this->createCardInstance($card, $collection, [
                'condition' => $mappedCondition,
                'foil' => $foil,
                'language' => $language ?: 'English',
                'tags' => $tags,
                'alter' => $alter,
                'proxy' => $proxy,
                'purchase_price' => $purchasePrice,
            ]);
        }
    }

    /**
     * Find or create a card record.
     */
    protected function findOrCreateCard(string $name, string $edition, string $collectorNumber): Card
    {
        // Try to find existing card by name, edition, and collector number
        $card = Card::where('title', $name)
            ->where('edition', $edition)
            ->where('collector_number', $collectorNumber)
            ->first();

        if ($card) {
            $this->stats['cards_found']++;
            return $card;
        }

        // Create new card
        $card = Card::create([
            'title' => $name,
            'edition' => $edition,
            'collector_number' => $collectorNumber,
            'type' => 'Unknown', // Required field, will need to be updated manually or via API
        ]);

        $this->stats['cards_created']++;
        return $card;
    }

    /**
     * Create a card instance.
     */
    protected function createCardInstance(Card $card, Collection $collection, array $attributes): CardInstance
    {
        $instance = CardInstance::create([
            'card_id' => $card->id,
            'collection_id' => $collection->id,
            'condition' => $attributes['condition'],
            'foil' => $attributes['foil'],
            'language' => $attributes['language'],
            'tags' => $attributes['tags'],
            'alter' => $attributes['alter'],
            'proxy' => $attributes['proxy'],
            'purchase_price' => $attributes['purchase_price'],
        ]);

        $this->stats['instances_created']++;
        return $instance;
    }

    /**
     * Map Moxfield condition to our condition enum.
     */
    protected function mapCondition(string $condition): string
    {
        return $this->conditionMapping[$condition] ?? 'near_mint';
    }

    /**
     * Parse tags from string to array.
     */
    protected function parseTags(string $tags): ?array
    {
        if (empty(trim($tags))) {
            return null;
        }

        // Split by comma and clean up
        $tagArray = array_map('trim', explode(',', $tags));
        $tagArray = array_filter($tagArray);

        return empty($tagArray) ? null : array_values($tagArray);
    }

    /**
     * Parse purchase price from string to decimal.
     */
    protected function parsePurchasePrice(string $price): ?float
    {
        if (empty(trim($price))) {
            return null;
        }

        // Remove currency symbols and extract numeric value
        $numericPrice = preg_replace('/[^\d.,]/', '', $price);
        $numericPrice = str_replace(',', '', $numericPrice);

        return is_numeric($numericPrice) ? (float) $numericPrice : null;
    }

    /**
     * Reset import statistics.
     */
    protected function resetStats(): void
    {
        $this->stats = [
            'processed' => 0,
            'cards_created' => 0,
            'cards_found' => 0,
            'instances_created' => 0,
            'errors' => [],
        ];
    }

    /**
     * Add an error to the stats.
     */
    protected function addError(int $row, string $message): void
    {
        $this->stats['errors'][] = [
            'row' => $row,
            'message' => $message,
        ];
    }

    /**
     * Get import statistics.
     */
    public function getStats(): array
    {
        return $this->stats;
    }
} 