<?php

namespace App\Http\Requests;

use App\Enums\PostVisibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated user can post — the auth:sanctum middleware
        // on the route already guarantees that much.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required_without:image', 'nullable', 'string', 'max:5000'],
            'image' => ['required_without:body', 'nullable', 'image', 'max:3072'], // 3MB
            'visibility' => ['required', Rule::enum(PostVisibility::class)],
        ];
    }
}
