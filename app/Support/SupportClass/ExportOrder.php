<?php

namespace App\Support\SupportClass;

use Larfree\Libs\Schemas;
use App\Support\Traits\OssStore;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Database\Query\Builder;
use App\Models\Express\ExpressMission;
use Illuminate\Database\Eloquent\Collection;

class ExportOrder
{
    use OssStore;

    public function export(Builder $dataBuilder)
    {
        $info = Collection::make();
        $orders = $dataBuilder->get();
        foreach ($orders as $order){
            $info->add($this->buildAppendInfo($order));
        }
        $name = strtotime(now()) . '.order.xlsx';
        $this->exportOrderMore($info,$name);
        return $this->ossStore($name,'temp/excel/');
}

    public function exportOrder($data, $name, $type = 'normal')
    {
        return (new FastExcel($data))->export(storage_path('app/public/' . $name), function ($info) use ($type) {
            if ($type == 'detail') {
                return [
                    'id' => $info->id,
                    '运单号' => $info->express_number,
                    '叮咚码' => $info->full_dingdong,
                    '下单用户' => $info->user->name,
                    '下单用户电话' => $info->user->phone,
                    '寄件人姓名' => $info->pick_name,
                    '寄件人电话' => $info->pick_phone,
                    '寄件人地址' => $info->pick_address,
                    '收件人姓名' => $info->delivery_name,
                    '收件人电话' => $info->delivery_phone,
                    '收件人地址' => $info->delivery_address,
                    '预计取件时间' => $info->forecast_pickup_time,
                    '预计完成时间' => $info->forecast_complete_time,
                    '最新路由信息' => $info->routers->first()->comment ?? 'null',
                    '产品标题' => $info->product->title ?? 'null',
                    '产品重量' => $info->product->total_weight ?? 'null',
                    '订单创建时间' => $info->created_at,
                    '订单价格' => $info->price,
                    '是否母单' => $info->parent_id > 0 ? '否' : '是',
                    '状态' => $this->buildStatusMap($info->status),
                ];
            }
            return [
                'id' => $info->id,
                '运单号' => $info->express_number,
                '叮咚码' => $info->full_dingdong,
                '寄件人姓名' => $info->pick_name,
                '寄件人电话' => $info->pick_phone,
                '寄件人地址' => $info->pick_address,
                '收件人姓名' => $info->delivery_name,
                '收件人电话' => $info->delivery_phone,
                '收件人地址' => $info->delivery_address,
                '预计取件时间' => $info->forecast_pickup_time,
                '预计完成时间' => $info->forecast_complete_time,
            ];
        });

    }

    public function buildStatusMap($number)
    {
        $arr = [
            -11 => '用户取消',
            -12 => '系统取消',
            -20 => '已退款',
            -30 => '已赔付',
            -13 =>  '2.0用户取消',
            -1 => '地址异常',
            0 => '带处理',
            5 => '已打单',
            10 => '确认',
            20 => '已付款',
            30 => '待取件',
            40 => '取货中',
            45 => '取货完成',
            47 => '待接管/调度',
            50 => '取货返回中',
            55 => '取货返回完成',
            60 => '中转中',
            65 => '中站已取',
            67 => '中转接管',
            68 => '待接管/调度',
            70 => '中转已送',
            90 => '派件已取',
            92 => '派送中',
            94 => '转交第三方派送',
            96 => '第三方已取货',
            98 => '第三方派送已签收',
            100 => '完成',
        ];
        return $arr[$number];
    }

