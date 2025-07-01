<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardInstanceRequest extends FormRequest
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
            'condition' => 'sometimes|string|in:mint,near_mint,lightly_played,moderately_played,heavily_played,damaged',
            'foil' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'condition.in' => 'The condition must be one of: mint, near_mint, lightly_played, moderately_played, heavily_played, or damaged.',
            'foil.boolean' => 'The foil field must be true or false.',
        ];
    }
}
