<?php
/**
 * Created by PhpStorm.
 * User: KitCheng
 * Date: 2018/9/14
 * Time: 17:50
 */

namespace App\Support;


use App\Support\Traits\OssStore;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Larfree\Libs\Schemas;
use Milon\Barcode\Facades\DNS1DFacade;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 *
 * 提供运单打印服务
 * Class PicModule
 * @package App\Support
 */
class PicModule
{

    use  OssStore;
    protected $size = [
        'A' => '37',
        'B' => '22',
        'C' => '18',
    ];

    /**
     * 生成运单
     * @param int $type
     * @return mixed
     */
    public function makeWaybill($resource,$type)
    {




        $schemas = Schemas::getSchemas('express.product');
        $data = ($schemas['type']['option'][$resource->product->type]);

        //dd($resource->getAtptribute('product')->getAttribute('type'));

        $img = $this->chooseType($data,$type);
        if($type=='normal'){
            return $this->buildNormalWaybill($resource,$img);
        }else{
            return $this->buildTripletWaybill($resource,$img);
        }

    }


    public  function  buildNormalWaybill($resource,$img){
        $barCode = (DNS1DFacade::getBarcodePNG($resource->express_number, "C128", '3.3', '100'));
        $data = base64_encode(QrCode::format('png')->margin(0)->size(235)->generate($resource->express_number));

        $img->insert($data, 'bottom-right', 56, 97);
        $img->insert($barCode, 'top-right', 30, 30);
        $times = $resource->waybill_code_count + 1;
        $this->createText($img, '第' . $times . '次打印时间' . ' ' . date('Y-m-d H:i', time()), 30, 110, 25); //打印时间

        $pickup_time = strtotime($resource->forecast_pickup_time);
        $complete_time = strtotime($resource->forecast_complete_time);
        if ($pickup_time === null && $complete_time === null) {
            $this->createText($img, '暂无' . '~' . '暂无', 660, 170, 30);
        } elseif ($pickup_time === null && $complete_time != null) {
            $this->createText($img, '暂无' . '~' . date('H:i', $complete_time), 660, 170, 30);
        } elseif ($pickup_time != null && $complete_time === null) {
            $this->createText($img, date('m.d H:i', $pickup_time) . '~' . '暂无', 660, 170, 30);
        } else {
            $this->createText($img, date('m.d H:i', $pickup_time) . '~' . date('H:i', $complete_time), 660, 170, 30);
        }


        $this->createText($img, $resource->full_dingdong, 30, 190, 50);   //叮咚码
        if (mb_strlen($resource->delivery_name) >= 12) {
            $this->createText($img, str_limit($resource->delivery_name, 24), 100, 300, 28);   //收件人
            $this->createText($img, $resource->delivery_phone, 476, 303, 28);   //电话
        } else {
            $this->createText($img, $resource->delivery_name, 100, 300, 28); //收件人
            $this->createText($img, $resource->delivery_phone, 476, 303, 28);   //电话
        }


        if (mb_strlen($resource->pick_name) >= 12) {
            $this->createText($img, str_limit($resource->pick_name, 24), 100, 500, 26);   //寄件人
            $this->createText($img, $resource->pick_phone, 474, 502, 26);   //电话
        } else {
            $this->createText($img, $resource->pick_name, 100, 500, 28);   //寄件人
            $this->createText($img, $resource->pick_phone, 474, 502, 28);   //电话
        }

        $this->createSenderAddress($img, $resource->delivery_address);  //收件人地址

        $this->createLargeText($img, $resource->pick_address);

        if (isset($resource->order_goods_payment->total_price) && ($resource->order_goods_payment->total_price != null)) {
            $this->createText($img, '代收' . $resource->order_goods_payment->total_price . '元', 680, 330, 36);  // 代收
        }

        if (isset($resource->insurance->price)) {
            $this->createText($img, '保价' . $resource->insurance->price . '元', 680, 400, 36);  //保价
        }
        $this->createText($img, '计价重量' . $resource->total_weight . 'kg', 680, 470, 36);  //保价
        $res = [];

        foreach ($resource->comment as $k => $item) {
            $res['content'][] = $item->content;
        }

        if(@$resource->product_comment){
            $product = $resource->product_comment->content;
            $this->createLargeText($img, $product, 800);
        }

        if ($res != null) {
            if (@$res['content']) {
                $note = (implode("", $res['content']));
            }
            if (@$note) {
                $this->createLargeText($img, $note, 650);
            }
        }


        $name = strtotime(now()) . uniqid() . $resource->id . '.jpg';
        $img->save(storage_path("app/public/$name"));
        return $this->ossStore($name, 'temp/pic/');

        // return $img->response('jpg');


    }