    public function exportOrderMore($data, $name)
    {
        $useId=getLoginUserID();
        $check=(in_array($useId,['4574','43001','32093']));

        return (new FastExcel($data))->export(storage_path('app/public/' . $name), function ($info) use($check) {
            return [
                'id' => $info->id,
                '叮咚码' => $info->full_dingdong,
                '运单号' => $info->express_number,
                '邀请者邀请码' => $info->invite_code,
                '产品类型' => $this->getProductType($info->type ?? 'null'),
                '下单用户' => $check==true?$info->user_name: mb_substr($info->pick_name, 0, 1) . @str_repeat('*', (mb_strlen($info->user_name) - 1)),
                '下单用户电话' =>$check==true? $info->user_phone:substr_replace($info->user_phone,'****',3,4),
                '发件人姓名' => $check==true?$info->pick_name: mb_substr($info->pick_name, 0, 1) . @str_repeat('*', (mb_strlen($info->pick_name) - 1)),
                '发件人电话' => $check==true? $info->pick_phone:substr_replace($info->pick_phone,'****',3,4),
                '发件人地址' => $check==true?$info->pick_address:$this->hidestr($info->pick_address,mb_strlen($info->pick_address)*0.3,mb_strlen($info->pick_address)*0.4),
                '发件人地址详情' => $info->pick_address_add,
                '收件人姓名' => $info->delivery_name,
                '收件人电话' => $info->delivery_phone,
                '收件人地址' => $info->delivery_address,
                '收件人地址详情' => $info->delivery_address_add,
                '发件区域' => $info->stat_name ?? null,
                '收件区域' => $info->end_name ?? null,
                '应付金额' => $info->shipping_price ?? null,
                '优惠金额' => $info->coupon->price ?? null,
                '实付金额' => $info->price ?? null,
                '预计取件时间' => $info->forecast_pickup_time ?? null,
                '预计完成时间' => $info->forecast_complete_time ?? null,
                '实际取货时间' => $info->pick_complete_time ?? null,
                '取货返回时间'=>$info->pick_up_back_at,
                '中转取货时间'=>$info->go_spot_pick_at,
                '中转返回时间'=>$info->go_spot_send_at,
                '派送取货时间'=>$info->send_to_pick_at,
                '配送完成时间' => $info->complete_time ?? null,
                '下单时间' => $info->created_at,
                '取件时间' => $info->complete_time,
                '产品标题' => $info->title ?? null,
                '快件状态' => $this->buildStatusMap($info->status),
                '属性'    => $info->append,
                '三方单号' => $info->other_number ?? null,
                '关联三方单号' => $info->related_code ?? null,
                '备注' => @$info->content ?? '',
                '备注类型'=> @$info->cType ?? '',
            ];

        });

    }



    public function getProductType($type)
    {
        if ($type === NULL) {
            return '暂无';
        }
        $schemas = Schemas::getSchemas('express.product');
        if (isset($type)) {
            if ($type > 9 || $type == 'null') {
                return '暂无';
            }
            $productType = $schemas['res']['option'][$type];
            if (!@$productType) {
                $productType = '暂无';
            }
        } else {
            $productType = '暂无';

        }
        return $productType;
    }

