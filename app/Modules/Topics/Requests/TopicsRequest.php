<?php

namespace App\Modules\Topics\Requests;

use App\Http\Requests\BaseRequest;

class TopicsRequest extends BaseRequest {
    const SUBJECT_ML = 255;
    const FACILITY_ML = 255;
    const DETAIL_ML = 500;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function topicRules() {
        return [
            "office_id" => "required",
            "notice_no" => "required",
            "subject" => sprintf("required|max:%s", TopicsRequest::SUBJECT_ML),
            "event_date" => "required",
            "building_id" => "required",
            "fuel_type" => "required",
            "facility_id" => "required",
            "facility_detail1" => sprintf("required|max:%s", TopicsRequest::FACILITY_ML),
            "facility_detail2" => sprintf("required|max:%s", TopicsRequest::FACILITY_ML),
            "previous_user_id" => "required",
            "today_user_id" => "required",
            "detail" => sprintf("required|max:%s", TopicsRequest::DETAIL_ML),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function topicMessages() {
        return [
            "office_id.required" => __("validation.required", ["attribute" => __("label.office_id")]),
            "notice_no.required" => __("validation.required", ["attribute" => __("label.notice_no")]),
            "subject.required" => __("validation.required", ["attribute" => __("label.subject")]),
            "subject.max" => __("validation.max", ["number" => TopicsRequest::SUBJECT_ML]),
            "event_date.required" => __("validation.required", ["attribute" => __("label.event_date")]),
            "building_id.required" => __("validation.required", ["attribute" => __("label.building_id")]),
            "fuel_type.required" => __("validation.required", ["attribute" => __("label.fuel_type")]),
            "facility_id.required" => __("validation.required", ["attribute" => __("label.facility_id")]),
            "facility_detail1.required" => __("validation.required", ["attribute" => __("label.facility_detail1")]),
            "facility_detail1.max" => __("validation.max", ["number" => TopicsRequest::FACILITY_ML]),
            "facility_detail2.required" => __("validation.required", ["attribute" => __("label.facility_detail2")]),
            "facility_detail2.max" => __("validation.max", ["number" => TopicsRequest::FACILITY_ML]),
            "previous_user_id.required" => __("validation.required", ["attribute" => __("label.previous_user_id")]),
            "today_user_id.required" => __("validation.required", ["attribute" => __("label.today_user_id")]),
            "detail.required" => __("validation.required", ["attribute" => __("label.detail")]),
            "detail.max" => __("validation.max", ["number" => TopicsRequest::DETAIL_ML]),
        ];
    }

}
