<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2018/12/15
 * Time: 下午2:40
 */

namespace App\Support\Traits;
use Illuminate\Support\Facades\Storage;

trait OssStore{

    /**
     * 上传阿里云oss
     * @param $name =>文件名字;
     * @return mixed
     */
    public function ossStore($name,$path='temp/')
    {
        $storage = Storage::disk('oss');

        if ($storage->put($path . $name, file_get_contents(storage_path() . '/app/public/' . $name))) {
            $url = $storage->url($path . $name);  //oss 存储地址
            Storage::disk('public')->delete($name);  //删除本地pdf
            return $url;
        }else{
            apiError('oss上传失败');
        }

    }

    /**
     * 七牛云上传
     * @param $name
     * @param string $path
     * @return mixed
     */
    public function qiniuStore($name,$path='temp/')
    {
        $storage = Storage::disk('qiniu');
        if ($storage->put($path . $name, file_get_contents(storage_path() . '/app/excel/' . $name))) {
            $url = $storage->url($path . $name);  //oss 存储地址
            Storage::disk('public')->delete($name);  //删除本地pdf
            return $url;
        }else{
            apiError('oss上传失败');
        }

    }


}