    /**
     * @param string $startTime
     * @param string $endTime
     * @param string|null $type
     * @param int|null $userId
     * @param array|null $status
     * @return Builder
     */
    public function getBuilder(string $startTime, string $endTime, string $type = null, int $userId = null, array $status = null ,bool $remark = null )
    {
        $query= DB::table('express_order as exp')
            ->leftJoin('express_area  as area1', 'exp.start_area', '=', 'area1.id')
            ->leftJoin('express_area as area2', 'exp.end_area', '=', 'area2.id')
            ->leftJoin('common_user as user', 'exp.user_id', '=', 'user.id')
            ->leftJoin('common_user as user2', 'user.invite_id', '=', 'user2.id')
            ->leftJoin('express_product as product', 'exp.product_id', '=', 'product.id');
           if ($remark != false) {
               $query->leftJoin('express_order_comment as eoc', 'eoc.order_id', '=', 'exp.id')
                   ->select('exp.id','exp.product_id as cid','exp.full_dingdong','exp.express_number','exp.pick_name','exp.pick_phone',
                       'exp.pick_address','exp.pick_address_add','exp.delivery_name','exp.delivery_phone','exp.delivery_address',
                       'exp.delivery_address_add','exp.shipping_price','exp.price','exp.created_at','exp.complete_time','exp.user_id',
                       'exp.forecast_pickup_time','exp.forecast_complete_time','exp.dispatch_status',
                       'exp.pick_complete_time','exp.complete_time','exp.spot_id','exp.fleet_id','exp.end_fleet_id',
                       'exp.status','exp.product_id','exp.pay_id','exp.start_area','exp.end_area','exp.coupon_id',
                       'area1.name as stat_name','area2.name as end_name','user.name as user_name','user.phone as user_phone',
                       'product.title','product.type','exp.pick_up_back_at','exp.go_spot_pick_at','exp.go_spot_send_at','exp.send_to_pick_at'
                       ,'user2.invite_code','eoc.content','eoc.type as cType'
                   );
           }else{
               $query->select('exp.id','exp.product_id as cid','exp.full_dingdong','exp.express_number','exp.pick_name','exp.pick_phone',
                   'exp.pick_address','exp.pick_address_add','exp.delivery_name','exp.delivery_phone','exp.delivery_address',
                   'exp.delivery_address_add','exp.shipping_price','exp.price','exp.created_at','exp.complete_time','exp.user_id',
                   'exp.forecast_pickup_time','exp.forecast_complete_time','exp.dispatch_status',
                   'exp.pick_complete_time','exp.complete_time','exp.spot_id','exp.fleet_id','exp.end_fleet_id',
                   'exp.status','exp.product_id','exp.pay_id','exp.start_area','exp.end_area','exp.coupon_id',
                   'area1.name as stat_name','area2.name as end_name','user.name as user_name','user.phone as user_phone',
                   'product.title','product.type','exp.pick_up_back_at','exp.go_spot_pick_at','exp.go_spot_send_at','exp.send_to_pick_at'
                   ,'user2.invite_code'
               );
           }

            $query->where('exp.site', getCurrentSiteId())->where('exp.forecast_complete_time', '<', $endTime)
            ->where(function ($query) use ($type){
                if($type=='single'){
                    $query->where('parent_id','0');
                }elseif($type=='children'){
                    $query->where('parent_id','>','0');
                }elseif ($type=='parent'){
                    $query->where('parent_id','-1');
                }
            })
            ->where(function ($query) use ($userId){
                if($userId){
                    $query->where('user.id',$userId);
                }
            })
            ->where(function ($query) use ($status){
                if(is_array($status)&& $status!=null){
                    $query->whereIn('exp.status',$status);
                }
            })
            ->where('exp.forecast_complete_time','>=',$startTime);
           return $query;
    }

    public  function checkDate($date){
        if(empty($date['start_time'])||empty($date['end_time'])){
            apiError('时间选择不正确');
        }
        if(strtotime($date['end_time'])<=strtotime($date['start_time'])){
            apiError('结束时间得大于开始时间');
        }
    }

    /**
     * @param $data
     * @param $name
     * @return string
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     * 商户端导出数据
     */
    public function exportMerchantOrder($data,$name){
        return (new FastExcel($data))->export(storage_path('app/public/'.$name), function ($order) {
            $pick_time = json_decode(json_encode($order->pick_complete_time));
            //如果没有取件时间,就是预计取件
            $pick_time = $pick_time??json_decode(json_encode($order->forecast_pickup_time));
            $pick_time_date = $pick_time?substr($pick_time,0,10):' ';
            $pick_time = $pick_time?substr($pick_time,11):' ';
            $complete_time = json_decode(json_encode($order->complete_time))??' ';
            $created_at = json_decode(json_encode($order->created_at));
            $type = $order->product->type;
            $comment = $order->comment;
            if($comment){
                $comment = $comment->toArray()[0]['content']??' ';
            }else{
                $comment =' ';
            }
            $product_comment = $order->product_comment;
            if($product_comment){
                $product_comment = $product_comment->content??' ';
            }else{
                $product_comment = ' ';
            }
            $this->getType($type);
            return [
                '第三方单号'=>$order->other_number,
                '寄件人姓名'=>$order->pick_name,
                '寄件人电话'=>$order->pick_phone,
                '寄件人地址'=>$order->pick_address.$order->pick_address_add,
                '收件人姓名'=>$order->delivery_name,
                '收件人电话'=>$order->delivery_phone,
                '收件人地址'=>$order->delivery_address.$order->delivery_address_add,
                '取件日期'=>$pick_time_date,
                '取件时间'=>$pick_time,
                '送达时间'=>$complete_time,
                '货物重量kg'=>$order->total_weight ?? '0',
                '产品规格'=>$product_comment,
                '配送备注'=>$comment,
                '保价价值'=>$order->insurance->price ?? '0',
                '订单状态'=>$this->buildStatusMap($order->status),
                '运单金额'=>$order->price ?? '0',
                '产品类型'=>$this->getType($type),
                '下单时间'=>substr($created_at->date,0,19),
                '识别码'=>$order->full_dingdong,

            ];

        });
    }


