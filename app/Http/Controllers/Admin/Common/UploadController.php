<?php
/**
 * Created by PhpStorm.
 * User: xiao
 * Date: 2018/11/13
 * Time: 上午10:49
 */

namespace App\Http\Controllers\Admin\Common;


use App\Http\Controllers\Controller;
use App\Models\Common\CommonUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class UploadController extends Controller {
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
}
