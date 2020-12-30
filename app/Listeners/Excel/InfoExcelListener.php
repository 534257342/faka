<?php

namespace App\Listeners\Excel;



use App\Events\Excel\InfoExcel;
use App\Models\Common\CommonApply;
use App\Models\Common\CommonUser;

use App\Models\Express\ExpressInfo;
use App\Models\User\UserDriver;
use Illuminate\Foundation\PackageManifest;
use Rap2hpoutre\FastExcel\FastExcel;


Class InfoExcelListener
{


    public function handle(InfoExcel $event)
    {

         $path = $event->path;
         $collection = (new FastExcel)->import($path);
         if ($collection->isEmpty()) {
            apiError('无数据');
         }
         $count=count($collection);
         $this->countMoney($count,$event->applyId);  //扣钱等操作


        $data=[];
        foreach ($collection as  $k=>$value) {
         $data[$k]=[
             'phone'=>$value['md5'],
             'type'=>$value['type'],
             'channel'=>$value['channel'],
             'place'=>$value['provence'].$value['city'],
             'apply_id'=>$event->applyId,
             'created_at'=>date('Y-m-d ')
         ];
        }

       if( ExpressInfo::query()->insert($data)){
           return true;
       }else{
           apiError('插入失败');
       }
    }


    public  function countMoney($count,$applyId){
     $apply= CommonApply::query()->where('id',$applyId)->first();
        if(empty($apply)){
            apiError('该订单不存在');
        }
        $apply->today_send=$count;
        $apply->all_send=$apply->all_send+$count;
        $apply->save();
        $data=[
            'price'=>$count*0.1,
            'userid'=>$apply->user_id,
            'type'=>'buy',
        ];
        $commonUser=new CommonUser();
        $commonUser->changePrice($data);
    }


}