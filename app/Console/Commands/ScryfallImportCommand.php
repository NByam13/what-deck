<?php

namespace App\Console\Commands;

use App\Services\ScryfallImportService;
use Exception;
use Illuminate\Console\Command;

class ScryfallImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scryfall:import 
                            {source? : Source URL or file path (optional - will auto-fetch latest if not provided)}
                            {--type=default_cards : Type of bulk data to import (default_cards, oracle_cards, unique_artwork, all_cards)}
                            {--batch-size=1000 : Number of cards to process in each batch}
                            {--skip-layouts=* : Card layouts to skip (e.g., token, emblem)}
                            {--stats : Show import statistics only}
                            {--list-bulk : List available bulk data downloads}';

    /**
     * The console command description.
     */
    protected $description = 'Import Magic: The Gathering cards from Scryfall bulk data';

    protected ScryfallImportService $importService;

    public function __construct(ScryfallImportService $importService)
    {
        parent::__construct();
        $this->importService = $importService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Handle --stats option
            if ($this->option('stats')) {
                return $this->showStats();
            }

            // Handle --list-bulk option
            if ($this->option('list-bulk')) {
                return $this->listBulkData();
            }

            // Perform import
            return $this->performImport();

        } catch (Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Perform the actual import
     */
    protected function performImport(): int
    {
        $source = $this->argument('source');
        $type = $this->option('type');
        $batchSize = (int) $this->option('batch-size');
        $skipLayouts = $this->option('skip-layouts');

        // Validate batch size
        if ($batchSize < 100 || $batchSize > 5000) {
            $this->error('Batch size must be between 100 and 5000');
            return 1;
        }

        // Set batch size
        $this->importService->setBatchSize($batchSize);

        // Determine source
        if (!$source) {
            $this->info("No source provided, fetching latest {$type} data...");
            try {
                $source = $this->importService->getBulkDataUrl($type);
                $this->info("Using source: {$source}");
            } catch (Exception $e) {
                $this->error("Failed to get bulk data URL: " . $e->getMessage());
                return 1;
            }
        }

        // Prepare options
        $options = [];
        if (!empty($skipLayouts)) {
            $options['skip_layouts'] = $skipLayouts;
            $this->info('Skipping layouts: ' . implode(', ', $skipLayouts));
        }

        $this->info('Starting Scryfall import...');
        $this->info('Source: ' . $source);
        $this->info('Batch size: ' . $batchSize);

        // Create progress bar placeholder
        $this->output->writeln('');

        try {
            $result = $this->importService->import($source, $options);

            // Display results
            $this->displayResults($result);

            return 0;

        } catch (Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show import statistics
     */
    protected function showStats(): int
    {
        $this->info('Retrieving Scryfall import statistics...');

        try {
            $totalCards = \App\Models\Card::count();
            $scryfallCards = \App\Models\Card::whereNotNull('scryfall_id')->count();
            $manualCards = $totalCards - $scryfallCards;
            $setsCount = \App\Models\Card::whereNotNull('set')->distinct('set')->count();
            
            $latestSet = \App\Models\Card::whereNotNull('released_at')
                ->orderBy('released_at', 'desc')
                ->value('set_name');

            $rarityBreakdown = \App\Models\Card::whereNotNull('rarity')
                ->selectRaw('rarity, COUNT(*) as count')
                ->groupBy('rarity')
                ->pluck('count', 'rarity')
                ->toArray();

            // Display statistics
            $this->info('');
            $this->info('=== Scryfall Import Statistics ===');
            $this->info('Total cards: ' . number_format($totalCards));
            $this->info('Scryfall cards: ' . number_format($scryfallCards));
            $this->info('Manual cards: ' . number_format($manualCards));
            $this->info('Unique sets: ' . number_format($setsCount));
            $this->info('Latest set: ' . ($latestSet ?: 'N/A'));
            
            if (!empty($rarityBreakdown)) {
                $this->info('');
                $this->info('Rarity Breakdown:');
                foreach ($rarityBreakdown as $rarity => $count) {
                    $this->info('  ' . ucfirst($rarity) . ': ' . number_format($count));
                }
            }

            return 0;

        } catch (Exception $e) {
            $this->error('Failed to retrieve statistics: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * List available bulk data downloads
     */
    protected function listBulkData(): int
    {
        $this->info('Retrieving available bulk data downloads...');

        try {
            // Use the improved service method with proper headers and rate limiting
            $bulkDataList = $this->importService->getBulkDataInfo();

            $this->info('');
            $this->info('=== Available Scryfall Bulk Data ===');

            $headers = ['Type', 'Name', 'Size (MB)', 'Updated'];
            $rows = [];

            foreach ($bulkDataList as $bulkData) {
                $sizeMB = round($bulkData['size'] / 1024 / 1024, 1);
                $updatedAt = date('Y-m-d H:i', strtotime($bulkData['updated_at']));
                
                $rows[] = [
                    $bulkData['type'],
                    $bulkData['name'],
                    $sizeMB,
                    $updatedAt
                ];
            }

            $this->table($headers, $rows);

            $this->info('');
            $this->info('To import a specific type, use: php artisan scryfall:import --type=TYPE');

            return 0;

        } catch (Exception $e) {
            $this->error('Failed to retrieve bulk data: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Display import results
     */
    protected function displayResults(array $result): void
    {
        $this->info('');
        $this->info('=== Import Results ===');
        $this->info('Processed: ' . number_format($result['processed']));
        $this->info('Created: ' . number_format($result['created']));
        $this->info('Updated: ' . number_format($result['updated']));
        $this->info('Skipped: ' . number_format($result['skipped']));
        $this->info('Errors: ' . number_format($result['errors']));
        $this->info('Success rate: ' . $result['success_rate'] . '%');

        if ($result['errors'] > 0 && !empty($result['error_log'])) {
            $this->warn('');
            $this->warn('Errors encountered:');
            foreach (array_slice($result['error_log'], 0, 10) as $error) {
                $this->warn('  ' . ($error['card_name'] ?? 'Unknown') . ': ' . $error['error']);
            }
            
            if (count($result['error_log']) > 10) {
                $this->warn('  ... and ' . (count($result['error_log']) - 10) . ' more errors');
            }
        }

        if ($result['processed'] > 0) {
            $this->info('');
            $this->info('Import completed successfully!');
        }
    }
}
