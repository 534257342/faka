<?php
/**
 * 消息队列
 * @author xiaopeng
 * @time 2019/12/13 11:52
 */

namespace App\Support;


use Illuminate\Support\Facades\Redis;
use Milon\Barcode\Facades\DNS1DFacade;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QueueService {

    /**
     * 添加消息队列
     * @param string $name 队列名称
     * @param array $data 数据
     * @param int $execTime 执行时间
     * @return bool|int
     */
    public function add($name, $data, $execTime = 0) {
        $execTime === 0 && $execTime = time();
        return Redis::zAdd($name . '_queue', $execTime, base64_encode(json_encode($data)));
    }

    /**
     * 读取消息队列
     * @param $name
     * @param int $time
     * @param int $limit
     * @return mixed
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function read($name, $time = 0, $limit = 1) {
        $time = $time ?: time();
        $data = Redis::zRangeByScore($name . '_queue', 0, $time, ['limit' => [0, $limit]]);
        if (!empty($data)) {
            return $limit === 1 ? $data[0] : $data;
        }
        return $data;
    }

    /**
     * 根据名称删除消息队列
     * @param $name
     * @param $value
     * @return mixed
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function del($name, $value) {
        return Redis::zRem($name . '_queue', $value);
    }
}
