<?php

namespace App\Modules\Groups\Requests;

use App\Http\Requests\BaseRequest;
use App\Utils\Lib;

class GroupRequest extends BaseRequest
{
    const GROUP_NAME_ML = 255;
    const DESCRIPTION_ML = 255;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function groupRules()
    {
        return [
            "group_name" => sprintf("required|max:%s", GroupRequest::GROUP_NAME_ML),
            "description" => sprintf("required|max:%s", GroupRequest::DESCRIPTION_ML),
            "member_id_list" => "required",
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function groupMessages()
    {
        return [
            "group_name.required" => __("validation.required", ["attribute" => __("label.group_name")]),
            "group_name.max" => __("validation.max", ["number" => GroupRequest::GROUP_NAME_ML]),
            "description.required" => __("validation.required", ["attribute" => __("label.description")]),
            "description.max" => __("validation.max", ["number" => GroupRequest::DESCRIPTION_ML]),
            "member_id_list.required" => __("validation.required", ["attribute" => __("label.member")]),
        ];
    }

}
