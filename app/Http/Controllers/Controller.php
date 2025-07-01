<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Magic: The Gathering card collection management API',
    title: 'What Deck API'
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
#[OA\Schema(
    schema: 'PaginatedResponse',
    title: 'Paginated Response',
    description: 'Standard paginated response format',
    properties: [
        new OA\Property(
            property: 'data',
            description: 'Array of resource items',
            type: 'array',
            items: new OA\Items()
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'last_page', type: 'integer', example: 5),
                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                new OA\Property(property: 'total', type: 'integer', example: 67),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'links',
            properties: [
                new OA\Property(property: 'first', type: 'string', format: 'url', example: 'https://api.example.com/cards?page=1'),
                new OA\Property(property: 'last', type: 'string', format: 'url', example: 'https://api.example.com/cards?page=5'),
                new OA\Property(property: 'prev', type: 'string', format: 'url', nullable: true, example: null),
                new OA\Property(property: 'next', type: 'string', format: 'url', nullable: true, example: 'https://api.example.com/cards?page=2'),
            ],
            type: 'object'
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ValidationError',
    title: 'Validation Error Response',
    description: 'Response format for validation errors',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: [
                'title' => ['The title field is required.'],
                'type' => ['The type field is required.']
            ],
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    title: 'Error Response',
    description: 'Standard error response format',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Resource not found'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['card' => ['This card has existing instances and cannot be deleted']],
            nullable: true,
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    title: 'Success Response',
    description: 'Standard success response format',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully'),
    ],
    type: 'object'
)]
abstract class Controller
{
    //
}
