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
            'user_name' => 'required',
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
            "user_name.required" => __("validation.required", ["attribute" => __("label.user_name")]),
            "password.required" => __("validation.required", ["attribute" => __("label.password")]),
        ];
    }
}
