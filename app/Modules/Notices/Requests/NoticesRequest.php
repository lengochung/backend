<?php

namespace App\Modules\Notices\Requests;

use App\Http\Requests\BaseRequest;

class NoticesRequest extends BaseRequest {
    const SUBJECT_ML = 255;
    const FACILITY_ML = 255;
    const DETAIL_ML = 500;

    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function noticeRules() {
        return [
            "office_id" => "required",
            "subject" => sprintf("required|max:%s", NoticesRequest::SUBJECT_ML),
            "event_date" => "required",
            "building_id" => "required",
            "fuel_type" => "required",
            "facility_detail1" => sprintf("max:%s", NoticesRequest::FACILITY_ML),
            "facility_detail2" => sprintf("max:%s", NoticesRequest::FACILITY_ML),
            "user_id" => "required",
            "status_id" => "required",
            "detail" => sprintf("required|max:%s", NoticesRequest::DETAIL_ML),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function noticeMessages() {
        return [
            "office_id.required" => __("validation.required", ["attribute" => __("label.office_id")]),
            "subject.required" => __("validation.required", ["attribute" => __("label.subject")]),
            "subject.max" => __("validation.max", ["number" => NoticesRequest::SUBJECT_ML]),
            "event_date.required" => __("validation.required", ["attribute" => __("label.event_date")]),
            "building_id.required" => __("validation.required", ["attribute" => __("label.building_id")]),
            "fuel_type.required" => __("validation.required", ["attribute" => __("label.fuel_type")]),
            "facility_detail1.max" => __("validation.max", ["number" => NoticesRequest::FACILITY_ML]),
            "facility_detail2.max" => __("validation.max", ["number" => NoticesRequest::FACILITY_ML]),
            "user_id.required" => __("validation.required", ["attribute" => __("label.user_id")]),
            "status_id.required" => __("validation.required", ["attribute" => __("label.notice_status_id")]),
            "detail.required" => __("validation.required", ["attribute" => __("label.detail")]),
            "detail.max" => __("validation.max", ["number" => NoticesRequest::DETAIL_ML]),
        ];
    }

}
