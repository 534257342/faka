<?php


namespace App\Support;


use App\Models\Express\ExpressOrder;
use App\Models\User\UserCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 *
 * 提供微特派第三方api接口对接
 * Class PicModule
 * @package App\Support
 */
class WtpApi
{


    protected $url = 'http://ditu.weitepai.com/wtperp/duijie/dj_vtpcomm.php';
    public $userid = '100001';
    public $key = '8f3001d7ec321c28b206104e06314191';
    protected $method = 'POST';
    public $city = 2;  //默认选择为上海   2 上海 1 北京
    protected $debug = false;


    protected $pick_phone = '18721551423';
    protected $pick_address = '上海市青浦区新协路1688号';
    protected $pick_name = '佘习丙';

    protected $pick_phone_beijng = '4001333518';
    protected $pick_address_beijing = '北京市通州区永乐店镇宇培物流园';
    protected $pick_name_beijing = '微特派';
    /**
     * 默认设置当前城市为上海
     * WtpApi constructor.
     */
    public function __construct()
    {
        if(config('wtp.debug')===true){
            $this->debug=config('wtp.debug');
        }
        if(config('wtp.debug')===false){
            $this->url=config('wtp.url');
            $this->debug=config('wtp.debug');
        }
        $this->setCity($this->city);
    }

    /**
     * 获取微特派id,并登陆
     */
    public function setUser()
    {
        if($this->city==1){
            $user = UserCompany::where('id', 11)->first();
        }else{
            $user = UserCompany::where('id', 6)->first();
        }

        if (@$user) {
            Auth::loginUsingId($user->user_id);
        } else {
            return apiError('微特派用户还未建立');
        }

    }


    /**
     *
     * 运单下载（CREATEORDER）
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrder()
    {
        $data = [
            'userid' => $this->userid,
            'msgtype' => 'CREATEORDER',
        ];
        $msgxml = $this->array2Xml($data);
        $client = new \GuzzleHttp\Client();
        $verifycode = $this->setVerifycode($data['msgtype'], $msgxml);
        $data['city'] = $this->city;
        $data['msgxml'] = $msgxml;
        $data['verifycode'] = $verifycode;
        $res = $client->request(
            $this->method,
            $this->url,
            [
                'form_params' => $data,
                'debug' => false,
            ]
        );
        $statusCode = $res->getStatusCode();
        if ($statusCode == 200) {
            $body = $res->getBody();
            $stringBody = (string)$body;
            return ($this->simplexml2Array($stringBody));
        } else {
            return false;
        }
    }


    /**
     * 运单下载反馈
     * @param String $wayBill 需要进行更新订单号
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */

    public function updateOrder(String $wayBill = '')
    {
        if ($this->debug) {
            print_r($wayBill);
            $wayBill = "D661021088344";  //debug下不反馈真实下载信息
        }
        $note = $this->buildWayBill($wayBill);
        $msgxml = $this->setXmlDom($note, 'UPDATEORDER');
        $result = $this->getXmlData('UPDATEORDER', $msgxml);
        return $result;
    }