    public function getSignDriver($startDate = '',$endDate='',$type='sign')
    {
        $name = strtotime(now()) . '.driver.xlsx';
        if($type=='sign'){
            $data=$this->getDriverSignInfoByDate($startDate,$endDate);
            if($data->isEmpty()){
                return apiError('没有数据');
            }
            $data=collect($data);
            $this->exportDriverExcel($data,$name,$startDate);
            return $this->ossStore($name,'temp/excel/');
        }elseif ($type=='notsign'){
            $data=$this->getDriverNotSignInfoByDate($startDate,$endDate);
            if(!$data){
                return apiError('没有数据');
            }
            $this->exportDriverExcel($data,$name,$startDate,'notsign');
            return $this->ossStore($name,'temp/excel/');
        }

    }


    public function exportDriverExcel($data, $name ,$date,$type='sign')
    {
        return (new FastExcel($data))->export(storage_path('app/public/' . $name), function ($info) use ($date,$type) {
            if($type=='sign'){
                return [
                    '司机姓名' => $info->name??'',
                    '司机电话' => $info->phone ??'',
                    '签到时间' => $info->created_at??'',
                    '区域连队' => $info->title?? '',
                    '日期'    => $date ?? '',
                ];
            }elseif($type=='notsign'){
                return [
                    '司机姓名' => $info->name??'',
                    '司机电话' => $info->phone ??'',
                    '区域连队' => $info->title?? '',
                    '日期'    => $info->date ?? '',
                ];
            }

        });
    }