    public  function  buildTripletWaybill($resource,$img){

        $schemas = Schemas::getSchemas('express.product');
        if(isset($resource->res)){
            $productType=$schemas['res']['option'][$resource->res];
            if(!@$productType){
                $productType='暂无';
            }
        }else{
            $productType='暂无';
        }

        $this->createText($img, $productType, 78, 906, 24); //物品类型
        $this->createText($img, $productType, 78, 1364, 24); //物品类型


        $barCode = (DNS1DFacade::getBarcodePNG($resource->express_number, "C128", '1.78', '54'));
        $data = base64_encode(QrCode::format('png')->margin(0)->size(115)->generate($resource->express_number));

        $img->insert($data, 'bottom-right', 85, 765);
        $img->insert($barCode, 'top-right', 30, 30); //派件联一维码
        $img->insert($barCode, 'top-right', 30, 720); //客户联一维码
        $img->insert($barCode, 'top-right', 30, 1035); //寄件联一维码
        $times = $resource->waybill_code_count + 1;
        $this->createText($img, '第' . $times . '次打印时间' . ' ' . date('Y-m-d H:i', time()), 30, 70, 14); //打印时间

        $pickup_time = strtotime($resource->forecast_pickup_time);
        $complete_time = strtotime($resource->forecast_complete_time);
        if ($pickup_time === null && $complete_time === null) {
            $this->createText($img, '暂无' . '~' . '暂无', 354, 130, 18);
        } elseif ($pickup_time === null && $complete_time != null) {
            $this->createText($img, '暂无' . '~' . date('H:i', $complete_time), 354, 130, 18);
        } elseif ($pickup_time != null && $complete_time === null) {
            $this->createText($img, date('m.d H:i', $pickup_time) . '~' . '暂无', 354, 130, 18);
        } else {
            $this->createText($img, date('m.d H:i', $pickup_time) . '~' . date('H:i', $complete_time), 354, 130, 18);
        }


        $this->createText($img, $resource->full_dingdong, 30, 100, 40);   //叮咚码
        if (mb_strlen($resource->delivery_name) >= 5) {
            $this->createText($img, str_limit($resource->delivery_name, 10), 60, 180, 22);   //收件人
            $this->createText($img, $resource->delivery_phone, 184, 184, 22);   //电话

            $this->createText($img, str_limit($resource->delivery_name, 18), 60, 1100, 22);   //寄件人 客户联
            $this->createText($img, $resource->delivery_phone, 300, 1100, 22);   //电话 客户联
        } else {
            $this->createText($img, $resource->delivery_name, 60, 180, 22); //收件人
            $this->createText($img, $resource->delivery_phone, 184, 184, 22);   //电话

            $this->createText($img, $resource->delivery_name, 60, 1100, 22); //收件人
            $this->createText($img, $resource->delivery_phone, 300, 1100, 22);   //电话
        }


        if (mb_strlen($resource->pick_name) >= 5) {
            $this->createText($img, str_limit($resource->pick_name, 10), 60, 340, 22);   //寄件人
            $this->createText($img, $resource->pick_phone, 184, 340, 22);   //电话

            $this->createText($img, str_limit($resource->pick_name, 18), 60, 800, 22);   //寄件人 客户联
            $this->createText($img, $resource->pick_phone, 300, 800, 22);   //电话 客户联

            $this->createText($img, str_limit($resource->pick_name, 18), 60, 1240, 22);   //寄件人 客户联
            $this->createText($img, $resource->pick_phone, 300, 1240, 22);   //电话 客户联

        } else {
            $this->createText($img, $resource->pick_name, 60, 340, 22);   //寄件人
            $this->createText($img, $resource->pick_phone, 184, 340, 22);   //电话

            $this->createText($img, $resource->pick_name, 60, 800, 22);   //寄件人 客户联
            $this->createText($img, $resource->pick_phone, 300, 800, 22);   //电话 客户联

            $this->createText($img, $resource->pick_name, 60, 1240, 22);   //寄件人 客户联
            $this->createText($img, $resource->pick_phone, 300, 1240, 22);   //电话 客户联
        }

        $this->createTripletSenderAddress($img, $resource->delivery_address.$resource->delivery_address_add);  //收件人地址
        $this->createTripletSenderAddress($img, $resource->delivery_address.$resource->delivery_address_add,1130); //收件人地址寄件联

        $this->createTripletPickerAddress($img, $resource->pick_address.$resource->pick_address_add);   //寄件人地址

        $this->createTripletPickerAddress($img, $resource->pick_address.$resource->pick_address_add,820); //客户联

        $this->createTripletPickerAddress($img, $resource->pick_address.$resource->pick_address_add,1270); //寄件联

        if (isset($resource->order_goods_payment->total_price) && ($resource->order_goods_payment->total_price != null)) {
            $this->createText($img, '代收' . $resource->order_goods_payment->total_price . '元', 350, 230, 25);  // 代收
        }


        if (isset($resource->insurance->price)) {
            $this->createText($img, '保价' . $resource->insurance->price . '元', 350, 270, 25);  //保价
        }

        if($resource->total_weight<10){
            $this->createText($img, '计价重量' . $resource->total_weight . 'kg', 350, 310, 24);
        }else{
            $this->createText($img, '计价重量' . $resource->total_weight . 'kg', 350, 310, 18);
        }

        $res = [];


        foreach ($resource->comment as $k => $item) {
            $res['content'][] = $item->content;
        }
        if(@$resource->product_comment){
            $product = $resource->product_comment->content;
            $this->createTripletPickerAddress($img, $product, 540);
        }


        if ($res != null) {
            if (@$res['content']) {
                $note = (implode("", $res['content']));
            }
            if (@$note) {
                $this->createTripletPickerAddress($img, $note, 420);
            }
        }

        $name = strtotime(now()) . uniqid() . $resource->id . '.jpg';
        $img->save(storage_path("app/public/$name"));
        return $this->ossStore($name, 'temp/pic/');

        //$img->save(public_path('1.png'));
        // return $img->response('jpg');
    }


