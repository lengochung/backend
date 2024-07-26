<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class BaseRequest extends Request
{
    //Input maxlength
    const USER_NAME_ML = 60;
    const EMAIL_ML = 255;
    const PASSWORD_ML = 60;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
