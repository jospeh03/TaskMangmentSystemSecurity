<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
class LoginRequest extends FormRequest
{
/**
* Determine if the user is authorized to make this request.
*/
public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }
protected function failedValidation(\Illuminate\contracts\Validation\Validator $validator)
{
    throw new HttpResponseException(response()->json([
        'status'=>'error',
        'message'=>'Please check the input',
        'errors'=>$validator->errors(),
        ]));
}
}