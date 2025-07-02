<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // In production, implement proper authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'image_url' => 'nullable|url|max:500',
            'description' => 'nullable|string|max:1000',
            'cost' => 'nullable|string|max:50',
            'type' => 'sometimes|required|string|max:100',
            'subtype' => 'nullable|string|max:100',
            'power' => 'nullable|integer|min:0|max:999',
            'toughness' => 'nullable|integer|min:0|max:999',
            'edition' => 'nullable|string|max:10',
            'collector_number' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The card title is required.',
            'title.max' => 'The card title cannot exceed 255 characters.',
            'type.required' => 'The card type is required.',
            'image_url.url' => 'The image URL must be a valid URL.',
            'power.integer' => 'Power must be a valid integer.',
            'power.min' => 'Power cannot be negative.',
            'toughness.integer' => 'Toughness must be a valid integer.',
            'toughness.min' => 'Toughness cannot be negative.',
        ];
    }
}
