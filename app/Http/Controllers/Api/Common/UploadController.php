<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2018/11/13
 * Time: 上午10:49
 */

namespace App\Http\Controllers\Admin\Common;

use App\Models\Common\CommonUpload;
use App\Support\Traits\OssStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Larfree\Controllers\ApisController as Controller;

class UploadController extends Controller {
    use  OssStore;

    /**
     * 图片上传使用这个方法即可
     * @param Request $request
     * @param string $type
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Larfree\Exceptions\ApiException
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function images(Request $request, $type = '') {
        $rule = [
            'file' => 'required',
        ];
        $message = [
            'file.required' => '请选择要上传的文件',
        ];
        $validator = $this->validate($request, $rule, $message);

        if (!$validator) {
            apiError($validator);
        }
        return CommonUpload::uploadImg($request->file('file'), $type);
    }


    /**
     * @OA\Post(
     *   summary="上传信息",
     *   description="上传信息",
     *   path="/common/file",
     *   tags={"文件相关"},
     *   security={{
     *     "jwt":{}
     *   }},
     *   @OA\Response(
     *     response="200",
     *     description="请求成功 ",
     *   ),
     * )
     */

    public function uploadInfo(Request $request) {
        ini_set('memory_limit', '3072M');
        set_time_limit(0);
        if ($request->isMethod('post')) {
            if ($request->file('fileUpload')) {
                $file = $request->file('fileUpload');
            } else {
                $file = $request->file('file');
            }
            if (empty($file)) {
                apiError('上传文件为空');
            }
            if ($file->isValid()) {
                $size = $file->getSize();
                if ($size > 300 * 1024 * 1024) {
                    apiError('上传文件不能超过400M');
                }
                $ext = $file->getClientOriginalExtension();
                /*         $filetype = ['xls','xlsx'];
                         if(!in_array($ext,$filetype)){
                             apiError('不是要求类型');
                         }*/
                $realPath = $file->getRealPath();   //临时文件的绝对路径
                $files = file_get_contents($realPath);
                $filename = date('Y-m-d') . uniqid() . '.' . $ext;//新文件名 文件名不能重复
                $url = storage_path() . '/app/pic/' . $filename;
                if (!file_exists($url)) {
                    fopen($url, 'w');
                }
                $path = file_put_contents($url, $files);
                if ($path) {
                    $res = [
                        'name' => $filename,
                        'path' => $this->qiniuStore($filename),
                        'size' => @$size ? round($size / 1024, 2) . 'kb' : 0
                    ];
                    return Response()->success($res, '上传成功');
                }
            }
        }
    }

    function doUpload(Request $request) {
        $file = $request->file('video');
        if ($file->isValid()) {
            //$originalName = $file->getClientOriginalName(); // 文件原名
            $ext = $file->getClientOriginalExtension();     // 扩展名
            $realPath = $file->getRealPath();   //临时文件的绝对路
            $filename = date('Y-m-d-H-i-s') . '-' . uniqid() . '.' . $ext;
            $bool = Storage::disk('uploads')->put($filename, file_get_contents($realPath));
            if (true == $bool) {
                $SQLFileName['path'] = '/uploads/' . $filename;
                array_push($SQLFileName, $ext);
                return $SQLFileName;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}
