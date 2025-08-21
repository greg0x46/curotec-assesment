<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'owner_id'       => ['exists:users,id'],
            'assigned_to_id' => ['nullable', 'exists:users,id'],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'priority'       => ['required', Rule::enum(TaskPriority::class)],
            'status'         => ['required', Rule::enum(TaskStatus::class)],
            'due_date'       => ['nullable', 'date'],
            'categories'     => ['nullable', 'array'],
            'categories.*'   => ['integer', 'exists:categories,id'],
        ];
    }
}
