<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can implement authorization logic based on user roles here
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:3|max:25',
            'description' => 'sometimes|min:10|max:1000',
            'type' => 'sometimes|in:Bug,Feature,Improvement',
            'priority' => 'sometimes|in:Low,Medium,High',
            'due_date' => 'sometimes|date|after_or_equal:today',];
    }
}
