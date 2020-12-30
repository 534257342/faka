<?php
namespace App\Models\Common;

use App\Events\Excel\InfoExcel;
use App\Support\Traits\OssStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommonUpload{
    use OssStore;
    public static function uploadImg($file,$type=''){

        //获取上传文件的大小
        $size = $file->getSize();
        //这里可根据配置文件的设置，做得更灵活一点
        if($size > 10*1024*1024){
            apiError('上传文件不能超过10M');
        }
        //文件类型
        $mimeType = $file->getMimeType();
        $imgType = explode('/',$mimeType);
        //这里根据自己的需求进行修改
        $filetype = ['jpg', 'jpeg', 'gif', 'bmp', 'png','pdf'];
        if(!in_array($imgType[1],$filetype)){
            apiError('不是图片类型');
        }
        //扩展文件名
        $ext = $file->getClientOriginalExtension();
        //判断文件是否是通过HTTP POST上传的
        $realPath = $file->getRealPath();

        if(!$realPath){
            apiError('非法操作');
        }

        //创建以当前日期命名的文件夹
        $today = date('Y-m-d');
        $filename = uniqid().'.'.$ext;//新文件名
        if($type){//如果传入filesName
            $today = $type.'/'.$today;
        }
        $filename = $today.'/'.$filename;

        $storage = Storage::disk('qiniu');
        if($storage->put($filename,file_get_contents($realPath))){
            $url = $storage->url($filename);
            $small_url  = getThumbs($filename,'200','200');
            $big_url  = getThumbs($filename,'1000','1000');
            return response(['name'=>$filename,'small_url'=>$small_url,'url'=>$url,'big_url'=>$big_url],200);
        }else{
            apiError('上传失败');
        }
    }

    public static function uploadFile1($file,$type=''){

        //获取上传文件的大小
        $size = $file->getSize();
        //这里可根据配置文件的设置，做得更灵活一点
        if($size > 100*1024*1024){
            apiError('上传文件不能超过100M');
        }
        //扩展文件名
        $ext = $file->getClientOriginalExtension();
        //判断文件是否是通过HTTP POST上传的
        $realPath = $file->getRealPath();

        if(!$realPath){
            apiError('非法操作');
        }

        //创建以当前日期命名的文件夹
        $today = date('Y-m-d');
        $filename = uniqid().'.'.$ext;//新文件名
        if($type){//如果传入filesName
            $today = $type.'/'.$today;
        }
        $filename = $today.'/'.$filename;

        $storage = Storage::disk('qiniu');
        if($storage->put($filename,file_get_contents($realPath))){
            $url = $storage->url($filename);
            event(new InfoExcel($url));
            return response(['name'=>$filename,'url'=>$url],200);
        }else{
            apiError('上传失败');
        }
    }

    public   function  uploadFile($file,$order_id='',$user_id=''){

        if ($file->isValid()) {
            $size = $file->getSize();
            //这里可根据配置文件的设置，做得更灵活一点
            if($size > 300*1024*1024){
                apiError('上传文件不能超过300M');
            }

            $ext = $file->getClientOriginalExtension();
            $filetype = ['xls','xlsx'];
            if(!in_array($ext,$filetype)){
                apiError('不是要求类型');
            }

            $realPath = $file->getRealPath();   //临时文件的绝对路径
            $files = file_get_contents($realPath);

            $filename = date('Y-m-d').uniqid().'.'.$ext;//新文件名
            $url = storage_path().'/app/excel/'.$filename;
            if(!file_exists($url)){
                fopen($url,'w');
            }

            $path = file_put_contents($url,$files);
            if($path){
                event(new InfoExcel($url,$order_id,$user_id));
                $filename=$this->qiniuStore($filename);
                DB::table('common_excel')->where('id',$order_id)->update(['url' => $filename]);
                return response(['name'=>$filename,'url'=>$url],200);
            }
        }

    }


}
