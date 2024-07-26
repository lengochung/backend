<?php

namespace App\Modules\Users\Requests;

use App\Http\Requests\BaseRequest;
use App\Utils\Lib;

class UserRequest extends BaseRequest
{
    const EMPLOYEE_NO_ML = 30;
    const AFFILIATION_ML = 96;
    const POSITION_ML = 96;
    const PASS_HASH_ML = 32;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function userRules()
    {
        return [
            "user_last_name" => sprintf("required|max:%s", UserRequest::USER_NAME_ML),
            "user_first_name" => sprintf("required|max:%s", UserRequest::USER_NAME_ML),
            "employee_no" => sprintf("max:%s", UserRequest::EMPLOYEE_NO_ML),
            "affiliation" => sprintf("max:%s", UserRequest::AFFILIATION_ML),
            "position" => sprintf("max:%s", UserRequest::POSITION_ML),
            "mail" => sprintf("required|email|max:%s", UserRequest::EMAIL_ML),
            "role_list" => "required",
            "role_list.*.office_id" => "required|numeric",
            "role_list.*.role_id" => "required|numeric",
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function userMessages()
    {
        return [
            "user_last_name.required" => __("validation.required", ["attribute" => __("label.last_name")]),
            "user_last_name.max" => __("validation.max", ["number" => UserRequest::USER_NAME_ML]),
            "user_first_name.required" => __("validation.required", ["attribute" => __("label.first_name")]),
            "user_first_name.max" => __("validation.max", ["number" => UserRequest::USER_NAME_ML]),
            "employee_no.max" => __("validation.max", ["number" => UserRequest::EMPLOYEE_NO_ML]),
            "affiliation.max" => __("validation.max", ["number" => UserRequest::AFFILIATION_ML]),
            "position.max" => __("validation.max", ["number" => UserRequest::POSITION_ML]),
            "mail.required" => __("validation.required", ["attribute" => __("label.mail")]),
            "mail.max" => __("validation.max", ["number" => UserRequest::EMAIL_ML]),
            "mail.email" => __("validation.email", ["attribute" => __("label.mail")]),
            "password.required" => __("validation.required", ["attribute" => __("label.password")]),
            "password.max" => __("validation.max", ["number" => UserRequest::PASS_HASH_ML]),
            "new_password.max" => __("validation.max", ["number" => UserRequest::PASSWORD_ML]),
            "new_password.regex" => __("validation.password"),
            "role_list.required" => __("validation.required", ["attribute" => __("label.role_setting")]),
            "role_list.*.office_id.required" => __("validation.required", ["attribute" => __("label.office")]),
            "role_list.*.office_id.numeric" => __("validation.number", ["attribute" => __("label.office")]),
            "role_list.*.role_id.required" => __("validation.required", ["attribute" => __("label.role")]),
            "role_list.*.role_id.numeric" => __("validation.number", ["attribute" => __("label.role")]),
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateProfileRules($isUpdatePass = false)
    {
        return [
            "user_last_name" => sprintf("required|max:%s", UserRequest::USER_NAME_ML),
            "user_first_name" => sprintf("required|max:%s", UserRequest::USER_NAME_ML),
            "employee_no" => sprintf("max:%s", UserRequest::EMPLOYEE_NO_ML),
            "affiliation" => sprintf("max:%s", UserRequest::AFFILIATION_ML),
            "position" => sprintf("max:%s", UserRequest::POSITION_ML),
            "mail" => sprintf("required|email|max:%s", UserRequest::EMAIL_ML),
            "password" => $isUpdatePass ? sprintf("required|max:%s", UserRequest::PASS_HASH_ML) : "",
            "new_password" => $isUpdatePass ? sprintf("regex:%s|max:%s", Lib::getPasswordRegex(), UserRequest::PASSWORD_ML) : "",
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function updateProfileMessages()
    {
        return [
            "user_last_name.required" => __("validation.required", ["attribute" => __("label.last_name")]),
            "user_last_name.max" => __("validation.max", ["number" => UserRequest::USER_NAME_ML]),
            "user_first_name.required" => __("validation.required", ["attribute" => __("label.first_name")]),
            "user_first_name.max" => __("validation.max", ["number" => UserRequest::USER_NAME_ML]),
            "employee_no.max" => __("validation.max", ["number" => UserRequest::EMPLOYEE_NO_ML]),
            "affiliation.max" => __("validation.max", ["number" => UserRequest::AFFILIATION_ML]),
            "position.max" => __("validation.max", ["number" => UserRequest::POSITION_ML]),
            "mail.required" => __("validation.required", ["attribute" => __("label.mail")]),
            "mail.max" => __("validation.max", ["number" => UserRequest::EMAIL_ML]),
            "mail.email" => __("validation.email", ["attribute" => __("label.mail")]),
            "password.required" => __("validation.required", ["attribute" => __("label.password")]),
            "password.max" => __("validation.max", ["number" => UserRequest::PASS_HASH_ML]),
            "new_password.max" => __("validation.max", ["number" => UserRequest::PASSWORD_ML]),
            "new_password.regex" => __("validation.password"),
        ];
    }
}
