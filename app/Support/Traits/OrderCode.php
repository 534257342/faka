<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2018/12/15
 * Time: 下午2:17
 */
namespace App\Support\Traits;
use App\Models\Express\ExpressOrderCode;

trait OrderCode
{

    /**
     * 插入orderCode
     * @param $order
     * @throws \Larfree\Exceptions\ApiException
     */
    public  function  InsertOrderCode($order){
        foreach ($order as $number) {
            $code_data[]=([
                'order_id' => $number->id,
                'related_code'=>$number->express_number.strtotime(now()),
                'related_at'=>date('Y-m-d H:i:s',time()),
                'user_id'=>getLoginUserID()??0,
                'comment'=>'运单打印',
                'type'=>3,
            ]);
        }
       $res= ExpressOrderCode::insert($code_data);
     if(!$res){
         return apiError('order_code插入失败');
     }
    }

}