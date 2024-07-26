<?php

namespace App\Modules\Models;

use Exception;
use App\Utils\Lib;
use App\Traits\HasCompositePrimaryKey;
use Illuminate\Support\Facades\DB;
use App\Modules\Common\Models\BaseModel;

class CorrectivesDetailModel extends BaseModel {
    use HasCompositePrimaryKey;
    protected $table = 'correctives-detail';
    protected $primaryKey = ['corrective_no', 'edition_no'];
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'corrective_no',
        'edition_no',
        'plan_detail',
        'plan_attached_file',
        'plan_user_id',
        'plan_deadline',
        'plan_request_user_id',
        'plan_request_date',
        'plan_approval1_user_id',
        'plan_approval1_datetime',
        'plan_approval1_comment',
        'plan_approval2_user_id',
        'plan_approval2_datetime',
        'plan_approval2_comment',
        'result_detail',
        'is_result_judgment',
        'result_attached_file',
        'result_request_user_id',
        'result_request_date',
        'result_approval1_user_id',
        'result_approval1_datetime',
        'result_approval1_comment',
        'result_approval2_user_id',
        'result_approval2_datetime',
        'result_approval2_comment',
        'add_datetime',
        'add_user_id',
        'upd_datetime',
        'upd_user_id',
        'is_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'plan_deadline' => 'date:'.DATE_FORMAT,
        'plan_request_date' => 'date:'.DATE_TIME_FORMAT,
        'plan_approval1_datetime' => 'date:'.DATE_TIME_FORMAT,
        'plan_approval2_datetime' => 'date:'.DATE_TIME_FORMAT,
        'result_request_date' => 'date:'.DATE_TIME_FORMAT,
        'result_approval1_datetime' => 'date:'.DATE_TIME_FORMAT,
        'result_approval2_datetime' => 'date:'.DATE_TIME_FORMAT,
    ];


}
