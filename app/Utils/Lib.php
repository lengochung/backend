<?php

namespace App\Utils;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
class Lib
{

    /**
    * Returns a JSON result with the specified status, message, data, and total row count.
    *
    * @param bool $status The status of the result (true for success, false for failure).
    * @param mixed $msg The message associated with the result. Default is an empty string.
    * @param mixed $data The data to be returned. Default is null.
    * @param int $totalRow The total number of rows associated with the data. Default is 0.
    * @return \Illuminate\Http\JsonResponse The JSON response containing the result.
    */
    public static function returnJsonResult($status, $msg = "", $data = null, $totalRow = 0)
    {
        $result["status"] = $status;
        $result["msg"] = $msg;
        $result["data"] = $data;
        $result["total_row"] = $totalRow;
        if ($status) {
            $url = request()->url();
            Log::debug(__METHOD__ . " [Response] = " . json_encode($result));
            Log::debug(__METHOD__ . " ===================== [END Request - API : $url] =====================");
        }
        return response()->json($result);
    }

    /**
    * Checks if a variable is blank.
    *
    * A variable is considered blank if it is not set or if it is a string and its trimmed value is empty.
    *
    * @param mixed $var The variable to check.
    * @return bool True if the variable is blank; otherwise, false.
    */
    public static function isBlank($var)
    {
        return (!isset($var) || (is_string($var) && trim($var) === ''));
    }

    /**
    * Gets the current datetime in MySQL format.
    *
    * This function retrieves the current datetime and format it as 'Y-m-d H:i:s',
    * which is the standard MySQL datetime format.
    *
    * @return string The current datetime in MySQL format ('Y-m-d H:i:s').
    */
    public static function toMySqlNow()
    {
        try {
            $timeNow = new \DateTime();
            $result = $timeNow->format('Y-m-d H:i:s');
            return $result;
        } catch (\Exception $ex) {
            return "";
        }
    }

    /**
    * Retrieves the file extension from the given file name.
    *
    * This function extracts the file extension from the provided file name using the `pathinfo` function.
    *
    * @param string $fileName The file name from which to retrieve the extension.
    * @return string The file extension.
    */
    public static function getFileExtension($fileName)
    {
        return pathinfo($fileName, PATHINFO_EXTENSION);
    }

    /**
    * Checks if a string contains multibyte characters.
    *
    * This function checks if the given string contains multibyte characters by comparing the
    * length of the string when measured in multibyte characters using mb_strlen() against
    * the length of the string when measured in single-byte characters using strlen().
    *
    * @param string $str The string to check for multibyte characters.
    * @return bool True if the string contains multibyte characters; otherwise, false.
    */
    public static function isMultibyte($str)
    {
        if (Lib::isBlank($str)) {
            return false;
        }
        return mb_strlen($str, 'utf-8') < strlen($str);
    }

    /**
    * Sql escape like
    *
    * @param $string
    * @return mixed
    */
    public static function escapeLike($string)
    {
        $search = array('%', '_');
        $replace   = array('\%', '\_');
        return str_replace($search, $replace, $string);
    }

    /**
    * Gets the current date in the specified format.
    *
    * @param string $format The format in which to return the date. Default is DATE_FORMAT.
    * @return string The current date formatted according to the specified format.
    */
    public static function getCurrentDate($format = DATE_FORMAT_DB)
    {
        try {
            $timeNow = new \DateTime();
            $result = $timeNow->format($format);
            return $result;
        } catch (\Exception $ex) {
            return "";
        }
    }
    /**
    * Converts a date from one format to another.
    *
    * @param string $strDate The date string to convert.
    * @param string $formatIn The format of the input date string.
    * @param string $formatOut The format to which the date string should be converted.
    * @return string|null The converted date string or null if the input date string is blank or conversion fails.
    */
    public static function convertDateFormat($strDate, $formatIn, $formatOut)
    {
        if (Lib::isBlank($strDate)) {
            return null;
        }
        $newDate = Lib::convert2Datetime($strDate, $formatIn);
        if ($newDate != null) {
            return date_format($newDate, $formatOut);
        }
        return null;
    }