    /**
     * 运单状态查询 (暂时不需要开发)
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryOrder($wayBill = '')
    {
        if ($this->debug) {
            $wayBill = "YG00010640268,123213213";
        }
        $note = $this->buildWayBill($wayBill);
        $msgxml = $this->setXmlDom($note, 'QUERYORDER');
        $result = $this->getXmlData('QUERYORDER', $msgxml);
        return $result;
    }

    /**
     * 运单跟踪反馈
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateTrack($info, $nextSite = false)
    {

        $note = $this->buildUpdate($info, $nextSite);
        $msgxml = $this->setXmlDom($note, 'UPDATETRACK');
        $result = $this->getXmlData('UPDATETRACK', $msgxml);
        return $result;

    }


    /**
     * 同步微特派订单到本地
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function buildLocalOrder()
    {   set_time_limit(0);
        $this->setUser();
        $data = $this->createOrder();

        if (!(@$data['records']['record'])) {
            return true;
        }
        $expOrder = new ExpressOrder();
        if (!isset($data['records']['record'][0])) {
            $item = $data['records']['record'];
            $mainOrder = $this->buildMainOrder($item);
            if (@$item['waybill']) {
                $wayBills = explode(';', $item['waybill']);
                if (isset($wayBills[0]) && $wayBills[0]) {
                    foreach ($wayBills as $i => $wayBill) {
                        $mainOrder['children'][$i]['other_number'] = $wayBill;
                    }
                }
            }
            $check = $expOrder->where('other_number', $item['mainwaybill'])->first();
            if (!$check) {
                try {
                    $res = $expOrder->order($mainOrder);
                    if (@$res) {
                        $this->updateOrder($item['mainwaybill']);

                    }
                } catch (\Exception $exception) {
                    $erroMessage = ('运单号' . $item['mainwaybill'] . $exception->getMessage());
                }
            } else {
                $exitMessage = $item['mainwaybill'] . '已经存在于数据库';
            }
        } else {
            foreach ($data['records']['record'] as $k => $item) {
                $mainOrder[$k] = $this->buildMainOrder($item);
                if (@$item['waybill']) {
                    $wayBills = explode(';', $item['waybill']);
                    if (isset($wayBills[0]) && $wayBills[0]) {
                        foreach ($wayBills as $i => $wayBill) {
                            $mainOrder[$k]['children'][$i]['other_number'] = $wayBill;
                        }
                    }
                }
                $check = $expOrder->where('other_number', $item['mainwaybill'])->first();
                if (!$check) {
                    try {
                        $res = $expOrder->order($mainOrder[$k]);
                        if (@$res) {
                            $this->updateOrder($item['mainwaybill']);
                        }
                    } catch (\Exception $exception) {
                        $erroMessage[] = ('运单号' . $item['mainwaybill'] . $exception->getMessage());
                    }
                } else {
                    $message['content'][] = $item['mainwaybill'];
                    $message['content'][] = $item['mainwaybill'];
                    if (@$message['content']) {
                        $exitMessage = (implode(",", $message['content']));
                    }
                }
            }

        }

        if (@$erroMessage) {
            if (is_array($erroMessage)) {
                $erroMessages = (implode(",", $erroMessage));
            } else {
                $erroMessages = $erroMessage;
            }
        } else {
            $erroMessages = [];
        }
        if (@$exitMessage) {
            $exitMessage = $exitMessage . '已经下载';
        } else {
            $exitMessage = [];
        }
        return $msg = [
            'exitMessage' => $exitMessage,
            'erroMessage' => $erroMessages,
        ];

    }


    /**
     * 更新路由状态
     * @param string $id
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Larfree\Exceptions\ApiException
     */
    function update_order_router(ExpressOrder $order)
    {
        $this->setCity(getCurrentSiteId());
        $data = $order;
        if ($parentID=$data->isChildOrder()){
            $myExp= ExpressOrder::select('id','other_number')->where('id',$parentID)->first();
            $otherNumber=$myExp->other_number;
        }else{
            $otherNumber=$data->other_number;
        }

        if (@$data->routers->first()) {
            $latestRoute = $data->routers->first();
            $info = $this->buildRouterInfo($data, $latestRoute,false,$otherNumber);
            if ($info = @$this->updateTrack($info)) {
                if ($data['status'] == 100) {
                    $res = $this->buildRouterInfo($data, $latestRoute, true,$otherNumber);
                    if ($info = @$this->updateTrack($res)) {
                        return $info['records']['record'];
                    }
                }
                return $info['records']['record'];
            }
            return false;
        } else {
            apiError('该订单没有路由信息');
        }
    }

    function update_error_order_router(ExpressOrder $order)
    {
        $this->setCity(getCurrentSiteId());
        $data = $order;
        if (@$data->routers->first()) {
            $latestRoute = $data->routers->first();
            $info = $this->buildErrorRouterInfo($data, $latestRoute, true);
            if ($info = @$this->updateTrack($info)) {
                $res = $this->buildErrorRouterInfo($data, $latestRoute);
                if ($info = @$this->updateTrack($res, true)) {
                    return $info['records']['record'];
                }
                return $info['records']['record'];
            }
            return false;
        } else {
            apiError('该订单没有路由信息');
        }
    }


    /**
     * 构建order详情信息
     * @param $item
     * @return array
     *
     */