    /**
     * 生成文字
     * @param $resource
     * @param $text
     * @param $left
     * @param $up
     * @param $size
     * @param string $color
     */

    public function createText($resource, $text, $left, $up, $size, $color = '#000000')
    {
        $resource->text($text, $left, $up, function ($font) use ($size, $color) {
            $font->file('font/weiruan.ttf');
            $font->size($size);
            $font->valign('top');
            $font->color($color);
        });

    }

    /**
     * 创建收件人信息
     * @param $resource
     * @param $text
     */
    public function createLargeText($resource, $text, $mid = 540)
    {
        //dd(mb_strlen($text));
        $size = $this->getFontSize(mb_strlen($text));
        if (mb_strlen($text) < 15) {
            $this->createText($resource, mb_substr($text, 0, 15), 100, $mid + 20, 30);
        }
        if (mb_strlen($text) >= 15 && mb_strlen($text) <= 30) {
            $this->createText($resource, mb_substr($text, 0, 17), 100, $mid, 32);
            $this->createText($resource, mb_substr($text, 17, 17), 100, $mid + 40, 32);
        }
        if (mb_strlen($text) > 30 && mb_strlen($text) <= 40) {
            $this->createText($resource, mb_substr($text, 0, 20), 100, $mid, 26);
            $this->createText($resource, mb_substr($text, 20, 20), 100, $mid + 40, 26);
        }

        if (mb_strlen($text) > 40 && mb_strlen($text) <= 60) {
            $this->createText($resource, mb_substr($text, 0, 26), 100, $mid, 21);
            $this->createText($resource, mb_substr($text, 26, 26), 100, $mid + 30, 21);
            $this->createText($resource, mb_substr($text, 52, 20), 100, $mid + 60, 21);
        }
        if (mb_strlen($text) > 60 && mb_strlen($text) <= 84) {
            $this->createText($resource, mb_substr($text, 0, 32), 100, $mid, 17);
            $this->createText($resource, mb_substr($text, 32, 32), 100, $mid + 30, 17);
            $this->createText($resource, mb_substr($text, 64, 32), 100, $mid + 60, 17);
        }

    }