    /**
     * Convert string to date
     *
     * @param string $strTime format is date ex YYYY-mm-dd
     * @param string $format  ex format Y-m-d
     * * @return string|null
     */
    public static function convert2Datetime($strTime, $format)
    {
        try {
            if (Lib::isBlank($strTime)) {
                return null;
            }
            if (Lib::isBlank($format)) {
                return null;
            }
            return date_create_from_format($format, $strTime);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Compare date
     *
     * @param string $from_date YYYY-mm-dd
     * @param string $to_date YYYY-mm-dd
     * @param string $compare_type  compare type
     * -    1: >
     * -    2: <
     * -    3: =
     * -    4: >=
     * -    5: <=
     * @return boolean
     */
    public static function compareDate($fromDate = null, $toDate = null, $compareType = 1)
    {
        if (Lib::isBlank($fromDate) || Lib::isBlank($toDate)) {
            return false;
        }
        switch ($compareType) {
            case 1:
                return strtotime($fromDate) > strtotime($toDate);
            case 2:
                return strtotime($fromDate) < strtotime($toDate);
            case 3:
                return strtotime($fromDate) == strtotime($toDate);
            case 4:
                return strtotime($fromDate) >= strtotime($toDate);
            case 5:
                return strtotime($fromDate) <= strtotime($toDate);
        }
        return false;
    }

    /**
     * Check is date
     *
     * @param string $strDate
     * @param boolean $isFormat   true format date as YYYY/mm/dd, false: format date as YYYY-mm-dd
     * @return boolean
     */
    public static function checkIsDate($strDate = null, $isFormat = true)
    {
        if (Lib::isBlank($strDate)) {
            return false;
        }
        $dateArr = "";
        if ($isFormat) {
            $dateArr = explode('/', $strDate);
        } else {
            $dateArr = explode('-', $strDate);
        }
        if (!$dateArr || count($dateArr) < 2) {
            return false;
        }
        return checkdate($dateArr[1], $dateArr[2], $dateArr[0]);
    }

    /**
     * Returns the current date and time in the UTC timezone formatted as a string.
     *
     * @return string       The formatted date and time string.
     */
    public static function getStringDateUTC() {
        try {
            $utcNow = Carbon::now('UTC');
            return $utcNow->format('Y-m-d H:i:s.v');
        } catch(\Exception $e) {
            Log::info(__METHOD__ . $e->getMessage());
            return '';
        }
    }
    /**
    * Get the current time in milliseconds in UTC timezone.
    *
    * This function retrieves the current time in milliseconds in UTC timezone using Carbon library.
    *
    * @return int The current time in milliseconds in UTC timezone, or 0 if an error occurs.
    */
    public static function getMillisecondsTimeUTC() {
        try {
            $utcNow = Carbon::now('UTC');
            return $utcNow->valueOf();
        } catch(\Exception $e) {
            Log::info(__METHOD__ . $e->getMessage());
            return 0;
        }
    }

    /**
     * Convert a date UCT to milliseconds.
     *
     * @param mixed $date A string
     * @return int The number of milliseconds
     */
    public static function convertDateUTCToMilliseconds($date) {
        try {
            if (!$date) {
                return 0;
            }
            $utc = Carbon::parse($date, 'UTC')->setTimezone('UTC');
            if (!$utc) {
                return 0;
            }
            return $utc->valueOf();
        } catch(\Exception $e) {
            Log::info(__METHOD__ . $e->getMessage());
            return 0;
        }
    }
    /**
    * Convert milliseconds to UTC date format.
    *
    * @param int $milliseconds The number of milliseconds.
    * @return string The UTC date in 'Y-m-d H:i:s.v' format, or an empty string if conversion fails.
    */
    public static function convertMillisecondsToDateUTC($milliseconds) {
        try {
            if (!$milliseconds) {
                return '';
            }
            $carbon = Carbon::createFromTimestampMs($milliseconds);
            if (!$carbon) {
                return '';
            }
            $utc = $carbon->utc();
            return $utc->format('Y-m-d H:i:s.v');
        } catch(\Exception $e) {
            Log::info(__METHOD__ . $e->getMessage());
            return '';
        }
    }
    /**
    * Convert time string to milliseconds.
    *
    * @param string $strTime The time string to convert(ex: one day(1d), one hour(1h), one minute(1m)...)
    * @return float The time in milliseconds.
    */
    public static function convertTimeStringToMilliseconds($strTime) {
        try {
            $time = floatval($strTime);
            if (strpos($strTime, 'd') !== false) {
                return $time * 24 * 60 * 60 * 1000;
            }
            if (strpos($strTime, 'h') !== false) {
                return $time * 60 * 60 * 1000;
            }
            if (strpos($strTime, 'm') !== false) {
                return $time * 60 * 1000;
            }
            if (strpos($strTime, 's') !== false) {
                return $time * 1000;
            }
            if (strpos($strTime, 'ms') !== false) {
                return $time;
            }
            return $time;
        } catch (\Exception $e) {
            Log::info(__METHOD__ . $e->getMessage());
            return 0;
        }
    }

    /**
    * Trim spaces from all strings in the given array.
    *
    * @param array|null $arr The array to trim.
    * @return array|null The trimmed array or null if the input is not an array.
    */
    public static function trimArraySpaces($arr)
    {
        if (!is_array($arr)) {
            return null;
        }
        foreach ($arr as &$item) {
            if (is_string($item)) {
                $item = trim($item);
            }
        }
        return $arr;
    }

    /**
    * Check for comparison values that exist in the array
    *
    * @param array $arr ex: [1,3,5,...]
    * @param object $value comparison value exists in an array
    * @return boolean
    */
    public static function checkValueExistInArray($arr, $value)
    {
        if (!is_array($arr)) {
            return false;
        }
        return in_array($value, $arr);
    }

    /**
    * Check if the file extension matches the specified file type.
    *
    * @param string $fileName The name of the file.
    * @param int $fileType The type of the file (0: image, 1: mp3, 2: mp4, 3: zip).
    * @return bool True if the file extension matches the specified file type; otherwise, false.
    */
    public static function checkExtensionFile($fileName, $fileType)
    {
        if (Lib::isBlank($fileName) || Lib::isBlank($fileType)) {
            return false;
        }
        $extImg = array('png', 'jpg');
        $extMP3 = 'mp3';
        $extMP4 = 'mp4';
        $extZip = 'zip';
        $fileName = strtolower($fileName);
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        if ($fileType == 0) {
            return in_array($ext, $extImg);
        } elseif ($fileType == 1) {
            return $ext === $extMP3;
        } elseif ($fileType == 2) {
            return $ext === $extMP4;
        } elseif ($fileType == 3) {
            return $ext === $extZip;
        }
        return false;
    }

    /**
    * Generate a random string of specified length and type.
    *
    * @param int $length The length of the random string (default: 10).
    * @param int $type The type of characters to include in the random string (0: letters, 1: letters and numbers, 2: numbers only, 3: letters, numbers, and special characters, 4: lowercase letters only).
    * @return string The generated random string.
    */
    public static function randomString($length = 10, $type = 0)
    {
        $chars = "";
        if ($type == 0) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        } else if ($type == 1) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        } else if ($type == 2) {
            $chars = "0123456789";
        } else if ($type == 3) {
            //$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!\"\#$%&'()*+,-./:;<=>?@[\]^_`{|}~";
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$()*+,-.:;<=>@^_{|}";
        } else if ($type == 4) {
            $chars = "abcdefghijklmnopqrstuvwxyz";
        }
        if (!$chars) {
            return '';
        }
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $char = $chars[rand(0, strlen($chars) - 1)];
            $randomString .= $char;
        }
        return $randomString;
    }

    /**
     * Check email
     * @param string $email
     *
     * @return boolean
     */
    public static function checkEmail($email = null)
    {
        if (!$email) return false;
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
    * Remove duplicate entries from a multidimensional array based on a specific key.
    *
    * @param array $array The input multidimensional array.
    * @param string $key The key to check for uniqueness.
    * @return array The array with duplicate entries removed.
    */
    public static function uniqueMultiDimensionalArrayByKey($array, $key) {
        $result = array();
        $i = 0;
        $keyArray = array();
        foreach($array as $val) {
            if (is_object($val)) {
                $val = (array)$val;
            }
            if (!in_array($val[$key], $keyArray)) {
                $keyArray[$i] = $val[$key];
                $result[$i] = $val;
            }
            $i++;
        }
        return $result;
    }
    /**
    * Check if two arrays are equal.
    *
    * This function compares two arrays to check if they contain the same elements, regardless of their order.
    *
    * @param array $array1 The first array to compare.
    * @param array $array2 The second array to compare.
    * @return bool True if the arrays are equal; otherwise, false.
    */
    public static function  checkArrayEqual($array1, $array2) {
        if (!is_array($array1) || !is_array($array2)) {
            return false;
        }
        if(
            $array1 === array_intersect($array1, $array2)
            && $array2 === array_intersect($array2, $array1)
        ) {
            return true;
        }
        return false;
    }
    /**
    * Convert an object to an array.
    *
    * This function converts an object to an associative array using get_object_vars function.
    * If the input is already an array, it returns the input array unchanged.
    *
    * @param mixed $data The data to convert to an array.
    * @return array The converted array or the input data if it's already an array.
    */
    public static function convertObjectToArray($data) {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (is_array($data)) {
            return $data;
        }
        else {
            return $data;
        }
    }
    /**
    * Assign data based on table structure.
    *
    * This function assigns data from the input array to a new array based on the provided table structure.
    * Only keys that exist in both the table structure and the input data will be included in the result array.
    *
    * @param array $tblStructure The structure of the table (array of column names).
    * @param mixed $data The data to assign to the table structure.
    * @return array|null The assigned data array or null if either input is empty.
    */
    public static function assignData($tblStructure, $data) {
        if ($tblStructure && $data) {
            $result = array();
            $data = Lib::convertObjectToArray($data);
            foreach ( $data as $key => $val ){
                if(Lib::checkValueExistInArray($tblStructure, $key)) {
                    $result[$key] = $val;
                }
            }
            return $result;
        }
        return;
    }
    /**
     * console.log a variable
     * @param mixed $variable
     */
    public static function consoleLog ($variable) {
        $console = new ConsoleOutput();
        $console->writeln(json_encode($variable));
    }
    /**
     * Get password regular expression
     * @return string Password regular expression
     */
    public static function getPasswordRegex() {
        return '/^[a-zA-Z0-9!@?#$%^&*<>()]+$/';
    }
    /**
     * Get katakana regular expression
     * @return string Kana regular expression
     */
    public static function getKatakanaRegex() {
        return '/^[\u30A0-\u30FF]+$/';
    }
    /**
     * Get alphanumeric regular expression
     * @return string alphanumeric regular expression
     */
    public static function getAlphaNumericRegex() {
        return '/^[A-Za-z0-9]+$/';
    }
    /**
     * Get slug regular expression
     * @return string alphanumeric regular expression
     */
    public static function getSlugRegex() {
        return '/^[a-z0-9-]+$/';
    }
    /**
     * Get url regular expression
     * @return string alphanumeric regular expression
     */
    public static function getUrlRegex() {
        return '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';
    }
    /**
     * Add time to a specific day
     *
     * @param  string $date any English textual datetime description, see https://www.php.net/manual/en/function.strtotime.php
     * @param  string $time any English textual datetime description, see https://www.php.net/manual/en/function.strtotime.php
     * @param  string $format target format, "Y,m,d" by default
     * @return string formatted date time
     */
    public static function addTimeToDate($date, $time, $format = 'Y,m,d') {
        return date($format, strtotime($time, strtotime($date)));
    }
    /**
     * Search in array, item's property have value which you need to search. If item is found, return key array.
     * @param array $array
     * @param $property
     * @param $value
     * @param bool $strictCmp default is true
     * @return array
     */
    public static function arraySearch(&$array, $property, $value, $strictCmp = true)
    {
        $result = array();
        foreach ($array as $item) {
            if (is_object($item)) {
                $item = (array)$item;
            }
            if (($strictCmp && $item[$property] === $value) ||
                (!$strictCmp && $item[$property] == $value)) {
                array_push($result, $item);
            }
        }
        return $result;
    }
    /**
    * Check if a value exists in an array.
    *
    * This function checks if the given value exists in the provided array.
    * Optionally, it can return the position of the value in the array if $isPosition is set to true.
    *
    * @param mixed $name The value to search for.
    * @param array $array The array to search in.
    * @param bool $isPosition Whether to return the position of the value in the array (default: false).
    * @return int|bool|int|false If $isPosition is true, returns the position of the value; otherwise, returns true if the value exists, false otherwise.
    */
    public static function checkValueExistsArray($name = null, $array = array(), $isPosition = false)
    {
        if ($name && $array) {
            if($isPosition) {
                return array_search($name, $array);
            } else if(in_array($name, $array)) {
                return true;
            }
        }
        return false;
    }
    /**
    * Convert an object or array of objects to an array.
    *
    * This function converts the input object or array of objects to an array of associative arrays.
    *
    * @param mixed $result The object or array of objects to convert.
    * @return array|null The converted array or null if the input is empty.
    */
    public static function toArray($result)
    {
        if (!$result) {
            return;
        }
        return array_map(function ($value) {
            return (array)$value;
        }, $result);
    }

    /**
    * Encrypt a string using CryptoJS AES algorithm.
    *
    * This function encrypts the input string using the CryptoJS AES algorithm with the provided public key and initialization vector.
    *
    * @param string|null $str The string to encrypt.
    * @return string|null The encrypted string, or null if the input is empty.
    */
    public static function cryptoJsAesEncrypt($str = null){
        try {
            if (!$str) {
                return null;
            }
            $key = utf8_encode(CRYPTOJS['PUBLIC_KEY']);
            $iv = utf8_encode(CRYPTOJS['INIT_VECTOR']);
            $output = openssl_encrypt($str, CRYPTOJS['ENCRYPT_METHOD'], $key, 0, $iv);
            return $output;
        } catch(\Exception $e) {
            Log::info(__METHOD__ . " cryptoJsAesEncrypt : " . $e->getMessage());
            return null;
        }
    }
    /**
    * Decrypt a string encrypted using CryptoJS AES algorithm.
    *
    * This function decrypts the input string encrypted using CryptoJS AES algorithm with the provided public key and initialization vector.
    *
    * @param string|null $str The encrypted string to decrypt.
    * @return string|null The decrypted string, or null if the input is empty.
    */
    public static function cryptoJsAesDecrypt($str = null){
        try {
            if (empty($str)) {
                return null;
            }
            $key = utf8_encode(CRYPTOJS['PUBLIC_KEY']);
            $iv = utf8_encode(CRYPTOJS['INIT_VECTOR']);
            $output = openssl_decrypt($str, CRYPTOJS['ENCRYPT_METHOD'], $key, 0, $iv);
            return $output;
        } catch(\Exception $e) {
            Log::info(__METHOD__ . $e->getMessage());
            return null;
        }
    }

    /**
     * Replace * to % for search like sql.
     * If not contain character * change keyword to %keyword%
     *
     * @param string $str keyword search
     * @return string keyword with %
     */
    public static function asteriskSearch($str) {
        if (Lib::isBlank($str)) return "";
        $str = Lib::escapeLike($str);
        $search = "*";
        $replace = "%";
        if (str_contains($str, $search)) {
            return str_replace($search, $replace, $str);
        }
        return $replace.$str.$replace;
    }

}
