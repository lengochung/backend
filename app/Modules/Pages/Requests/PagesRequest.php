<?php

namespace App\Modules\Pages\Requests;

use App\Http\Requests\BaseRequest;

class PagesRequest extends BaseRequest {
    const GROUP_NAME_ML = 255;
    const DESCRIPTION_ML = 255;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function groupRules() {
        return [

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

        ];
    }

}