    public function createTripletPickerAddress($resource, $text, $mid = 350)
    {
        if($mid>800){
            if (mb_strlen($text) < 20) {
                $this->createText($resource, mb_substr($text, 0, 24), 60, $mid + 18, 24);
            }
            if (mb_strlen($text) >= 20 && mb_strlen($text) <= 40) {
                $this->createText($resource, mb_substr($text, 0, 20), 60, $mid + 18, 22);
                $this->createText($resource, mb_substr($text, 20, 20), 60, $mid + 50, 22);
            }
            if (mb_strlen($text) > 40 ) {
                $this->createText($resource, mb_substr($text, 0, 20), 60, $mid + 18, 22);
                $this->createText($resource, str_limit(mb_substr($text, 20, 20),38), 60, $mid + 50, 22);
            }
        }else{
            if (mb_strlen($text) < 12) {
                $this->createText($resource, mb_substr($text, 0, 12), 60, $mid + 18, 24);
            }
            if (mb_strlen($text) >= 12 && mb_strlen($text) <= 24) {
                $this->createText($resource, mb_substr($text, 0, 12), 60, $mid + 18, 22);
                $this->createText($resource, mb_substr($text, 12, 12), 60, $mid + 40, 22);
            }
            if (mb_strlen($text) > 24 ) {
                $this->createText($resource, mb_substr($text, 0, 12), 60, $mid + 18, 22);
                $this->createText($resource, str_limit(mb_substr($text, 12, 12),22), 60, $mid + 40, 22);
            }
        }



    }


    /**
     * 创建寄件人信息
     * @param $resource
     * @param $text
     */
    public function createSenderAddress($resource, $text)
    {
        $size = $this->getFontSize(mb_strlen($text));
        if (mb_strlen($text) < 15) {
            $this->createText($resource, mb_substr($text, 0, 15), 100, 370, $size);
        }
        if (mb_strlen($text) >= 15 && mb_strlen($text) <= 30) {
            $this->createText($resource, mb_substr($text, 0, 15), 100, 370, $size);
            $this->createText($resource, mb_substr($text, 15, 15), 100, 420, $size);
        }
        if (mb_strlen($text) > 30 && mb_strlen($text) <= 60) {
            $this->createText($resource, mb_substr($text, 0, 24), 100, 360, $size);
            $this->createText($resource, mb_substr($text, 24, 24), 100, 410, $size);
            $this->createText($resource, mb_substr($text, 48, 24), 100, 460, $size);
        }
        if (mb_strlen($text) > 60 && mb_strlen($text) <= 84) {
            $this->createText($resource, mb_substr($text, 0, 28), 100, 370, $size);
            $this->createText($resource, mb_substr($text, 28, 28), 100, 410, $size);
            $this->createText($resource, mb_substr($text, 56, 28), 100, 450, $size);
        }
    }



