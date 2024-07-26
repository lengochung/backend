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
    'required' => ':attributeを入力してください。',
    'required_choose' => ':attributeと入力してください。',
    'maxlength' => ':attributeの長さは:number文字以上超えないです。',
    'minlength' => ':attributeの長さは:number文字以上にしてください。',
    'max' => ':number以下の値を入力してください。',
    'min' => ':number以上の値を入力してください。',
    'date' => 'Please enter {{filed}} a correct date(YYYY/MM/DD)',
    'number' => ':attribute中に数字しか含まないです。',
    'equalto' => ':attribute1と「:attribute2」が一致していません。',
    'range' => ':numberから:number1の間で入力してください。',
    'email' => ':attributeメールは正しい形式で入力してください。',
    'alphanumeric' => '半角英数字で入力してください。',
    'exist' => ':attributeが既存してありました。違う:attributeにしてください。',
    'invalid_alphanumeric' => ':attribute半角英数字で入力してください。',
    'password' => '無効なパスワード。',
    'username' => ':attribute半角英数字で入力してください。.',
    'required_one' => '少なくとも1つの:attributeを入力してください。',
    'katakana' => ':attributeカタカナで入力してください。',
    'phone' => '電話番号を入力してください。',
    'fax' => 'FAX番号を入力してください。',
    'new_password_not_match' => '新しいパスワード確認の値が一致しません。入力し直してください。',
    'wrong_password' => 'パスワードが間違っています。',
    'regex' => ':attribute形式は無効です。',

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