    public function buildMainOrder($item)
    {
        $mainOrder = [
            'other_number' => $item['mainwaybill'],
            'pick_name' =>$this->city==1?$this->pick_name_beijing:$this->pick_name,
            'pick_phone' =>$this->city==1? $this->pick_phone_beijng:$this->pick_phone,
            'pick_address' =>$this->city==1? $this->pick_address_beijing:$this->pick_address,
            'delivery_name' => mb_convert_encoding(urldecode($item['receiver']), 'utf-8', 'gb2312'),
            'delivery_phone' => $item['phone']?:'',
            'delivery_address' => mb_convert_encoding(urldecode($item['address']), 'utf-8', 'gb2312'),
            'weight' => $item['weight'] * 0.001,
            'pay_type'=>$item['cod']>0?6:100,
            'signing_type'=>$item['cod']>0?7:4,
            'collection_price'=>$item['cod'],
            'number' => $item['number'],  //子单数量
            'from'=>'other-wtp',//来源第三方微特派
        ];
        if ($this->debug) {
            $mainOrder['delivery_address'] = '上海市第六人民医院';
            $mainOrder['pick_address'] = '上海市第六人民医院';
            $mainOrder['pick_name'] = '测试:' . $this->pick_name;
        }


        return $mainOrder;
    }


    /**
     *  构建xml头部信息
     * @param $note
     * @param $action
     * @return string
     */

    public function setXmlDom($note, $action)
    {
        $msgxml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>  
        <request>
        <userid>{$this->userid}</userid>
        <msgtype>{$action}</msgtype>
        <records>
        {$note}
        </records>
        </request>";
        return $msgxml;
    }


    /**
     *
     * 构造运单下载反馈,运单状态查询 的xml节点
     * @param $wayBill
     * @return mixed
     *
     */
    public function buildWayBill($wayBill)
    {
        $wayBillS = explode(',', $wayBill);
        foreach ($wayBillS as $i => $item) {
            $res['content'][] = "<record><waybill>" . $item . "</waybill></record>";
        }
        $note = (implode("", $res['content']));
        return $note;
    }


    public function buildUpdate($info, $nextSite = false)
    {
        if ($nextSite) {
            $res = "
        <record>
            <waybill>" . $info['waybill'] . "</waybill>
            <opertime>" . $info['opertime'] . "</opertime>
            <opertype>" . $info['opertype'] . "</opertype>
            <operid>" . $info['operid'] . "</operid>
            <opername>" . $info['opername'] . "</opername>
            <opertel>" . $info['opertel'] . "</opertel>
            <dispatchid>" . $info['dispatchid'] . "</dispatchid>
            <dispatchname>" . $info['dispatchname'] . "</dispatchname>
            <dispatchtel>" . $info['dispatchtel'] . "</dispatchtel>
            <signer>" . $info['signer'] . "</signer>   
            <signtime>" . $info['signtime'] . "</signtime>   
            <remark>" . $info['remark'] . "</remark>
            <paytype>" . $info['paytype'] . "</paytype>
            <uniqueid>" . $info['uniqueid'] . "</uniqueid>
            <nnextsite>" . $info['nnextsite'] . "</nnextsite>
        </record>";
        } else {
            $res = "
        <record>
            <waybill>" . $info['waybill'] . "</waybill>
            <opertime>" . $info['opertime'] . "</opertime>
            <opertype>" . $info['opertype'] . "</opertype>
            <operid>" . $info['operid'] . "</operid>
            <opername>" . $info['opername'] . "</opername>
            <opertel>" . $info['opertel'] . "</opertel>
            <dispatchid>" . $info['dispatchid'] . "</dispatchid>
            <dispatchname>" . $info['dispatchname'] . "</dispatchname>
            <dispatchtel>" . $info['dispatchtel'] . "</dispatchtel>
            <signer>" . $info['signer'] . "</signer>   
            <signtime>" . $info['signtime'] . "</signtime>   
            <remark>" . $info['remark'] . "</remark>
            <paytype>" . $info['paytype'] . "</paytype>
            <uniqueid>" . $info['uniqueid'] . "</uniqueid>
            <operreason>" . $info['operreason'] . "</operreason>
        </record>";
        }
        return $res;

    }

