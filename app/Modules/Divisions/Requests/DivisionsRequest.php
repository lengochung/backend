<?php

namespace App\Modules\Divisions\Requests;

use App\Http\Requests\BaseRequest;

class DivisionsRequest extends BaseRequest {
    const CANDIDATE_ML = 255;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'function_id' => 'required',
            'page_id' => 'required',
            'item_id' => 'required',
            'candidate' => sprintf("required|max:%s", DivisionsRequest::CANDIDATE_ML)
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
            "function_id.required" => __("validation.required_choose", ["attribute" => __("label.function_id")]),
            "page_id.required" => __("validation.required_choose", ["attribute" => __("label.page_id")]),
            "item_id.required" => __("validation.required_choose", ["attribute" => __("label.item_id")]),
            "candidate.max" => __("validation.max", ["number" => DivisionsRequest::CANDIDATE_ML]),
            "candidate.required" => __("validation.required", ["attribute" => __("label.candidate")]),
        ];
    }

}
