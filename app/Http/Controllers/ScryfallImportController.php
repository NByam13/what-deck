<?php

namespace App\Http\Controllers;

use App\Services\ScryfallImportService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Scryfall Import', description: 'Import Magic: The Gathering cards from Scryfall database')]
class ScryfallImportController extends Controller
{
    protected ScryfallImportService $importService;

    public function __construct(ScryfallImportService $importService)
    {
        $this->importService = $importService;
    }

    #[OA\Post(
        path: '/api/scryfall/import',
        summary: 'Import cards from Scryfall bulk data',
        description: 'Import Magic: The Gathering cards from Scryfall bulk data. Can import from URL or file path.',
        tags: ['Scryfall Import'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['source'],
                properties: [
                    'source' => new OA\Property(
                        property: 'source',
                        type: 'string',
                        description: 'URL to Scryfall bulk data or local file path',
                        example: 'https://data.scryfall.io/default-cards/default-cards-20240101-210000.json'
                    ),
                    'options' => new OA\Property(
                        property: 'options',
                        type: 'object',
                        properties: [
                            'skip_layouts' => new OA\Property(
                                property: 'skip_layouts',
                                type: 'array',
                                items: new OA\Items(type: 'string'),
                                description: 'Card layouts to skip during import',
                                example: ['token', 'emblem', 'planar']
                            ),
                            'batch_size' => new OA\Property(
                                property: 'batch_size',
                                type: 'integer',
                                description: 'Number of cards to process in each batch',
                                example: 1000
                            )
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Import completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        'message' => new OA\Property(property: 'message', type: 'string', example: 'Import completed successfully'),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'processed' => new OA\Property(property: 'processed', type: 'integer', example: 50000),
                                'created' => new OA\Property(property: 'created', type: 'integer', example: 45000),
                                'updated' => new OA\Property(property: 'updated', type: 'integer', example: 4500),
                                'skipped' => new OA\Property(property: 'skipped', type: 'integer', example: 500),
                                'errors' => new OA\Property(property: 'errors', type: 'integer', example: 0),
                                'success_rate' => new OA\Property(property: 'success_rate', type: 'number', example: 99.2)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request data',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
            new OA\Response(
                response: 500,
                description: 'Import failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source' => 'required|string',
            'options' => 'array',
            'options.skip_layouts' => 'array',
            'options.skip_layouts.*' => 'string',
            'options.batch_size' => 'integer|min:100|max:5000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $source = $request->input('source');
            $options = $request->input('options', []);

            // Set batch size if provided
            if (isset($options['batch_size'])) {
                $this->importService->setBatchSize($options['batch_size']);
            }

            Log::info('Starting Scryfall import via API', [
                'source' => $source,
                'options' => $options
            ]);

            $result = $this->importService->import($source, $options);

            return response()->json([
                'message' => 'Import completed successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Scryfall import failed via API', [
                'error' => $e->getMessage(),
                'source' => $request->input('source')
            ]);

            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/scryfall/bulk-data',
        summary: 'Get available Scryfall bulk data downloads',
        description: 'Retrieve information about available bulk data downloads from Scryfall',
        tags: ['Scryfall Import'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bulk data information retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        'message' => new OA\Property(property: 'message', type: 'string', example: 'Bulk data retrieved successfully'),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    'type' => new OA\Property(property: 'type', type: 'string', example: 'default_cards'),
                                    'name' => new OA\Property(property: 'name', type: 'string', example: 'Default Cards'),
                                    'description' => new OA\Property(property: 'description', type: 'string', example: 'All cards in English (default)'),
                                    'download_uri' => new OA\Property(property: 'download_uri', type: 'string', example: 'https://data.scryfall.io/default-cards/default-cards-20240101.json'),
                                    'size' => new OA\Property(property: 'size', type: 'integer', example: 503316480),
                                    'content_type' => new OA\Property(property: 'content_type', type: 'string', example: 'application/json'),
                                    'updated_at' => new OA\Property(property: 'updated_at', type: 'string', example: '2024-01-01T21:00:00Z')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to retrieve bulk data information',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function getBulkData(): JsonResponse
    {
        try {
            // Use the improved service method with proper headers and rate limiting
            $bulkData = $this->importService->getBulkDataInfo();

            return response()->json([
                'message' => 'Bulk data retrieved successfully',
                'data' => $bulkData
            ]);

        } catch (Exception $e) {
            Log::error('Failed to retrieve Scryfall bulk data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve bulk data information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/scryfall/import/auto',
        summary: 'Auto-import latest Scryfall data',
        description: 'Automatically download and import the latest default cards from Scryfall',
        tags: ['Scryfall Import'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    'data_type' => new OA\Property(
                        property: 'data_type',
                        type: 'string',
                        description: 'Type of bulk data to import',
                        example: 'default_cards',
                        enum: ['default_cards', 'oracle_cards', 'unique_artwork', 'all_cards']
                    ),
                    'options' => new OA\Property(
                        property: 'options',
                        type: 'object',
                        properties: [
                            'skip_layouts' => new OA\Property(
                                property: 'skip_layouts',
                                type: 'array',
                                items: new OA\Items(type: 'string'),
                                description: 'Card layouts to skip during import'
                            ),
                            'batch_size' => new OA\Property(
                                property: 'batch_size',
                                type: 'integer',
                                description: 'Number of cards to process in each batch'
                            )
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Auto-import completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        'message' => new OA\Property(property: 'message', type: 'string', example: 'Auto-import completed successfully'),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'data_type' => new OA\Property(property: 'data_type', type: 'string', example: 'default_cards'),
                                'download_url' => new OA\Property(property: 'download_url', type: 'string', example: 'https://data.scryfall.io/default-cards/default-cards-20240101.json'),
                                'processed' => new OA\Property(property: 'processed', type: 'integer', example: 50000),
                                'created' => new OA\Property(property: 'created', type: 'integer', example: 45000),
                                'updated' => new OA\Property(property: 'updated', type: 'integer', example: 4500),
                                'skipped' => new OA\Property(property: 'skipped', type: 'integer', example: 500),
                                'errors' => new OA\Property(property: 'errors', type: 'integer', example: 0),
                                'success_rate' => new OA\Property(property: 'success_rate', type: 'number', example: 99.2)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request data',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
            new OA\Response(
                response: 500,
                description: 'Auto-import failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function autoImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data_type' => 'string|in:default_cards,oracle_cards,unique_artwork,all_cards',
            'options' => 'array',
            'options.skip_layouts' => 'array',
            'options.skip_layouts.*' => 'string',
            'options.batch_size' => 'integer|min:100|max:5000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $dataType = $request->input('data_type', 'default_cards');
            $options = $request->input('options', []);

            // Set batch size if provided
            if (isset($options['batch_size'])) {
                $this->importService->setBatchSize($options['batch_size']);
            }

            Log::info('Starting Scryfall auto-import via API', [
                'data_type' => $dataType,
                'options' => $options
            ]);

            // Get latest bulk data URL using improved service method
            $downloadUrl = $this->importService->getBulkDataUrl($dataType);
            
            Log::info('Retrieved bulk data URL', [
                'data_type' => $dataType,
                'url' => $downloadUrl
            ]);

            // Import from the URL
            $result = $this->importService->import($downloadUrl, $options);

            // Add download info to result
            $result['data_type'] = $dataType;
            $result['download_url'] = $downloadUrl;

            return response()->json([
                'message' => 'Auto-import completed successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Scryfall auto-import failed via API', [
                'error' => $e->getMessage(),
                'data_type' => $request->input('data_type', 'default_cards')
            ]);

            return response()->json([
                'message' => 'Auto-import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/scryfall/stats',
        summary: 'Get Scryfall import statistics',
        description: 'Get statistics about imported Scryfall cards in the database',
        tags: ['Scryfall Import'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        'message' => new OA\Property(property: 'message', type: 'string', example: 'Statistics retrieved successfully'),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'total_cards' => new OA\Property(property: 'total_cards', type: 'integer', example: 50000),
                                'scryfall_cards' => new OA\Property(property: 'scryfall_cards', type: 'integer', example: 49500),
                                'manual_cards' => new OA\Property(property: 'manual_cards', type: 'integer', example: 500),
                                'sets_count' => new OA\Property(property: 'sets_count', type: 'integer', example: 850),
                                'latest_set' => new OA\Property(property: 'latest_set', type: 'string', example: 'Bloomburrow'),
                                'rarity_breakdown' => new OA\Property(
                                    property: 'rarity_breakdown',
                                    type: 'object',
                                    properties: [
                                        'common' => new OA\Property(property: 'common', type: 'integer', example: 25000),
                                        'uncommon' => new OA\Property(property: 'uncommon', type: 'integer', example: 15000),
                                        'rare' => new OA\Property(property: 'rare', type: 'integer', example: 8000),
                                        'mythic' => new OA\Property(property: 'mythic', type: 'integer', example: 2000)
                                    ]
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function getStats(): JsonResponse
    {
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

            return response()->json([
                'message' => 'Statistics retrieved successfully',
                'data' => [
                    'total_cards' => $totalCards,
                    'scryfall_cards' => $scryfallCards,
                    'manual_cards' => $manualCards,
                    'sets_count' => $setsCount,
                    'latest_set' => $latestSet,
                    'rarity_breakdown' => $rarityBreakdown
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to retrieve Scryfall statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
