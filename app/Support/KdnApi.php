<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2019/2/13
 * Time: 下午2:17
 */

namespace App\Support;


use App\Models\Express\ExpressOrder;
use App\Models\User\UserCompany;
use App\Support\SupportClass\Client;
use App\Support\SupportClass\ExportOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


class KdnApi
{

    private const PARTNER_ID = 'DML';

    private const QUERYTRACE = 'querytrace';  //路由信息查询

    private const REGISTERRORDER = 'registerorder'; //订单注册

    private const PUSHTRACE = 'pushtrace';  //路由信息推送

    private const PUSHORDERSTATE = 'pushorderstate'; //订单状态推送

    private const AGENTPOINTRACE = 'agentpointtrace'; //末端路由下发

    private const AGENTPUSHTRACE = 'agentpushtrace'; //末端路由推送

    private const CREATEORDER = 'createorder';  //预约取件

    private const CANCLEORDER = 'cancleorder'; //取消订单

    private const FORMAT = 'json';

    private const KEY = 'dmlkdnsecretkey';

    private const ENCRYPT = 'url';

    private const VERSION = '1.0';

    private const TEST_URL_ROUTER = 'http://183.62.170.46:40030/klop/gateway/pushTrace.json';

    private const PUSH_STATUS = 'http://connector.kdniao.com/api/status/klop ';

    private const API_URL_ROUTER = 'http://connector.kdniao.com/api/receiver/klop';

    private const STATUS_ABNORMAL = -10;

    public $ResultCode = '';

    public $Success = '';

    public $ParentId = self::PARTNER_ID;

    public $Reason = '';



    public function getSign($data = '', Array $other = [], $transfer = false)
    {
        if ($transfer == true) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $data = json_encode($data);
        }


        $sign = "data={$data}encrypt=" . $other['encrypt'] . "format=" . $other['format'] . "method={$other['method']}partnerid=" . $other['partnerid'] . "timestamp={$other['timestamp']}version=" . $other['version'] . "" . self::KEY . "";

        Log::debug('KDN签名数据：' . $sign);

