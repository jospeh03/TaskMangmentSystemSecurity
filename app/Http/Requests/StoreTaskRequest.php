<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'title'=>'required|string|min:3|max:25', 
            'description'=>'nullable|min:10|max:1000', 
            'type'=>'required|in:Bug,Feature,Improvment', 
            'priority'=>'required|in:Low,Medium,High', 
            'status'=>'required|in:Open,In_Progress,Completed,Blocked', 
            'due_date'=>'required|date|after_or_equal:date', 
        ];
    }
}