    public function getSignDriverID($startDate = '')
    {
        $endDate=date("Y-m-d",strtotime("$startDate+1 day"));
        $driver = DB::table('user_driver_sign')
            ->select('driver_id')->where(function ($query) use ($startDate, $endDate) {
                if ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $query->where('created_at', '<', $endDate);
                }
            })->get();
        $driver = json_decode(json_encode($driver), true);
        $driver = (iterator_to_array(
            new \RecursiveIteratorIterator(
                new \RecursiveArrayIterator($driver)
            ),
            false
        )
        );
        return ($driver);
    }


    public function  getDriverSignInfoByDate($startDate,$endDate){
        // $endDate=date("Y-m-d",strtotime("$startDate+1 day"));
        return DB::table('user_driver_sign as sign')
            ->Join('common_user as user', 'sign.driver_id', '=', 'user.id')
            ->Join('user_driver as driver', 'driver.user_id', '=', 'user.id')
            ->Join('express_fleet as fleet', 'driver.work_fleet_id', '=', 'fleet.id')
            ->select('user.id','user.name','user.phone','sign.created_at','driver.work_fleet_id','fleet.title')
            ->where('driver.site',getCurrentSiteId())
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate) {
                    $query->where('sign.created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $query->where('sign.created_at', '<', $endDate);
                }
            })->get();

    }


    public function  getDriverNotSignInfoByDate($startDate,$endDate){
        // $BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));
        // $endDate=( date('Y-m-d', strtotime("$BeginDate +1 month -1 day")));

        while(strtotime($startDate)!=strtotime($endDate))
        {

            $id=$this->getSignDriverID($startDate);
            $info=DB::table('common_user as user')
                ->Join('user_driver as driver', 'driver.user_id', '=', 'user.id')
                ->Join('express_fleet as fleet', 'driver.work_fleet_id', '=', 'fleet.id')
                ->select('user.name','user.phone','fleet.title')
                ->where('driver.site',getCurrentSiteId())
                ->whereNotIn('user.id', $id)->get();
            foreach ($info as $item){
                $item->date=$startDate;
                $notSignData[]=$item;
            }

            $startDate=date("Y-m-d",strtotime("$startDate+1 day"));
        }
        return $notSignData;

    }

    public function getType($type)
    {
        $schemas = Schemas::getSchemas('express.product');
        return $schemas['type']['option'][$type];
    }

    public  function  buildAppendInfo($order){
        if($order->status==30 && $order->dispatch_status==0){
            $order->append='待取件';
        }elseif($order->status=='100'){
            $order->append='完成';
        }elseif ($order->status==30 && $order->dispatch_status==1){
            $order->append='已取件';
        }elseif ($order->spot_id>0 && $order->fleet_id!=$order->end_fleet_id??'0'){
            $order->append='在取货站点';
        }
        elseif ($order->spot_id>0 && ($order->fleet_id!=$order->end_fleet_id??'0')){
            $order->append='在末端站点 ';
        }
        elseif ($order->status>45 && $order->status<70 &&  $order->spot_id==0){
            $order->append='在干线 ';
        }
        elseif ($order->status>=70){
            $order->append='末端配送中 ';
        } elseif($order->status<0){
            $order->append='取消';
        }else{
            $order->append='无';
        }
        return $order;
    }

    /**
     * 每日数据导出
     * @return mixed
     */
    public function  getEverydayReport(){
        $data['beijing']=$this->beijingOrderCount();
        $data['count']=$this->beijingshenzheng();
        $data['count']['shanghai']=$this->exportShanghaiOrder();
        $data['today']=$this->todaySend();
        return $data;
    }

    /**
     * 获取2.0的上海统计信息.
     */
    public  function  exportShanghaiOrder(){
        $sql="SELECT
  anum as 'express_number',
  onum as 'finish',
  nnum as 'not_finish'
FROM
  (
    (
      (
        (
          (
          SELECT
            count( 0 ) AS `anum` 
          FROM
            `dingdong`.`expresses` 
          WHERE
            (
              ( to_days( `dingdong`.`expresses`.`delivery_before` ) = to_days( now( ) )) 
              AND ( `dingdong`.`expresses`.`is_dingdong` = 1 ) 
              AND ( state not in (99,98))
              AND ( `dingdong`.`expresses`.`city_code` = '021' ) 
            ) 
          ) `a`
          JOIN (
          SELECT
            count( 0 ) AS `onum` 
          FROM
            `dingdong`.`expresses` 
          WHERE
            (
              ( `dingdong`.`expresses`.`is_dingdong` = 1 ) 
              AND ( to_days( `dingdong`.`expresses`.`delivery_before` ) = to_days( now( ) )) 
              AND ( `dingdong`.`expresses`.`state` = 100 ) 
              AND ( `dingdong`.`expresses`.`city_code` = '021' ) 
            ) 
          ) `o` 
        )
        JOIN (
        SELECT
          count( 0 ) AS `nnum` 
        FROM
          `dingdong`.`expresses` 
        WHERE
          (
            ( to_days( `dingdong`.`expresses`.`delivery_before` ) = to_days( now( ) )) 
            AND ( `dingdong`.`expresses`.`state` not in (99,98,100) ) 
            AND ( `dingdong`.`expresses`.`is_dingdong` = 1 ) 
            AND ( `dingdong`.`expresses`.`city_code` = '021' ) 
          ) 
        ) `n` 
      )
      )
    )";
        $data = DB::connection('dingdong')->select($sql);
        $data[0]->city='上海';
        return $data[0];
    }

    /**
     * 北京下单统计
     */
    public  function  beijingOrderCount(){
        $sql="SELECT
  case a.site
  WHEN 1 THEN '北京'
  WHEN 2 THEN '上海'
  when 3 THEN '深圳'
  ELSE '其它'
  END as 'city',
  IFNULL(a.num,0) as 'order',
  IFNULL(b.num,0) as 'today_send',
  IFNULL(c.num,0) as 'tomorrow_send',
  IFNULL(d.num,0) as 'other_send'
FROM
(
  SELECT
    site,
    count(*) as num
    FROM
    express_order
    WHERE
    `status` >= 0
    and TO_DAYS(created_at) = TO_DAYS(NOW())
    GROUP BY site
) as a
LEFT JOIN
(
  SELECT
    site,
    count(*) as num
    FROM
    express_order
    WHERE
    `status` >= 0
    and TO_DAYS(created_at) = TO_DAYS(NOW())
    and TO_DAYS(forecast_complete_time) = TO_DAYS(NOW())
    GROUP BY site
) as b
on a.site = b.site
LEFT JOIN
(
  SELECT
    site,
    count(*) as num
    FROM
    express_order
    WHERE
    `status` >= 0
    and TO_DAYS(created_at) = TO_DAYS(NOW())
    and TO_DAYS(forecast_complete_time) = TO_DAYS(NOW()) +1
    GROUP BY site
) as c
on a.site = c.site
LEFT JOIN
(
  SELECT
    site,
    count(*) as num
    FROM
    express_order
    WHERE
    `status` >= 0
    and TO_DAYS(created_at) = TO_DAYS(NOW())
    and TO_DAYS(forecast_complete_time) > TO_DAYS(NOW()) +1
    GROUP BY site
) as d
on a.site = d.site
GROUP BY a.site";
        $data= DB::select($sql);
        foreach ($data as $i){
            if($i->city=='北京'){
                $info=$i;
            }
        }
        if(!$info){
            return 0;
        }
        return $info;
    }

    /**
     * 北京深圳运单统计
     */
    public  function  beijingshenzheng(){
        $sql="SELECT
  a.city_code as 'city',
  a.num as 'express_number',
  b.num as 'finish',
  c.num as 'not_finish'
  FROM
(  
  SELECT
  CASE site
  WHEN 1 THEN '北京'
  WHEN 2 THEN '上海'
  when 3 THEN '深圳'
  else '其它城市'
  END as city_code,
  count(*) as num
  FROM
  express_order
  WHERE TO_DAYS(forecast_complete_time) = TO_DAYS(NOW())
  and `status` BETWEEN 0
AND 100
  GROUP BY site
) as a
LEFT JOIN 
(
  SELECT
  CASE site
  WHEN 1 THEN '北京'
  WHEN 2 THEN '上海'
  when 3 THEN '深圳'
  else '其它城市'
  END as city_code,
  count(*) as num
  FROM
  express_order
  WHERE TO_DAYS(forecast_complete_time) = TO_DAYS(NOW())
  and status = 100
  GROUP BY site
) as b
on a.city_code = b.city_code
LEFT JOIN
(
  SELECT
  CASE site
  WHEN 1 THEN '北京'
  WHEN 2 THEN '上海'
  when 3 THEN '深圳'
  else '其它城市'
  END as city_code,
  count(*) as num
  FROM
  express_order
  WHERE TO_DAYS(forecast_complete_time) = TO_DAYS(NOW())
  and status < 100 and status >=0
  GROUP BY site
) as c
on a.city_code = c.city_code";
        $data= DB::select($sql);
        foreach ($data as $item){
            if($item->city=='北京')
            {
                $info['beijing']=$item;
            }
            elseif($item->city=='深圳')
            {
                $info['shenzheng']=$item;
            }
        }
        return $info;
    }

    /**
     * 当日送达
     */
    public  function  todaySend(){
        $sql="SELECT
--   t0.znum as '当日达下单数',
  t1.znum as 'send'
--   t2.znum as '当日达未完成'
FROM
  (
    SELECT
      count(*) as znum
    FROM
    express_order
    WHERE 
    TO_DAYS(complete_time) = TO_DAYS(NOW())
    and site = 1
    AND status = 100
  )  as t0,
  (
    SELECT
      count(*) as znum
    FROM
    express_order
    WHERE 
    TO_DAYS(complete_time) = TO_DAYS(NOW())
    and site = 1
--     and status = 100
  ) as t1,
  (
    SELECT
      count(*) as znum
    FROM
    express_order
    WHERE 
    TO_DAYS(complete_time) = TO_DAYS(NOW())
    and site = 1
    AND complete_time is NULL
    AND status = 100
  ) as t2";
        $data= DB::select($sql);
        return ($data[0]->send);
    }

    /**
     * 统计司机运单进入数据库
     * @param $startDate
     */
    public  function driverOrderCount($startDate){
        $startDate='2019-01-21';
        setCurrentSiteId(2);
        $info=$this->getDriverSignInfoByDate($startDate);

        foreach ($info as $i=>$item){
            $data[$i]['name']=$item->name;
            $data[$i]['phone']=$item->phone;
            $data[$i]['title']=$item->title;
            $data[$i]['sign_time']=$item->created_at;
            $data[$i]['pick']=$this->countPickUpNumber($item->id,$startDate);
            $data[$i]['transfer']=$this->countTransferNumber($item->id,$startDate);
            $data[$i]['send']=$this->countSendNumber($item->id,$startDate);
        }
        dd($data);
    }


    public  function countPickUpNumber($driver_id,$startDate){


        $endDate=date("Y-m-d",strtotime("$startDate+1 day"));
        /*     ExpressRouter::where('user_id',$driver_id)->where('created_at','>',$startDate)
                 ->where('created_at','<'.$startDate)->get();*/

        $mission=ExpressMission::where("driver_id",$driver_id)->where('type','>=',0)
            ->get();
        dd($mission->toArray());
        $number=0;
        if($mission){
            foreach ($mission as $item){
                $check=ExpressMission::where("driver_id",$driver_id)->where('complete_time','>',$startDate)
                    ->where('complete_time','<',$endDate)->where('type',20)->where('mission_id',$item->mission_is)->first();
                if($check){
                    $number=$number+1;
                }
            }
        }
        return $number;
    }

    public function countTransferNumber($driver_id,$startDate){
        $endDate=date("Y-m-d",strtotime("$startDate+1 day"));
        $data=ExpressMission::where("driver_id",$driver_id)->where('complete_time','>',$startDate)
            ->where('complete_time','<',$endDate)
            ->where('type',10)->get();
        $number=0;
        if($data){
            foreach ($data as $item){
                $check=ExpressMission::where("driver_id",$driver_id)->where('complete_time','>',$startDate)
                    ->where('complete_time','<',$endDate)->where('type',20)->where('mission_id',$item->mission_is)->first();
                if($check){
                    $number=$number+1;
                }
            }
        }
        return $number;

    }

    public  function  countSendNumber($driver_id,$startDate){
        $endDate=date("Y-m-d",strtotime("$startDate+1 day"));
        $data=ExpressMission::where("driver_id",$driver_id)->where('complete_time','>',$startDate)
            ->where('complete_time','<',$endDate)
            ->where('type',30)->get();
        $number=0;
        if($data){
            foreach ($data as $item){
                $check=ExpressMission::where("driver_id",$driver_id)->where('complete_time','>',$startDate)
                    ->where('complete_time','<',$endDate)->where('type',40)->where('mission_id',$item->mission_is)->first();
                if($check){
                    $number=$number+1;
                }
            }
        }
        return $number;
    }


    public  function  exportDriverCount($data){
        $name = strtotime(now()) . '.driverCount.xlsx';
        $this->exportDriverCountExcel($data,$name);
        return $this->ossStore($name,'temp/excel/');
    }

    public function exportDriverCountExcel($data,$name)
    {
        return (new FastExcel($data))->export(storage_path('app/public/' . $name), function ($info)  {
            return [
                '司机姓名' => $info->name??'',
                '送件数' => $info->total_sends ??'',
                '取件数' => $info->total_gets??'',
            ];
        });
    }


    public  function  exportGanxian($data){
        $name = strtotime(now()) . '.Ganxian.xlsx';
        $this->exportGanxianExcel($data,$name);
        return $this->ossStore($name,'temp/excel/');
    }

    public function exportGanxianExcel($data,$name)
    {
        return (new FastExcel($data))->export(storage_path('app/public/' . $name), function ($info)  {
            return [
                '订单编号' => $info->id??'',
                '包裹重量' => $info->total_weight ??'',
                '订单始发点经度' => $info->pick_lat??'',
                '订单始发点纬度' => $info->pick_lon??'',
                '订单下单时间' => $info->created_at ??'',
                '订单经过的接驳点id' => $info->spot_id??'',
                '订单到达时间' => $info->arrive_time??'',
                '订单离开时间' => $info->mtime ??'',
                '订单终点经度' => $info->delivery_lat??'',
                '订单终点纬度' => $info->delivery_lon??'',
                '订单实际到达时间' => $info->complete_time ??'',
                '订单要求到达时间' => $info->forecast_complete_time??'',
            ];
        });
    }


    function hidestr($string, $start = 0, $length = 0, $re = '*') {

        $strarr = [];
        $mb_strlen = mb_strlen($string);
        while ($mb_strlen) {//循环把字符串变为数组
            $strarr[] = mb_substr($string, 0, 1, 'utf8');
            $string = mb_substr($string, 1, $mb_strlen, 'utf8');
            $mb_strlen = mb_strlen($string);
        }
        $strlen = count($strarr);
        $begin  = $start >= 0 ? $start : ($strlen - abs($start));
        $end    = $last   = $strlen - 1;
        if ($length > 0) {
            $end  = $begin + $length - 1;
        } elseif ($length < 0) {
            $end -= abs($length);
        }
        for ($i=$begin; $i<=$end; $i++) {
            $strarr[$i] = $re;
        }
        if ($begin >= $end || $begin >= $last || $end > $last) return false;
        return implode('', $strarr);
    }

    /**
     * @param $data
     * @param $name
     * @return string
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     * DB导出excel
     */
    public function exportBDExcel($data,$name){
        return (new FastExcel($data))->export(storage_path('app/public/' . $name), function ($info)  {
            return [
                'BD经理' => $info['name']??0,
                'BD电话' => $info['phone']??0,
                '本月注册' => $info['inviteUserCount'] ??0,
                '本月新增客户数' => $info['newAddUserCount']??0,
                '本月新增充值客户数' => $info['newUserRechargeCount']??0,
                '本月新增充值金额' => $info['newAddUserRechargePrice']??0,
                '本月消费金额' => $info['newAddUserCostPrice']??0,
                '本月优惠金额' => $info['newAddUserOfferPrice']??0,
                '本月实际单量' => $info['totalOrderCount']??0,
                '本月次日达单量' => $info['nextOrderCount']??0,
                '本月新增用户实际下单量' => $info['newUserOrderCount']??0,
                '每月充值金额' => $info['rechargePrice']??0,
            ];
        });
    }

    public function exportCCExcel($data,$name)
    {
        return (new FastExcel($data))->export(storage_path('app/public/' . $name), function ($info)  {
            return [
                'CC姓名' => $info['name']??'',
                '单量' => $info['count'] ??'',
                '日期' => $info['time']??'',
            ];
        });
    }

    public function exportUser($data,$name){
        return (new FastExcel($data))->export(storage_path('app/public/' . $name), function ($info)  {
            if($info['user']['created_at']){
                $created_at = json_decode(json_encode($info['created_at']))->date;
                $created_at = substr($created_at,0,19);
            }else{
                $created_at ='';
            }
            return [
                '商户ID' => $info['SID']??0,
                '商户名称' => $info['name']??'',
                '城市' => $info['site']??'',
                '注册时间' => $created_at??'',
                '月初余额' => $info['early_balance']??0,
                '1月份充值余额' => $info['recharge_price']??0,
                '1月份消费余额' => $info['cost_price']??0,
                '充值返现金额' => $info['recharge_back_price']??0,
                '管理端返现' => $info['admin_bank']??0,
                '本月实际下单量' => $info['order_count']??0,
                '月末余额' => $info['end_price']??0,
                'BD' => $info['BD']??'',
            ];
        });
    }


    public function exportAreaOrder($data,$name){
        return (new FastExcel($data))->export(storage_path('app/public/'.$name), function ($info)  {
            return [
                '商圈名称' => $info['name']??'',
                '日期' => date('Y-m-d'),
                '发单量' => $info['count']??0,
            ];
        });
    }


    public function exportCountUserOrder($data,$name)
    {
        return (new FastExcel($data))->export(storage_path('app/public/' . $name), function ($info)  {
            return [
                '用户id' => $info['id']??'',
                'BD经理' => $info['inviteName']??'',
                '注册时间' => $info['created_at']??'',
                '最后一次下单时间' => $info['last']??'',
                '商户姓名' => $info['name']??'',
                '商户电话' => $info['phone']??'',
                '邀请码' => $info['inviteCode']??'',
                '11月单量' => $info['11']??'',
                '12月单量' => $info['12']??'',
                '1月单量' => $info['1']??'',
                '2月单量' => $info['2']??'',
            ];
        });
    }

}