    public function createTripletSenderAddress($resource, $text,$place=220)
    {

        if($place>800){
            if (mb_strlen($text) < 20) {
                $this->createText($resource, mb_substr($text, 0, 20), 60, $place , 22);
            }
            if (mb_strlen($text) >= 20 && mb_strlen($text) <= 40) {
                $this->createText($resource, mb_substr($text, 0, 20), 60, $place , 22);
                $this->createText($resource, mb_substr($text, 20, 20), 60, $place + 30, 22);
            }
            if (mb_strlen($text) > 40 ) {
                $this->createText($resource, mb_substr($text, 0, 20), 60, $place, 22);
                $this->createText($resource, mb_substr($text, 12, 20), 60, $place+30, 22);
                $this->createText($resource, str_limit(mb_substr($text, 24, 20),38), 60, $place+60, 22);
            }

        }else{
            if (mb_strlen($text) < 12) {
                $this->createText($resource, mb_substr($text, 0, 12), 60, $place, 22);
            }
            if (mb_strlen($text) >= 12 && mb_strlen($text) <= 24) {
                $this->createText($resource, mb_substr($text, 0, 12), 60, $place, 22);
                $this->createText($resource, mb_substr($text, 12, 12), 60, $place+30, 22);
            }
            if (mb_strlen($text) > 24 ) {
                $this->createText($resource, mb_substr($text, 0, 12), 60, $place, 22);
                $this->createText($resource, mb_substr($text, 12, 12), 60, $place+30, 22);
                $this->createText($resource, str_limit(mb_substr($text, 24, 12),22), 60, $place+60, 22);
            }

        }


    }
    /**
     * 获取文字大小
     */
    public function getFontSize($str)
    {
        if ($str > 0 && $str <= 30) $level = 'A';
        elseif ($str > 30 && $str <= 60) $level = 'B';
        elseif ($str > 60 && $str <= 90) $level = 'C';
        return $this->size[$level];
    }

    /**
     * 选择生成的运单类型
     * @param $type 1=>准时达 2=>次日达
     * @return mixed
     */
    public function chooseType($type,$product='nomal')
    {

        if($type=='准时达' && $product=='triplet'){
            $img = Image::make('pic/zsl.jpg')->resize(576, 1408);
            $this->createText($img, $type, 374, 174, 40);
        }elseif($type!='准时达' && $product=='triplet'){
            $img = Image::make('pic/crl.jpg')->resize(576, 1408);
            $this->createText($img, $type, 374, 174, 40,'#ffffff');
        }
        elseif ($type == '准时达' && $product=='normal') {
            $img = Image::make('pic/zhunshi.png')->resize(1000, 1000);
            $this->createText($img, $type, 670, 225, 60, '#ffffff');   //叮咚码
        } else {
            $img = Image::make('pic/ciri.png')->resize(1000, 1000);
            $this->createText($img, $type, 670, 225, 60, '#ffffff');   //叮咚码
        }

        return $img;
    }

    /**
     * 生成一维码
     * @param $other_number
     * @return mixed
     */
    public function buildBarCode($other_number){
        $img = Image::make('pic/wu.png')->resize(1000, 1000);
        $barCode=(DNS1DFacade::getBarcodePNG($other_number, "C128", '3.3', '100'));
        $img->insert($barCode, 'top-right', 30, 30);
        return $img->response('jpg');
    }

    /**
     * 创建html 文档流
     * @param $data array 图片地址
     * @return string
     */

    public function createDom($data)
    {
        foreach ($data as $k => $item) {
            $res['content'][] = "<page> <img src=$item ></page>";
        }
        $note = (implode("", $res['content']));
        //Start 构建html文档流模板
        $data = ('<style>*{margin:0;padding:0}
              img{ page-break-after: always; }
            </style>' . '<div>' . $note . '</div>');
        return $data;
    }



    /**
     * 创建pdf文件
     * @param $data =>dom流数据
     * @return string   =>pdf 路径
     */
    public function createPdf($data)
    {
        $pdf = App::make('dompdf.wrapper');
        $pdf->setPaper([0, 0, 750.00, 750.00], 'landscape')->loadHTML($data);  //生成pdf

        $name = date('Y-m-d', time()) . uniqid() . '.pdf';  //创建pdf名字
        $pdf->save(storage_path('app/public/' . $name));  //保存在本地的路径名字
        return $name;
    }




}