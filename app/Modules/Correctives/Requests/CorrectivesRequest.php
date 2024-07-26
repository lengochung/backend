<?php

namespace App\Modules\Correctives\Requests;

use App\Http\Requests\BaseRequest;
use App\Utils\Lib;

class CorrectivesRequest extends BaseRequest
{

    const SUBJECT_ML = 255;
    const FACILITY_ML = 255;
    const FIND_USER_ML = 255;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function rules()
    {
        return [
            "subject" => sprintf("required|max:%s", CorrectivesRequest::SUBJECT_ML),
            "facility_detail1" => sprintf("max:%s", CorrectivesRequest::FACILITY_ML),
            "facility_detail2" => sprintf("max:%s", CorrectivesRequest::FACILITY_ML),
            "find_user" => sprintf("max:%s", CorrectivesRequest::FIND_USER_ML),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function messages()
    {
        return [
            "subject.required" => __("validation.required", ["attribute" => __("label.subject")]),
            "facility_detail1.max" => __("validation.maxlength", ["attribute" => __("label.facility_detail1"), "number" => CorrectivesRequest::FACILITY_ML]),
            "facility_detail2.max" => __("validation.maxlength", ["attribute" => __("label.facility_detail2"), "number" => CorrectivesRequest::FACILITY_ML]),
            "find_user.max" => __("validation.maxlength", ["attribute" => __("label.find_user"), "number" => CorrectivesRequest::FIND_USER_ML]),
        ];
    }

}
