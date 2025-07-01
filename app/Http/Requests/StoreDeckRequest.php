<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeckRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'format' => 'nullable|string|max:100|in:Standard,Modern,Legacy,Vintage,Commander,Pioneer,Historic,Pauper,Limited',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'A user is required for the deck.',
            'user_id.exists' => 'The specified user does not exist.',
            'name.required' => 'The deck name is required.',
            'name.max' => 'The deck name cannot exceed 255 characters.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'format.in' => 'The format must be one of: Standard, Modern, Legacy, Vintage, Commander, Pioneer, Historic, Pauper, or Limited.',
        ];
    }
}
