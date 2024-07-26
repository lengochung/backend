<?php
//Total number of pages on 1 page
const PAGINATE_LIMIT = 20;
//Total number of media pages on 1 page
const MEDIA_PAGINATE_LIMIT = 30;
//Datetime format: show in web
const DATE_FORMAT = 'Y/m/d';
const DATE_TIME_FORMAT = 'Y/m/d H:i:s';
//Datetime format: save to database
const DATE_FORMAT_DB = 'Y-m-d';
const DATE_TIME_FORMAT_DB = 'Y-m-d H:i:s';
const DEFAULT_LANG = 'ja';
//Storage disk local
const STORAGE_DISK_LOCAL = 'public';
//uploads folder into storage foldeộ
const UPLOAD_DIR = 'uploads';
//uploads temp folder into storage folder
const TEMP_DIR = 'temp';
//Remove old temp files
const CLEANUP_TARGET_DIR = true;
// Temp file age in seconds
const MAX_FILE_AGE = 5 * 3600;
//Allow resize image
const ALLOW_RESIZE_IMAGE = true;
//Reisze image
const RESIZE_IMAGES = [300,768];
const PREFIX_RESIZE_IMAGE = "%sx%s";
//Auth key
const AUTH_API_GUARD_KEY = 'auth-api';
const AUTH_API_GUARD = 'assign.guard:auth-api';
const CRYPTOJS = [
    "ENCRYPT_METHOD" => 'aes-256-cbc',
    "PUBLIC_KEY" => 'f4e3572fa8b8e98a3e2c8f483acc3d138632b15c6ce93ccd5cc776c582af194d',
    "INIT_VECTOR" => 'qfCRQ-70SdldCBUF',
];
//Time allowed for API call request from client(40 seconds)
const EXPIRED_TIME_API_REQUEST = '40000s';
//Check edit status
const CHECK_EDIT_STATUS = [
    "NOT_EXISTS" => 0,
    "OK" => 1,
    "EDITED" => 2,
    "DELETED" => 3
];
//Deleted status
const DELETED_STATUS = [
    'NOT_DELETED' => 0,
    'DELETED' => 1
];
//Order directive
const ORDER_DIRECTION = [
    'asc',
    'desc'
];
//Topic status
const TOPIC_STATUS = [
    "PRECAUTION" => '予防処置',
    "CORRECTIVE" => '是正処置',
    "MALFUNCTION" => '障害報告書',
    "DAILY_REPORT" => '日報トピック',
];
//Notices send/receive type
const NOTICE_SEND_TYPE = [
    "USER_LOGIN" => '発信',
    "NOT_USER_LOGIN" => '受信'
];
const DB_EDIT_FIELDS = [
    'add_datetime',
    'upd_datetime',
    'add_user_id',
    'upd_user_id'
];
const DB_ITEM_NAME_VALUES = [
    // Text value of field items.item_name
    'item_name_status' => 'ステータス'
];

const DB_DIVISION_STATUS = [
    "create" => "起票中", // Init/Create new/Edit
    "synch" => "周知中", // Synched
    "close" => "クローズ" // Closed
];
//corrective status
const CORRECTIVE_STATUS = [
    "OPENING" => '起票中',
    "PLANNING" => '是正処置計画中',
    "IMPLEMENTING" => '是正処置実施中',
    "CLOSED" => 'クローズ',
];
const ITEM_NO_CORRECTIVE_STATUS = 1;
