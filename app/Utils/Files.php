<?php

namespace App\Utils;

//use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Utils\Constants;
use Illuminate\Support\Facades\Log;
use Exception;

class Files {

    /**
     *  File upload
     *
     * @param array  $files
     * [
     *      "file" => File,//Uploaded file
     *      "chunk" => number, //Number of chunks uploaded
     *      "chunks" => number, //Total number of file chunks
     *      "name" => string,//Name of the main uploaded file
     *      "fileNameTmp" => string,//Temporary uploaded file name as a random string to avoid collisions
     *      "isPendingSubmit" => boolean, // Pending submit form data
     *      "indexFile" => number, //Number of File uploaded
     *      "indexFileTotal" => number, //Total number of file uploaded
     *      "removeFilesString" => string, // array split '##|##' file name to remove
     * ]
     * @return array
     */
    public static function onFileUpload($files = null) {
        try {
            if (!$files) {
                return [
                    "code" => 101,
                    "message" => __('message.err_fopen_input'),
                ];
            }
            //Create a storage disk
            $storage = Storage::disk(STORAGE_DISK_LOCAL);
            //Create temp dir
            if (!$storage->exists(TEMP_DIR)) {
                $storage->makeDirectory(TEMP_DIR);
            }

            $fileUpload =  $files['file'];
            // Get a file name
            $fileName = $files['name'];
            $tempFileName = $files['fileNameTmp'];
            $keyRandom = $files['keyRandom'];
            if (empty($fileUpload) || empty($fileName) || empty($tempFileName)) {
                $result['code'] = 101;
                $result['message'] =  __('message.err_fopen_input');
                return $result;
            }
            //Create upload dir
            $currentUploadDir = $files['folderPath'];
            $uploadDir = UPLOAD_DIR . DIRECTORY_SEPARATOR . $currentUploadDir;
            // Settings
            $targetDir = $storage->path(TEMP_DIR);

            $filePath = $targetDir . DIRECTORY_SEPARATOR . $tempFileName;
            $filePath = str_replace('\\', '/', $filePath);
            // Chunking might be enabled
            $chunk = !empty($files['chunk']) ? intval($files['chunk']) : 0;
            $chunks = !empty($files['chunks']) ? intval($files['chunks']) : 0;
            // File number might be enabled
            $indexFile = !empty($files['indexFile']) ? intval($files['indexFile']) : 0;
            $indexFileTotal = !empty($files['indexFileTotal']) ? intval($files['indexFileTotal']) : 0;
            // Remove old temp files
            if (CLEANUP_TARGET_DIR) {
                if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                    $result['code'] = 100;
                    $result['message'] =  __('message.err_open_dir');
                    return $result;
                }
                while (($file = readdir($dir)) !== false) {
                    $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
                    $tmpfilePath = str_replace('\\', '/', $tmpfilePath);
                    // If temp file is current file proceed to the next
                    if ($tmpfilePath == "{$filePath}.part") {
                        continue;
                    }
                    // Remove temp file if it is older than the max age and is not the current file
                    if ((filemtime($tmpfilePath) < (time() - MAX_FILE_AGE))) {
                        @unlink($tmpfilePath);
                    }
                }
                closedir($dir);
            }
            // Open temp file
            if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
                $result['code'] = 102;
                $result['message'] =  __('message.err_fopen_output');
                return $result;
            }
            if (!file_exists ("{$filePath}.part")) {
                $result['code'] = 103;
                $result['message'] =  __('message.err_move_uploaded');
                return $result;
            }
            // Read binary input stream and append it to temp file
            if (!$in = @fopen($fileUpload->getRealPath(), "rb")) {
                $result['code'] = 101;
                $result['message'] =  __('message.err_fopen_input');
                return $result;
            }
            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }
            @fclose($out);
            @fclose($in);
            // Check if file has been uploaded
            if (!$chunks || $chunk == $chunks) {
                // Strip the temp .part suffix off
                rename("{$filePath}.part", $filePath);
            }
            $newFileName = "";
            //finish uploading
            if ($chunk == $chunks) {
                $tempFilePathSuccess = TEMP_DIR . DIRECTORY_SEPARATOR . $tempFileName;
                //Check file upload
                if (!$storage->exists($tempFilePathSuccess)) {
                    $result['code'] = 101;
                    $result['message'] =  __('message.err_file_upload');
                    return $result;
                }
                $result['dimensions'] = "";
                //get new file name upload
                $newFileName = Files::_getFileNameUpload($fileName, $uploadDir);
                $result['file_path'] = $files['isPendingSubmit'] ? $tempFilePathSuccess : $currentUploadDir . DIRECTORY_SEPARATOR . $newFileName;
            }
            // Move all file to upload folder
            if(!$files['isPendingSubmit']) {
                if ($chunk == $chunks && $indexFile == $indexFileTotal) {
                    $allFiles = $storage->allFiles(TEMP_DIR);
                    $filteredFiles = [];
                    foreach ($allFiles as $file) {
                        if(substr(basename($file), 0, strlen($keyRandom)) === $keyRandom) {
                            array_push($filteredFiles, $file);
                        }
                    }
                    foreach ($filteredFiles as $file) {
                        $basename = basename($file);
                        $fileName = substr($basename, strlen($keyRandom), strlen($basename));
                        $tempFilePathSuccess = TEMP_DIR . DIRECTORY_SEPARATOR . $basename;
                        $storage->move($tempFilePathSuccess, $uploadDir . DIRECTORY_SEPARATOR . $fileName);
                    }
                }
            }
            $result['code'] = 200;
            $result['message'] =  __('message.upload_successfully');
            $result['chunk'] = $chunk;
            $result['indexFile'] = $indexFile;
            $result['name'] = $newFileName;
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $result['code'] = 0;
            $result['message'] =  $e->getMessage();
            return $result;
        }
    }

    /**
     *  File upload
     *
     * @param array  $files
     * [
     *      "file" => File,//Uploaded file
     *      "chunk" => number, //Number of chunks uploaded
     *      "chunks" => number, //Total number of file chunks
     *      "name" => string,//Name of the main uploaded file
     *      "temp_file_name" => string,//Temporary uploaded file name as a random string to avoid collisions
     * ]
     * @return array
     */
    public static function onChunkFileUpload($files = null) {
        try {
            if (!$files) {
                return [
                    "code" => 101,
                    "message" => __('admin::message.err_fopen_input'),
                ];
            }
            $fileUpload = $files['file'];
            // Get a file name
            $fileName = $files['name'];
            $tempFileName = $files['temp_file_name'];
            if (empty($fileUpload) || empty($fileName) || empty($tempFileName)) {
                $result['code'] = 101;
                $result['message'] =  __('admin::message.err_fopen_input');
                return $result;
            }
            //Create a storage disk
            $storage = Storage::disk(STORAGE_DISK_LOCAL);
            //Create temp dir
            if (!$storage->exists(TEMP_DIR)) {
                $storage->makeDirectory(TEMP_DIR);
            }
            //Create upload dir
            $currentUploadDir = date('Y') . DIRECTORY_SEPARATOR . date('m');
            $uploadDir = UPLOAD_DIR . DIRECTORY_SEPARATOR . $currentUploadDir;
            if (!$storage->exists($uploadDir)) {
                $storage->makeDirectory($uploadDir);
            }

            // Settings
            $targetDir = $storage->path(TEMP_DIR);
            $filePath = $targetDir . DIRECTORY_SEPARATOR . $tempFileName;
            // Chunking might be enabled
            $chunk = !empty($files['chunk']) ? intval($files['chunk']) : 0;
            $chunks = !empty($files['chunks']) ? intval($files['chunks']) : 0;
            // Remove old temp files
            if (CLEANUP_TARGET_DIR) {
                if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                    $result['code'] = 100;
                    $result['message'] =  __('admin::message.err_open_dir');
                    return $result;
                }
                while (($file = readdir($dir)) !== false) {
                    $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
                    // If temp file is current file proceed to the next
                    if ($tmpfilePath == "{$filePath}.part") {
                        continue;
                    }
                    // Remove temp file if it is older than the max age and is not the current file
                    if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < (time() - MAX_FILE_AGE))) {
                        @unlink($tmpfilePath);
                    }
                }
                closedir($dir);
            }
            // Open temp file
            if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
                $result['code'] = 102;
                $result['message'] =  __('admin::message.err_fopen_output');
                return $result;
            }
            if ($fileUpload["error"] || !is_uploaded_file($fileUpload["tmp_name"])) {
                $result['code'] = 103;
                $result['message'] =  __('admin::message.err_move_uploaded');
                return $result;
            }
            // Read binary input stream and append it to temp file
            if (!$in = @fopen($fileUpload["tmp_name"], "rb")) {
                $result['code'] = 101;
                $result['message'] =  __('admin::message.err_fopen_input');
                return $result;
            }
            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }
            @fclose($out);
            @fclose($in);
            // Check if file has been uploaded
            if (!$chunks || $chunk == $chunks) {
                // Strip the temp .part suffix off
                rename("{$filePath}.part", $filePath);
            }
            $newFileName = "";
            //finish uploading
            if ($chunk === $chunks) {
                $tempFilePathSuccess = TEMP_DIR . DIRECTORY_SEPARATOR . $tempFileName;
                //Check file upload
                if (!$storage->exists($tempFilePathSuccess)) {
                    $result['code'] = 101;
                    $result['message'] =  __('admin::message.err_file_upload');
                    return $result;
                }
                $result['dimensions'] = "";
                //get new file name upload
                $newFileName = Upload::_getFileNameUpload($fileName, $uploadDir);
                $result['file_path'] = $currentUploadDir . DIRECTORY_SEPARATOR . $newFileName;
                //Check the uploaded file is an image
                $imageSize = getimagesize($filePath);
                if (!ALLOW_RESIZE_IMAGE) {
                    $storage->move($tempFilePathSuccess, $uploadDir . DIRECTORY_SEPARATOR . $newFileName);
                    if ($imageSize) {
                        $imageW = !empty($imageSize[0]) ? $imageSize[0] : 0;
                        $imageH = !empty($imageSize[1]) ? $imageSize[1] : 0;
                        $result['dimensions'] = $imageW . " x " . $imageH;
                    }
                } else {
                    if (!$imageSize) {
                        $storage->move($tempFilePathSuccess, $uploadDir . DIRECTORY_SEPARATOR . $newFileName);
                    } else {
                        $pathParts = pathinfo($newFileName);
                        $image = Image::make($filePath);
                        $imageW = !empty($imageSize[0]) ? $imageSize[0] : 0;
                        $imageH = !empty($imageSize[1]) ? $imageSize[1] : 0;
                        $result['dimensions'] = $imageW . ' x ' . $imageH;
                        //Save current file upload to new dir
                        $storage->move($tempFilePathSuccess, $uploadDir . DIRECTORY_SEPARATOR . $newFileName);
                        if ($image) {
                            // $imageW = $image->width();
                            // $imageH = $image->height();
                            for ($i = (count(RESIZE_IMAGES) - 1); $i >= 0; $i--) {
                                $resizeImageW = RESIZE_IMAGES[$i];
                                $resizeImageH = round(($resizeImageW * $imageH)/$imageW);
                                if ($imageW > $resizeImageW) {
                                    $resizeImage = $image->resize($resizeImageW, $resizeImageH, function ($constraint) {
                                        $constraint->aspectRatio();
                                    })->stream();
                                    $resizeFileName = $pathParts['filename'] . '-' . sprintf(PREFIX_RESIZE_IMAGE, $resizeImageW, $resizeImageH) . '.' . $pathParts['extension'];
                                    $storage->put($uploadDir . DIRECTORY_SEPARATOR . $resizeFileName, (string) $resizeImage, 'public');
                                }
                            }
                        }
                    }
                }
            }
            $result['code'] = 200;
            $result['message'] =  __('admin::message.upload_successfully');
            $result['chunk'] = $chunk;
            $result['file_name'] = $newFileName;
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $result['code'] = 0;
            $result['message'] =  $e->getMessage();
            return $result;
        }
    }
    /**
     * Get new file name upload
     * @param string $fileName
     * @param string $dir       directory containing file upload
     * @param string $disk      directory in local storage
     * @return string
     */
    private static function _getFileNameUpload($fileName, $dir, $disk = STORAGE_DISK_LOCAL)
    {
        try {
            if (!$fileName || !$dir) {
                return "";
            }
            //Create a storage disk
            $storage = Storage::disk($disk);
            //Check existence of files in the temporary directory
            $allFiles = $storage->allFiles($dir);
            $newFileName = "";
            if ($allFiles && count($allFiles) > 0) {
                $checkFile = array_filter($allFiles, function ($item) use ($dir, $fileName) {
                    //return pathinfo($item)['filename'] === pathinfo($fileName)['filename'] && pathinfo($item)['extension'] === pathinfo($fileName)['extension'];
                    return $item === $dir . DIRECTORY_SEPARATOR . $fileName;
                });
                if ($checkFile && count($checkFile) > 0) {
                    for ($i = 1; $i <= count($allFiles); $i++) {
                        $checkNewFile = pathinfo($fileName)['filename'] . '-' . $i;
                        $tempFile = array_filter($allFiles, function ($item) use ($checkNewFile, $fileName) {
                            return strpos(pathinfo($item)['filename'], $checkNewFile)  !== false && pathinfo($item)['extension'] === pathinfo($fileName)['extension'];
                        });
                        if (!$tempFile) {
                            $newFileName = $checkNewFile . '.' . pathinfo($fileName)['extension'];
                            break;
                        }
                    }
                    if (!$newFileName) {
                        $newFileName = pathinfo($fileName)['filename'] . '-1.' . pathinfo($fileName)['extension'];
                    }
                }
            }
            if (!$newFileName) {
                $newFileName = $fileName;
            }
            return $newFileName;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return "";
        }
    }

    /**
     *  Delete file
     * @param array  $files
     * [
     *      "file" => File,//Uploaded file
     *      "chunk" => number, //Number of chunks uploaded
     *      "chunks" => number, //Total number of file chunks
     *      "name" => string,//Name of the main uploaded file
     *      "fileNameTmp" => string,//Temporary uploaded file name as a random string to avoid collisions
     *      "isPendingSubmit" => boolean, // Pending submit form data
     *      "indexFile" => number, //Number of File uploaded
     *      "indexFileTotal" => number, //Total number of file uploaded
     *      "removeFilesString" => string, // array split '##|##' file name to remove
     * ]
     * @return array
     * @author hung.le 2024/07/17 15:00
     */
    public static function onDelete($files = null) {
        try {
            if (!$files) {
                return [
                    "code" => 101,
                    "message" => __('message.err_fopen_input'),
                ];
            }
            //Create a storage disk
            $storage = Storage::disk(STORAGE_DISK_LOCAL);
            /**
             * Remove all files
             */
            if(!$files['isPendingSubmit']) {
                //Create upload dir
                $folderPath = $files['folderPath'];
                $targetDir = UPLOAD_DIR . DIRECTORY_SEPARATOR . $folderPath;
                $filePath = $targetDir . DIRECTORY_SEPARATOR . $files['name'];
                /**
                 * Remove files
                 */
                if(!$storage->exists($filePath)) {
                    $result['code'] = 200;
                    $result['message'] =  __('message.upload_successfully');
                    return $result;
                }
                $isDelete = $storage->delete($targetDir . DIRECTORY_SEPARATOR . $files['name']);
                if(!$isDelete) return null;
                $result['code'] = 200;
                $result['message'] =  __('message.upload_successfully');
                return $result;
            } else {
                $result['code'] = 200;
                $result['message'] =  __('message.upload_successfully');
                return $result;
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }

    /**
     * Get file from request
     * @date 2024/07/05
     * @author hung.le
     */
    public static function getFileFromRequest ($request) {
        $file = $request['file'];
        if($file) {
            return [
                'file' => $file,
                'name' => $file->getClientOriginalName(),
                'tmp_name' => $file->getClientOriginalName(),
                'isPendingSubmit' => $request['isPendingSubmit'] == 'true',
                'isDeleteAll' => !empty($request['isDeleteAll']),
                'fileSize' => $file->getSize(),
                'folderPath' => empty($request['folderPath']) ? 0 : $request['folderPath'],
                'keyRandom' => empty($request['keyRandom']) ? 0 : $request['keyRandom'],
                'fileNameTmp' => $request['keyRandom'] . $file->getClientOriginalName(),
                'chunk' => empty($request['chunk']) ? 0 : $request['chunk'],
                'chunks' => empty($request['chunks']) ? 0 : $request['chunks'],
                'indexFile' => empty($request['indexFile']) ? 0 : $request['indexFile'],
                'indexFileTotal' => empty($request['indexFileTotal']) ? 0 : $request['indexFileTotal'],
                'error' => '',
                'removeFilesString' => empty($request['removeFilesString']) ? 0 : $request['removeFilesString'],
            ];
        }
        return null;
    }
    /**
     * Synched files when submit form success
     * @param array $synchedFiles
     * @param string $folderPath if create
     * @param boolean $isCopy if true => copy with action = 2
     * @return boolean
     * @date 2024/07/05
     * @author hung.le
     */
    public static function synchedFiles ($cond = null, $targetPath = null, $isCopy = false) {
        try {

            if(!$cond || empty($cond)) return false;
            $synchedFiles = $cond['synchedFiles'];
            if(!$synchedFiles || empty($synchedFiles)) return false;

            $folderPath = empty($cond['folderPath']) ? null : $cond['folderPath'];
            $folderPath = $targetPath ? $targetPath : $folderPath;
            if(!$folderPath) return false;
            //Create a storage disk
            $storage = Storage::disk(STORAGE_DISK_LOCAL);
            $uploadDir = UPLOAD_DIR . DIRECTORY_SEPARATOR . $folderPath;
            $uploadDir = str_replace('\\', '/', $uploadDir);

            foreach ($synchedFiles as $file) {
                $fileName = $file['name'];
                $filePath = str_replace('\\', '/', $file['file_path']);
                $action = empty($file['action']) ? 0 : $file['action']; //0: move, 1: delete
                if($action == 0) { // Move files pending submit form data
                    if(!$storage->exists($filePath)) continue;
                    $storage->move($filePath, $uploadDir . DIRECTORY_SEPARATOR . $fileName);
                } else if($action == 1) { // Delete file
                    $filePath = str_replace('\\', '/', $uploadDir . DIRECTORY_SEPARATOR . $file['name']);
                    if(!$storage->exists($filePath)) continue;
                    $storage->delete($filePath);
                } else if($action == 2) { // Copy file
                    if(!$isCopy) continue;
                    if(!$storage->exists($filePath)) continue;
                    $targetToCopy = str_replace('\\', '/', $uploadDir . DIRECTORY_SEPARATOR . $fileName);
                    $storage->copy($filePath, $targetToCopy);
                }

            }
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
    /**
     * Synched files when submit form success
     * @param array $synchedFiles
     * @return [
     *  'name' => ?
     *  'type' => ?
     * ]
     * @date 2024/07/05
     * @author hung.le
     */
    public static function getFiles ($request = null) {
        try {
            if(!$request || empty($request)) return null;

            $folderPath = empty($request['folderPath']) ? null : $request['folderPath'];
            if(!$folderPath) return null;
            //Create a storage disk
            $storage = Storage::disk(STORAGE_DISK_LOCAL);
            $targetDir = UPLOAD_DIR . DIRECTORY_SEPARATOR . $folderPath;
            $targetDir = str_replace('\\', '/', $targetDir);

            $allFiles = $storage->allFiles($targetDir);
            $resultFiles = [];
            foreach ($allFiles as $file) {
                // Get file name
                $fileName = basename($file);
                // Get file type (extension)
                $fileType = pathinfo($file, PATHINFO_EXTENSION);
                // Get file size
                $fileSize = $storage->size($file);
                $fileInfo = pathinfo($file);
                $filePath = str_replace('\\', '/', $fileInfo['dirname']);
                array_push($resultFiles, [
                    'name' => $fileName,
                    'file_path' => $filePath,
                    'type' => $fileType,
                    'size' => $fileSize,
                    'action' => 2,
                    'isClone' => true
                ]);
            }
            return $resultFiles;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }
}
