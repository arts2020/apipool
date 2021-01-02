<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Exception;

class UploadController extends ApiController
{
    /**
     * 识别 key.
     */
    const UPLOAD_KEY = 'file';

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * 上传文件.
     * @return json
     */
    public function index(Request $request)
    {
        if (!$request->get('type')) {
            throw new Exception('file type error.', 422);
        }
        $configName = 'upload.';

        $type = $request->get('type');

        $userPath = md5($this->user_id);

        $logoPath = substr($userPath,0,8).'/'.substr($userPath, 8).'/';

        $dir = config($configName . $type.'.storage_path') . $logoPath;

        $prefix = config($configName .$type.'.prefix') . $logoPath;

        is_dir($dir) || mkdir($dir, 0755, true);

        $file = $this->uploader($request, $dir, $prefix, $configName);

        return response()->json($file);
    }


    private function uploader($request,$dir,$prefix,$configs)
    {
        if (!$request->hasFile(self::UPLOAD_KEY)) {
            throw new Exception('no file found.', 422);
        }

        $type = $request->get('type');

        $file = $request->file(self::UPLOAD_KEY);

        $filesize = $file->getSize();

        $mime = $file->getMimeType();

        $ext = $this->checkMime($mime, $type,$configs);

        $this->checkSize($filesize, $type,$configs);

        $originalName = $file->getClientOriginalName();

        $filename = md5_file($file->getRealpath()).'.'.$ext;

        if (!file_exists($dir.$filename)) {
            $file->move($dir, $filename);
        }

        return [
            'name'  => $originalName,
            'size'  => $filesize,
            'type'  => $ext,
            'path'  => $filename,
            'url'   => $prefix .$filename,
            'state' => TRUE,
        ];
    }


    /**
     * 检查大小.
     *
     * @param int    $size
     * @param string $type 上传文件类型
     *
     * @throws Exception If too big.
     */
    protected function checkSize($size, $type,$configs)
    {
        if ($size > config($configs.$type.'.upload_max_size')) {
            throw new Exception('To big file.', 422);
        }
    }

    /**
     * 检测Mime类型.
     *
     * @param string $mime mime类型
     * @param string $type 文件上传类型
     *
     * @return bool
     */
    protected function checkMime($mime, $type,$configs)
    {
        $allowTypes = config($configs.$type.'.allow_types');
        if (!$ext = array_search($mime, $allowTypes)) {
            throw new Exception('Error file type', 422);
        }

        return $ext;
    }

}
