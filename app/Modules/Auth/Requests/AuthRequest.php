<?php

namespace App\Modules\Auth\Requests;
use Illuminate\Http\Request;

class AuthRequest extends Request
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required',
            'password' => 'required'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            "email.required" => __("validation.required", ["attribute" => __("label.email")]),
            "password.required" => __("validation.required", ["attribute" => __("label.password")]),
        ];
    }
}