        return (strtolower(base64_encode(md5($sign, true))));
    }

    /**
     * 路由信息查询
     * @param $info
     * @param $urlData
     * @return $this
     */
    public function queryTrace($info, $urlData)
    {
        setCurrentSiteId(0);
        $sign = $this->getSign($info, $urlData);

        if ($this->checkSign($sign, $urlData['sign']) === false) {
            return $this;
        }

        if (empty($info['WaybillCode'])) {
            $this->ResultCode = '421';
            $this->Success = false;
            $this->Reason = '缺少参数WaybillCode';
            return $this;
        }

        $order = ExpressOrder::withoutGlobalScope('site')->with(['routers'])
            ->where('express_number', $info['WaybillCode'])
            ->first();

        $routerInfos = $this->getRouterInfo($order);

        if ($routerInfos === false) {
            return $this;
        }
        $this->Traces = $routerInfos;
        $this->Success = true;
        $this->ResultCode = 200;
        return $this;

    }

    /**
     * 推送路由信息
     * @param null $WaybillCode
     * @return $this|bool|string
     */
    public function pushTrace(ExpressOrder $order = null)
    {

        $client = new Client();

        //$order='DML15512483799944';

        $signData = $this->getRouterInfo($order);
        if ($signData === false) {
            return false;
        }
        $urlInfo = [
            'method' => self::PUSHTRACE,
            'partnerid' => self::PARTNER_ID,
            'timestamp' => strtotime(now()),
            'format' => self::FORMAT,
            'encrypt' => self::ENCRYPT,
            'version' => self::VERSION,
        ];
        $sign = $this->getSign($signData, $urlInfo, true);
        Log::debug('KDN发送签名为：' . $sign);
        $postData = urlencode((json_encode($signData, JSON_UNESCAPED_UNICODE)));
        $urlInfo['sign'] = $sign;
        $urlString = $this->buildUrlParams($urlInfo);
        Log::debug('KDN发送json为：' . json_encode($signData));
        Log::debug('KDN post数据通过url加密后的报文数据：' . $postData);
        // $url = http_build_query($url);
        $url = self::API_URL_ROUTER . '?' . $urlString;
        Log::debug('KDN发送url为：' . $url);
        $client->setUrl($url);
        $client->setMethod('post');
        $client->setData($postData);
        $res = $client->sendKdnMessage();
        return $res;
    }


    /**
     * 订单注册(暂未开发)
     * @param $info
     * @param $urlData
     * @return $this
     */

    public function registerOrder($info, $urlData)
    {

        $sign = $this->getSign($info, $urlData);
        if ($this->checkSign($sign, $urlData['sign']) === false) {
            return $this;
        }

        if (empty($info['WaybillCode'])) {
            $this->ResultCode = '421';
            $this->Success = false;
            $this->Reason = '缺少参数WaybillCode';
            return $this;
        }

        $orders = ExpressOrder::withoutGlobalScope('site')->with(['routers'])
            ->where('id', $info['WaybillCode'])->first();

        $exportOrder = new ExportOrder();

        $data = ("{\"OrderCode\":\"KDN012657018199\",\"WaybillCode\":\"8565322233232\",\"PayType\": 1,\"MonthCode\": \"7553045845\",\"ExpType\": 1,\"Cost\": 1.0,\"OtherCost\": 1.0,\"Sender\": {\"Company\": \"LV\",\"Name\": \"Taylor\",\"Mobile\": \"15018442396\",\"ProvinceName\": \"上海 \",\"CityName\": \"上海\",\"ExpAreaName\": \"青浦区\",\"Address\": \"明珠路\"},\"Receiver\": {\"Company\": \"GCCUI\",\"Name\": \"Yann\",\"Mobile\": \"15018442396\",\"ProvinceName\": \"北京 \",\"CityName\": \"北京\",\"ExpAreaName\": \"朝阳区\",\"Address\": \"三里屯街道 \"},\"Commodity\": [{\"GoodsName\": \"鞋子\",\"Goodsquantity\": 1,\"GoodsWeight\": 1.0}],\"AddService\": [{\"Name\": \"COD\",\"Value\": \"1020\"}],\"Weight\": 1.0,\"Quantity\": 1,\"Volume\": 0.0,\"CallBack\":\"59357994-a5a8-43ba-9fc4-d77c3c6c312a\",\"Remark\": \"小 心轻放\"}");
        $data = (json_decode($data, true));

        $integrityOrder = [
            'other_number' => $data['WaybillCode'],
            'pick_name' => @$data['Sender']['Name'] ? $data['Sender']['Name'] : null,
            'pick_phone' => @$data['Sender']['Mobile'] ? $data['Sender']['Mobile'] : null,
            'pick_address' => @$data['Sender']['ProvinceName'] . @$data['Sender']['CityName'] . @$data['Sender']['ExpAreaName'] . @$data['Sender']['Address'],
            'delivery_name' => @$data['Receiver']['Name'] ? $data['Receiver']['Name'] : null,
            'delivery_phone' => @$data['Receiver']['Mobile'] ? $data['Receiver']['Mobile'] : null,
            'delivery_address' => @$data['Receiver']['ProvinceName'] . @$data['Receiver']['CityName'] . @$data['Receiver']['ExpAreaName'] . @$data['Receiver']['Address'],
            'weight' => @$data['Weight'] * 0.001,
            'pay_type' => $data['cod'] > 0 ? 6 : 100,
            'signing_type' => $data['cod'] > 0 ? 7 : 4,
            'collection_price' => $data['cod'],
            'number' => $data['number'],  //子单数量
            'from' => 'other-kdn',//来源第三方微特派
            'product_comment' => $data['Remark']
        ];
        $this->Traces = '';
        $this->Reason = '没有找到此运单';
        $this->Success = false;
        $this->ResultCode = 200;


        return $this;
    }

    /**
     *
     * 推送路由状态
     */
    public function pushOrderState(ExpressOrder $order = null)
    {

        $client = new Client();
        // $number = 'DML15512483799944';
        // $order = ExpressOrder::query()->where('express_number', $number)->first();
        if (!$order) {
            Log::debug('KDN:没有订单信息');
            return false;
        } else {
            $signData = [
                [
                    'WaybillCode' => $order->express_number,
                    'OrderState' => $this->buildOrderState($order->status),
                    'OperateDate' => date('Y-m-d H:i:s', strtotime(json_decode(json_encode($order->created_at))->date)),
                    'OperateDesc' => '无',
                ]
            ];

            $urlInfo = [
                'method' => self::PUSHORDERSTATE,
                'partnerid' => self::PARTNER_ID,
                'timestamp' => strtotime(now()),
                'format' => self::FORMAT,
                'encrypt' => self::ENCRYPT,
                'version' => self::VERSION,
            ];

            $sign = $this->getSign($signData, $urlInfo, true);
            $postData = urlencode((json_encode($signData, JSON_UNESCAPED_UNICODE)));
            $urlInfo['sign'] = $sign;
            $urlString = $this->buildUrlParams($urlInfo);
            $url = self::PUSH_STATUS . '?' . $urlString;
           // $url = 'http://183.62.170.46:40030/klop/gateway/pushOrderState.json' . '?' . $urlString;
            $client->setUrl($url);
            $client->setMethod('post');
            $client->setData($postData);
            $res = $client->sendKdnMessage();
            return $res;
        }
    }


    public function localIntegrityQueryTrace()
    {

        $client = new Client();
        /*  $signData = [
              'OrderCode' => '10224738',
              'WaybillCode'=>'DML15512483799944',
          ];*/
        $data = "{\"Commodity\":[{\"GoodsName\":\"鞋子\",\"GoodsPrice\":10,\"GoodsVol\":0,\"GoodsWeight\":1,\"Goodsquantity\":1}],\"Cost\":0,\"ExpType\":1,\"OrderCode\":\"KDN20171fgfdgfdg\",\"OtherCost\":0,\"PayType\":1,\"Quantity\":1,\"Receiver\":{\"Address\":\"天安门\",\"CityName\":\"北京市\",\"ExpAreaName\":\"西城区\",\"Mobile\":\"010-82199588\",\"Name\":\"李四\",\"ProvinceName\":\"北京\"},\"Remark\":\"kdn测试不要取件\",\"Sender\":{\"Address\":\"朝阳公园\",\"CityName\":\"北京市\",\"ExpAreaName\":\"朝阳区\",\"Mobile\":\"010-82199588\",\"Name\":\"发件人张三\",\"ProvinceName\":\"北京\"},\"Volume\":1,\"Weight\":1}";
        $signData = json_decode($data, true);

        $urlInfo = [
            'method' => self::CREATEORDER,//self::PUSHTRACE,
            'partnerid' => self::PARTNER_ID,
            'timestamp' => strtotime(now()),
            'format' => self::FORMAT,
            'encrypt' => self::ENCRYPT,
            'version' => self::VERSION,
        ];
        $sign = $this->getSign($signData, $urlInfo, true);

        $postData = urlencode((json_encode($signData, JSON_UNESCAPED_UNICODE)));

        Log::debug('KDN post数据通过url加密后的报文数据：' . $postData);
        $urlInfo['sign'] = $sign;
        $urlString = '';
        foreach ($urlInfo as $k => $item) {
            if ($k == 'method') {
                $urlString = $urlString . $k . '=' . $item;
            } else {
                $urlString = $urlString . '&' . $k . '=' . $item;
            }
        }
        $url = 'http://localhost/api/kdn' . '?' . $urlString;

        $client->setUrl($url);
        $client->setMethod('post');
        $client->setData($postData);
        $res = $client->sendKdnMessage();
        dd($res);
    }

    /**
     * 下单
     * @param $info
     * @param $urlData
     * @return $this
     */
    public function createOrder($info, $urlData)
    {
        $checkUser = $this->setUser();
        if ($checkUser === false) {
            return $this;
        }

       $sign = $this->getSign($info, $urlData, true);

        if ($this->checkSign($sign, $urlData['sign']) === false) {
            $this->msg1 = '接收到的post消息' . json_encode($info);
            $this->msg2 = '接收到的url参数' . json_encode($urlData);
            return $this;
        }

        if (empty($info['OrderCode'])) {
            $this->ResultCode = '421';
            $this->Success = false;
            return $this;
        }

   /*    $data = "{\"Commodity\":[{\"GoodsName\":\"鞋子\",\"GoodsPrice\":0,\"GoodsVol\":0,\"GoodsWeight\":1,\"Goodsquantity\":1}],\"Cost\":0,\"ExpType\":1,\"OrderCode\":\"KDN20171fgfdgfdg\",\"OtherCost\":0,\"PayType\":1,\"Quantity\":1,\"Receiver\":{\"Address\":\"天安门\",\"CityName\":\"北京市\",\"ExpAreaName\":\"西城区\",\"Mobile\":\"010-82199588\",\"Name\":\"收件人李四\",\"ProvinceName\":\"北京\"},\"Remark\":\"kdn测试不要取件\",\"Sender\":{\"Address\":\"朝阳公园\",\"CityName\":\"北京市\",\"ExpAreaName\":\"朝阳区\",\"Mobile\":\"010-82199588\",\"Name\":\"发件人张三\",\"ProvinceName\":\"北京\"},\"AddService\":[{\"Name\":\"COD\"}],\"Volume\":1,\"Weight\":1}";
        $data="{\"Commodity\":[{\"GoodsName\":\"鞋子\",\"GoodsPrice\":0,\"GoodsVol\":0,\"GoodsWeight\":1,\"Goodsquantity\":1}],\"Cost\":0,\"ExpType\":1,\"OrderCode\":\"KDN20171fgfdgfdg\",\"OtherCost\":0,\"PayType\":1,\"Quantity\":1,\"Receiver\":{\"Address\":\"天安门\",\"CityName\":\"北京市\",\"ExpAreaName\":\"西城区\",\"Mobile\":\"010-82199588\",\"Name\":\"李四\",\"ProvinceName\":\"北京\"},\"Remark\":\"kdn测试不要取件\",\"Sender\":{\"Address\":\"朝阳公园\",\"CityName\":\"北京市\",\"ExpAreaName\":\"朝阳区\",\"Mobile\":\"010-82199588\",\"Name\":\"测试张三\",\"ProvinceName\":\"北京\"},\"Volume\":1,\"Weight\":1}";
        $info = (json_decode($data, true));*/

        if (@$info['Sender']['CityName'] == '深圳' || @$info['Sender']['CityName'] == '深圳市') {
            setCurrentSiteId(3);
        } elseif (@$info['Sender']['CityName'] == '上海' || @$info['Sender']['CityName'] == '上海市') {
            setCurrentSiteId(2);
        } else {
            setCurrentSiteId(1);
        }

        if (@$info['Commodity']) {
            $cost = 0;
            foreach ($info['Commodity'] as $value) {

                if (!@$value['GoodsPrice']) {
                    $value['GoodsPrice'] = 0;
                }

                $cost = $cost + $value['GoodsPrice'];
            }

        }

        $integrityOrder = [
            'other_number' => $info['OrderCode'],
            'pick_name' => @$info['Sender']['Name'] ? $info['Sender']['Name'] : null,
            'pick_phone' => @$info['Sender']['Mobile'] ? $info['Sender']['Mobile'] : $info['Sender']['Tel'],
            'pick_address' => @$info['Sender']['ProvinceName'] . @$info['Sender']['CityName'] . @$info['Sender']['ExpAreaName'] . @$info['Sender']['Address'],
            'delivery_name' => @$info['Receiver']['Name'] ? $info['Receiver']['Name'] : null,
            'delivery_phone' => @$info['Receiver']['Mobile'] ? $info['Receiver']['Mobile'] : $info['Sender']['Tel'],
            'delivery_address' => @$info['Receiver']['ProvinceName'] . @$info['Receiver']['CityName'] . @$info['Receiver']['ExpAreaName'] . @$info['Receiver']['Address'],
            'weight' => @$info['Weight'] * 0.001,
            'pay_type' => (@$info['AddService'][0]['Name'] == 'COD') ?6 : @$this->buildPayType(@$info['PayType'])??100,
            'signing_type' => @$info['AddService'][0]['Name'] == 'COD' ? 7 : 4,
            'collection_price' => @$cost ?? 0,
            'from' => 'other-kdn',//来源第三方KDN
            'product_comment' => @$info['Commodity']['GoodsDesc'] ?? null,
            'comment' =>@$info['Remark'] ??null
        ];


        try {
            $result = (new ExpressOrder())->order($integrityOrder);

        } catch (\Exception $e) {
            $this->Reason = $e->getMessage();
            $this->Success = false;
            $this->ResultCode = 412;
            return $this;
        }
        if ($result) {
            try {
                $orders = $this->pushTrace($result);
                if (@$orders) {
                    Log::debug('KDN推送路由：成功');
                }
            } catch (\Exception $e) {
                Log::debug('KDN推送路由：失败');
            }
            $this->Reason = '下单成功';
            $this->Success = true;
            $this->ResultCode = 200;

        } else {
            $this->Reason = '服务器不稳定，请再次下单';
            $this->Success = false;
            $this->ResultCode = 500;
        }

        return $this;
    }

    /**
     * 取消订单
     * @param $info
     * @param $urlData
     * @return $this\
     */
    function cancleOrder($info, $urlData)
    {

        setCurrentSiteId(0);
        $checkUser = $this->setUser();
        if ($checkUser === false) {
            return $this;
        }
        $sign = $this->getSign($info, $urlData);

        if ($this->checkSign($sign, $urlData['sign']) === false) {
            return $this;
        }

        if (empty($info['OrderCode'])) {
            $this->ResultCode = '421';
            $this->Success = false;
            $this->Reason = '缺少参数OrderCode';
            return $this;
        }

        try {
            $result = (new ExpressOrder())->cancelOrder($info['OrderCode'], '', 'admin');//取消订单成功退款

        } catch (\Exception $e) {
            $this->Reason = '订单取消失败';
            $this->Success = false;
            $this->ResultCode = 404;
            return $this;
        }
        if ($result) {
            $this->Reason = '订单取消成功';
            $this->Success = true;
            $this->ResultCode = 200;
        } else {
            $this->Reason = '订单取消失败';
            $this->Success = false;
            $this->ResultCode = 404;
        }
        return $this;
    }


    function getRouterInfo($order = null)
    {

        if ($order instanceof ExpressOrder && $order) {
            $result = $order;
        } elseif ($order) {
            $record = ExpressOrder::withoutGlobalScope('site')->with(['routers'])
                ->where('express_number', $order)
                ->first();
            if ($record) {
                $result = $record;
            } else {
                $this->Traces = '';
                $this->Reason = '没有找到此运单';
                $this->Success = false;
                $this->ResultCode = 200;
                return false;
            }
        } else {
            $this->Traces = '';
            $this->Reason = '没有找到此运单';
            $this->Success = false;
            $this->ResultCode = 200;
            return false;
        }

        foreach ($result->routers as $k => $data) {
            $signData[] = [
                'OrderCode' => $data->id,
                'WaybillCode' => $data->express_number,
                'ScanType' => $this->buildScanType($data->type),
                'ScanDate' => date('Y-m-d H:i:s', strtotime(json_decode(json_encode($data->created_at))->date)),
                'TraceDesc' => $data->comment,
                'ScanMan' => $data->name,
                'ScanManPhone' => $data->phone,
                'Location' => $this->buildSite($data->site),
                'ScanSite' => $this->buildSite($data->site),
                'ScanSiteCode' => $data->site,
            ];
        }
        return $signData;

    }


    function buildSite($site)
    {
        $arr = [
            1 => '北京',
            2 => '上海',
            3 => '深圳',
            4 => '成都',
        ];
        return $arr[$site];

    }

    function buildPayType($type)
    {
        //$type为KDN传递的type  1 :寄付 2到付 3 月结 4 第三方付
        //'pay_type' => ['name' => "支付类型 -5=>'月结',-1=>'信用支付',1=>'微信支付',2=>'支付宝支付',3=>'银联支付',4=>'红包付',6=>'到付',7=>'后台支付',100=>'余额'"],
        //'signing_type' => ["name'=>'签收方式 1=>'扫码确认',2=>'验证码',3=>'发送短信',4=>'拨打电话',5=>'拍照确认',6=>'电子签名',7=>'收款'"],
        $arr = [
            1 => 100,
            2 => 6,
            3 => -5,
            4 => 7,
        ];
        return $arr[$type];
    }

    function buildOrderState($state)
    {
        $arr = [
            -11 => 'UNACCEPT',
            -12 => 'UNACCEPT',
            -20 => 'UNACCEPT',
            -30 => 'UNACCEPT',
            -13 => 'UNACCEPT',
            -1 => 'UNACCEPT',
            0 => 'ACCEPT',
            5 => 'TRANSIT',
            10 => 'ACCEPT',
            20 => 'ACCEPT',
            30 => 'GOT',
            40 => 'GOT',
            45 => 'GOT',
            47 => 'GOT',
            50 => 'TRANSPORT',
            55 => 'TRANSPORT',
            60 => 'TRANSPORT',
            65 => 'TRANSPORT',
            67 => 'TRANSPORT',
            68 => 'TRANSPORT',
            70 => 'TRANSPORT',
            90 => 'TRANSPORT',
            92 => 'TRANSPORT',
            94 => 'TRANSPORT',
            96 => 'TRANSPORT',
            98 => 'SIGNED',
            100 => 'SIGNED',
        ];
        return $arr[$state];
    }

    function buildScanType($state)
    {
        $arr = [
            -11 => 'FAILED',
            -12 => 'FAILED',
            -20 => 'FAILED',
            -30 => 'FAILED',
            -13 => 'FAILED',
            -1 => 'FAILED',
            0 => 'PRIORA',
            5 => 'PRIORA',
            10 => 'PRIORA',
            20 => 'PRIORA',
            30 => 'GOT',
            40 => 'GOT',
            45 => 'GOT',
            47 => 'DEPARTURE',
            50 => 'DEPARTURE',
            55 => 'DEPARTURE',
            60 => 'DEPARTURE',
            65 => 'DEPARTURE',
            67 => 'DEPARTURE',
            68 => 'DEPARTURE',
            70 => 'DEPARTURE',
            90 => 'DEPARTURE',
            92 => 'DEPARTURE',
            94 => 'DEPARTURE',
            96 => 'SIGNED',
            98 => 'SIGNED',
            100 => 'SIGNED',
        ];
        return $arr[$state];
    }

    function buildUrlParams($urlInfo)
    {
        $urlString = '';
        foreach ($urlInfo as $k => $item) {
            if ($k == 'method') {
                $urlString = $urlString . $k . '=' . $item;
            } else {
                $urlString = $urlString . '&' . $k . '=' . $item;
            }
        }
        return $urlString;
    }

    function object2array(&$object)
    {
        $object = json_decode(json_encode($object), true);
        return $object;
    }

    function checkSign($mysign, $othersign)
    {
        Log::debug('dml的签名：' . $mysign);
        Log::debug('kdn的签名：' . str_replace(" ", "+", $othersign));
        if ($mysign != str_replace(" ", "+", $othersign)) {
            $this->ResultCode = '420';
            $this->Success = false;
            $this->Reason = 'kdn签名为' . $othersign . '----' . 'dml签名为' . $mysign;
            return false;
        } else {
            return true;
        }
    }

    function getUrlQuery($array_query)
    {
        $tmp = array();
        foreach ($array_query as $k => $param) {
            $tmp[] = $k . '=' . $param;
        }
        $params = implode('&', $tmp);
        return $params;
    }

    function setUser()
    {
        $user = UserCompany::where('id', 10)->first();
        if (@$user) {
            Auth::loginUsingId($user->user_id);
        } else {
            $this->Reason = '快递鸟用户没有建立';
            $this->Success = false;
            $this->ResultCode = 404;
            return false;
        }

    }


}