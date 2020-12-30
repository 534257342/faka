<?php
/**
 * Created by PhpStorm.
 * User: xiao
 * Date: 2018/12/24
 * Time: 上午10:00
 */

namespace App\Http\Controllers\Admin\Common;


use App\Events\Excel\ImportExcel;
use App\Events\Excel\InfoExcel;
use App\Models\Common\CommonFile;
use App\Support\Traits\OssStore;
use Larfree\Controllers\ApisController as Controller;
use Illuminate\Http\Request;


class ExcelController extends Controller
{

    use  OssStore;
    public function uploadExcel(Request $request){
        if ($request->isMethod('post')) {

            $file = $request->file('fileUpload');
            // 文件是否上传成功
            if ($file->isValid()) {

                $size = $file->getSize();
                //这里可根据配置文件的设置，做得更灵活一点
                if($size > 300*1024*1024){
                    apiError('上传文件不能超过300M');
                }
                // 获取文件相关信息
                $originalName = $file->getClientOriginalName(); // 文件原名
                $ext = $file->getClientOriginalExtension();     // 扩展名
                $realPath = $file->getRealPath();   //临时文件的绝对路径
                $type = $file->getClientMimeType();     // image/jpeg

                // 上传文件
//                $filename = uniqid().'.'.$ext;
                $filename = $originalName;
                // 使用我们新建的uploads本地存储空间（目录）
                //这里的uploads是配置文件的名称
                $files = file_get_contents($realPath);
                $url = storage_path().'/excel/'.$filename;
                if(!file_exists($url)){
                    fopen($url,'w');
                }
                $path = file_put_contents($url,$files);
                if($path){
                    event(new ImportExcel($url,$originalName,1));
                    return '上传成功';
                }
            }
        }
    }

    public  function  uploadInfo(Request $request){
        if ($request->isMethod('post')) {

            $applyId=$request->applyId;
            $applyId=2;
            $file = $request->file('fileUpload');
           if ($file->isValid()) {
               $size = $file->getSize();
               //这里可根据配置文件的设置，做得更灵活一点
               if($size > 300*1024*1024){
                   apiError('上传文件不能超过300M');
               }

                $ext = $file->getClientOriginalExtension();
                $filetype = ['jpg', 'jpeg', 'gif', 'txt', 'xls','xlsx'];
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
                    event(new InfoExcel($url,$applyId));
                  //  Storage::disk('public')->delete($filename);
                    $info['path']=$url;

                    $res=[
                        'name'=>$filename,
                        'path'=>$url,
                    ];
                    CommonFile::query()->create($res);

                    return Response()->success($info,'上传成功');
                }
            }
        }
    }

}