    /**
     * @param $action
     * @param $msgxml =>XML节点
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getXmlData($action, $msgxml)
    {

        $client = new \GuzzleHttp\Client();
        $verifycode = $this->setVerifycode($action, $msgxml);
        $data['city'] = $this->city;
        $data['msgxml'] = $msgxml;
        $data['verifycode'] = $verifycode;
        $data['userid'] = $this->userid;
        $data['msgtype'] = $action;
        $res = $client->request(
            $this->method,
            $this->url,
            [
                'form_params' => $data,
                'debug' => false,
            ]
        );
        $statusCode = $res->getStatusCode();
        if ($statusCode == 200) {
            $body = $res->getBody();
            $stringBody = (string)$body;
            if (@$stringBody) {
                return ($this->simplexml2Array($stringBody));
            }
            return false;
        } else {
            return false;
        }

    }

    /**
     * 获取加密值
     * @param $msgtype
     * @param $msgxml
     * @return string
     */

    public function setVerifycode($msgtype, $msgxml)
    {
        return md5($this->userid . $msgtype . $msgxml . $this->key);

    }


    /**
     * xml转换成数组
     * @param $obj
     * @return mixed
     */
    private function simplexml2Array($obj)
    {
        $objectxml = simplexml_load_string($obj);
        $xmlarray = json_decode(json_encode($objectxml), true);//将json转换成数组
        return $xmlarray;
    }


    /**
     * 将数组转换成xml
     * @param $arr
     * @param null $dom
     * @param null $node
     * @param string $root
     * @param bool $cdata
     * @param string $cdataKey
     * @return string
     */

    private function array2Xml($arr, $dom = null, $node = null, $root = 'request', $cdata = false, $cdataKey = 'MESSAGEDETAIL')
    {
        if (!$dom) {
            $dom = new \DOMDocument('1.0', 'utf-8');
        }
        if (!$node) {
            $node = $dom->createElement($root);
            $dom->appendChild($node);
        }
        foreach ($arr as $key => $value) {
            $child_node = $dom->createElement(is_string($key) ? $key : 'node');
            $node->appendChild($child_node);
            if (!is_array($value)) {
                if ($cdataKey == $key) {
                    $data = $dom->createCDATASection($value);
                } elseif (!$cdata) {
                    $data = $dom->createTextNode($value);
                } else {
                    $data = $dom->createCDATASection($value);
                }
                $child_node->appendChild($data);
            } else {
                $this->array2Xml($value, $dom, $child_node, $root, $cdata);
            }
        }
        return $dom->saveXML();
    }


    /**
     *
     * 测试获取订单信息
     * @param string $id
     * @return mixed
     * @throws \Larfree\Exceptions\ApiException
     *
     */

    public function getExpressOrderRouter($id = '10002043')
    {

        $order = new ExpressOrder();
        $order = $order->append(['edit_status', 'driver_gps']);
        $data = $order->apiSelect()->with(['routers', 'comment', 'insurance'])->orderDingDong($id)
            ->withCount('has_code')->first();
        if (@$data) {
            return $data;
        } else {
            apiError('测试订单信息不存在');
        }
    }


    /**
     * 构建订单信息
     * @param $data =>ExpressOrder->toArray 订单转换成数组
     * @param $route =>xpressOrder->toArray[router][0] 最新的一条路由信息
     * @return array
     *
     */
    public function buildRouterInfo($data, $route, $final = false,$otherNumber)
    {

        $info = [
            'waybill' => $otherNumber,
            'opertime' =>$final ?date('Y-m-d H:i:s', strtotime( '+1 Minute')) :$route->created_at,
            'opertype' => $final ? 16 : $this->buildStatusMap($data->status),
            'operid' => $route->user_id,
            'opername' => $route->name,
            'opertel' => $route->phone,
            'dispatchid' => $route->user_id,
            'dispatchname' => $route->name,
            'dispatchtel' => $route->phone,
            'signer' => $data->delivery_name,
            'signtime' => $data->complete_time,
            'remark' => $route->comment,
            'paytype' => $final ? 0 : '',
            'uniqueid' => $route->id,
        ];
        Log::debug($info);
        return $info;
    }


