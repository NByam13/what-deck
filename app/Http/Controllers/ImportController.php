<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Services\MoxfieldImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Import', description: 'Card collection import operations')]
class ImportController extends Controller
{
    protected MoxfieldImportService $moxfieldImportService;

    public function __construct(MoxfieldImportService $moxfieldImportService)
    {
        $this->moxfieldImportService = $moxfieldImportService;
    }

    #[OA\Post(
        path: '/collections/{id}/import/moxfield',
        summary: 'Import Moxfield CSV',
        description: 'Import cards from a Moxfield CSV export into a specific collection',
        tags: ['Import'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Collection ID to import cards into',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['csv_file'],
                    properties: [
                        new OA\Property(
                            property: 'csv_file',
                            description: 'Moxfield CSV export file',
                            type: 'string',
                            format: 'binary'
                        ),
                        new OA\Property(
                            property: 'create_missing_cards',
                            description: 'Whether to create new card records for unknown cards',
                            type: 'boolean',
                            example: true
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Import completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Import completed successfully'),
                        new OA\Property(
                            property: 'stats',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'processed', type: 'integer', example: 150),
                                new OA\Property(property: 'cards_created', type: 'integer', example: 75),
                                new OA\Property(property: 'cards_found', type: 'integer', example: 75),
                                new OA\Property(property: 'instances_created', type: 'integer', example: 150),
                                new OA\Property(
                                    property: 'errors',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'row', type: 'integer', example: 5),
                                            new OA\Property(property: 'message', type: 'string', example: 'Invalid condition value'),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid file or import error',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Collection not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]
    public function moxfield(Request $request, Collection $collection): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
                'create_missing_cards' => 'boolean',
            ]);

            // Get the uploaded file
            $file = $request->file('csv_file');
            
            if (!$file->isValid()) {
                return response()->json([
                    'message' => 'Invalid file upload',
                    'errors' => ['csv_file' => ['The uploaded file is not valid']]
                ], 400);
            }

            // Read the CSV content
            $csvContent = file_get_contents($file->getRealPath());
            
            if ($csvContent === false) {
                return response()->json([
                    'message' => 'Could not read file content',
                    'errors' => ['csv_file' => ['Unable to read the uploaded file']]
                ], 400);
            }

            // Validate CSV format
            if (!$this->validateMoxfieldCsvFormat($csvContent)) {
                return response()->json([
                    'message' => 'Invalid Moxfield CSV format',
                    'errors' => ['csv_file' => ['The file does not appear to be a valid Moxfield export']]
                ], 400);
            }

            // Perform the import
            $stats = $this->moxfieldImportService->import($csvContent, $collection);

            // Determine response status based on errors
            $hasErrors = !empty($stats['errors']);
            $message = $hasErrors 
                ? 'Import completed with some errors'
                : 'Import completed successfully';

            return response()->json([
                'message' => $message,
                'stats' => $stats,
            ], $hasErrors ? 206 : 200); // 206 Partial Content if there were errors

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'errors' => ['import' => [$e->getMessage()]]
            ], 500);
        }
    }

    #[OA\Get(
        path: '/import/formats',
        summary: 'Get supported import formats',
        description: 'Get list of supported import formats and their requirements',
        tags: ['Import'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of supported import formats',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'formats',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', example: 'Moxfield'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Import from Moxfield CSV export'),
                                    new OA\Property(property: 'endpoint', type: 'string', example: '/collections/{id}/import/moxfield'),
                                    new OA\Property(
                                        property: 'required_columns',
                                        type: 'array',
                                        items: new OA\Items(type: 'string'),
                                        example: ['Count', 'Name', 'Edition', 'Condition', 'Language', 'Foil']
                                    ),
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function supportedFormats(): JsonResponse
    {
        return response()->json([
            'formats' => [
                [
                    'name' => 'Moxfield',
                    'description' => 'Import from Moxfield CSV export',
                    'endpoint' => '/collections/{id}/import/moxfield',
                    'required_columns' => [
                        'Count',
                        'Name', 
                        'Edition',
                        'Condition',
                        'Language',
                        'Foil',
                        'Collector Number'
                    ],
                    'optional_columns' => [
                        'Tags',
                        'Purchase Price',
                        'Alter',
                        'Proxy',
                        'Tradelist Count',
                        'Last Modified'
                    ],
                    'file_requirements' => [
                        'format' => 'CSV',
                        'max_size' => '10MB',
                        'encoding' => 'UTF-8'
                    ]
                ]
            ]
        ]);
    }

    /**
     * Validate that the CSV content matches Moxfield format.
     */
    protected function validateMoxfieldCsvFormat(string $csvContent): bool
    {
        $lines = explode("\n", trim($csvContent));
        
        if (empty($lines)) {
            return false;
        }

        // Check if first line contains expected headers
        $header = str_getcsv($lines[0]);
        $requiredColumns = ['Count', 'Name', 'Edition', 'Condition', 'Language', 'Foil'];
        
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $header)) {
                return false;
            }
        }

        return true;
    }
}
