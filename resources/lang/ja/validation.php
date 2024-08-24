<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    'required' => 'Vui lòng nhập :attribute.',
    'required_choose' => 'Vui lòng chọn :attribute.',
    'maxlength' => 'Độ dài của :attribute không vượt quá :number ký tự.',
    'minlength' => 'Độ dài của :attribute phải ít nhất :number ký tự.',
    'max' => 'Vui lòng nhập giá trị không vượt quá :number.',
    'min' => 'Vui lòng nhập giá trị không thấp hơn :number.',
    'date' => 'Vui lòng nhập ngày hợp lệ {{field}} (YYYY/MM/DD).',
    'number' => ':attribute chỉ có thể chứa các số.',
    'equalto' => ':attribute1 và ":attribute2" không khớp.',
    'range' => 'Vui lòng nhập giá trị từ :number đến :number1.',
    'email' => ':attribute phải có định dạng email hợp lệ.',
    'alphanumeric' => 'Vui lòng nhập bằng chữ cái và số.',
    'exist' => ':attribute đã tồn tại. Vui lòng chọn :attribute khác.',
    'invalid_alphanumeric' => ':attribute phải là chữ cái và số.',
    'password' => 'Mật khẩu không hợp lệ.',
    'username' => ':attribute phải là chữ cái và số.',
    'required_one' => 'Vui lòng nhập ít nhất một :attribute.',
    'katakana' => ':attribute phải được nhập bằng Katakana.',
    'phone' => 'Vui lòng nhập số điện thoại.',
    'fax' => 'Vui lòng nhập số fax.',
    'new_password_not_match' => 'Giá trị xác nhận mật khẩu mới không khớp. Vui lòng nhập lại.',
    'wrong_password' => 'Mật khẩu không chính xác.',
    'regex' => 'Định dạng :attribute không hợp lệ.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention 'attribute.rule' to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as 'E-Mail Address' instead
    | of 'email'. This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