    /**
     * 构建取消订单信息
     * @param $data =>ExpressOrder->toArray 订单转换成数组
     * @param $route =>xpressOrder->toArray[router][0] 最新的一条路由信息
     * @return array
     *
     */
    public function buildErrorRouterInfo($data, $route, $error = false)
    {

        $info = [
            'waybill' => $data->other_number,
            'opertime' => $error?date('Y-m-d H:i:s'):date('Y-m-d H:i:s', strtotime( '+1 Minute')),
            'opertype' => $error ? 6 : 11,
            'operid' => $route->user_id,
            'opername' => $route->name,
            'opertel' => $route->phone,
            'dispatchid' => $route->user_id,
            'dispatchname' => $route->name,
            'dispatchtel' => $route->phone,
            'signer' => $data->delivery_name,
            'signtime' => $data->complete_time,
            'remark' => $route->comment,
            'nnextsite' => $error ? '' : '210999',
            'operreason' => $error ? 123 : '',
            'uniqueid' => $route->id,
        ];
        Log::debug($info);
        return $info;
    }


    /**
     *
     * -11=>'用户取消',-12=>'系统取消',-20=>'已退款',-30=>'已赔付',-1=>'地址异常',
     * 0=>'待处理',5=>'已打单',10=>'确认',20=>'已付款',30=>'待取件',
     * 40=>'取货中', 45=>'取货完成',
     * 50=>'取货返回中', 55=>'取货返回完成',
     * 60=>'中转中', 65=>'中转已取',67=>'中转接管', 68=>'待接管/调度', 70 => '中转已送',
     * 90=>'派件已取', 92=>'派送中',
     * 94=>'转交第三方派送',96=>'第三方已取货',98=>'第三方派送已签收',
     * 100=>'完成',
     * @param $number
     * @return mixed
     * 获取微特派相应操作类型
     */

    public function buildStatusMap($number)
    {
        $arr = [
            -12 => 11,
            30 => 4,
            40 => 4,
            45 => 4,
            50 => 4,
            55 => 4,
            60 => 4,
            65 => 2,
            67 => 2,
            68 => 2,
            70 => 3,
            90 => 5,
            92 => 5,
            94 => 5,
            96 => 5,
            98 => 5,
            100 => 14,
        ];
        return $arr[$number];
    }


    /**
     * 创建城市映射
     * @param $id
     * @return mixed
     */
    public function buildCityMap($id)
    {
        $arr = [
            1 => 1,    //1北京
            2 => 3,    //3上海
        ];
        return $arr[$id];
    }


    /**
     * 设置当前城市 1北京  2 上海
     * @param $id
     */
    public function setCity($id)
    {
        $cityID = $this->buildCityMap($id);
        $this->city = $cityID;
        setCurrentSiteId($id);
        if(config('wtp.debug')===false) {
            if ($id == 2) {
                $this->userid = config('wtp.userid');
                $this->key = config('wtp.key');
            } else {
                $this->userid = config('wtp.userid_beijing');
                $this->key = config('wtp.key_beijing');
            }
        }
    }

    public function isDebug()
    {
        return $this->debug;
    }

    function fix($data)
    {
        $info = $data;
        if ($info = @$this->updateTrack($info)) {
            return $info['records']['record'];
        }
        return false;

    }


    public  function  fixOrder($otherNumber,$insert=false,$adress=''){
        $this->setCity(getCurrentSiteId());
        $this->setUser();
        $data = $this->createOrder();
        if(!@$data['records']['record']){
            apiError('暂时没数据');
        }
        foreach ($data['records']['record'] as $k => $item) {
            if($item['mainwaybill']==$otherNumber){
                $mainOrder = $this->buildMainOrder($item);
                if (@$item['waybill']) {
                    $wayBills = explode(';', $item['waybill']);
                    if (isset($wayBills[0]) && $wayBills[0]) {
                        foreach ($wayBills as $i => $wayBill) {
                            $mainOrder['children'][$i]['other_number'] = $wayBill;
                        }
                    }
                }
            }
        }
        if(empty($mainOrder)){
            apiError('没有这个三方单号');
        }
        if($insert=='insert'){
            $expOrder = new ExpressOrder();
            try {
                if($mainOrder['weight']>=30){
                    $mainOrder['weight']=30;
                }
                if(!empty($adress)){
                    $mainOrder['delivery_address']=$adress;
                }
                $res = $expOrder->order($mainOrder);
                if (@$res) {
                    $this->updateOrder($mainOrder['other_number']);
                    return true;
                }
            } catch (\Exception $exception) {
                apiError($exception->getMessage());
            }

        }else{
            return $mainOrder;
        }

    }


}