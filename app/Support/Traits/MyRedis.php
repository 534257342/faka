<?php

namespace App\Support\Traits;


use Illuminate\Support\Facades\Redis;

trait MyRedis {

    /**
     * 进程排他锁
     * @param $key
     * @param int $expire
     * @return bool
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function lock($key, $expire = 1) {
        $key = "lock_$key";
        // redis新版本可以用，参数形式完成这个方法。这里可能形成永不过期
        if (Redis::setnx($key, 1)) {
            Redis::expire($key, $expire);
            return true;
        }
        return false;
    }

    /**
     * 释放锁
     * @param string $key
     */
    public function unlock($key) {
        $key = "lock_$key";
        Redis::del($key);
    }

    /**
     * Redis线程阻塞锁
     * @param string $key 键名
     * @param int $time 持续时长，单位：秒
     * @param int $pending 阻塞时长，单位：毫秒
     * @return bool
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    protected function lockAndPending($key, $time, $pending) {
        $isLocked = false;
        while ($pending && !($isLocked = $this->lock($key, $time))) {
            $pending -= 100;
            usleep(100 * 1000);
        }
        return $isLocked;
    }

    /**
     * 生成9位自增唯一ID
     * 每天从头开始编号，前三位表示天，后6位表示ID序号
     * @param string $name id集合名称
     * @return string
     */
    public function uniqid9($name = 'v7') {
        $day10 = floor((time() - 1320041600) / 86400);
        $day36 = base_convert($day10, 10, 36);
        $id10 = $this->hIncrBy("uniqid_by_day", "{$name}_{$day36}", 1);
        $id36 = base_convert($id10, 10, 36);
        return substr('000' . $day36, -3) . substr('000000' . $id36, -6);
    }

    /**
     * 添加到消息队列
     * @param string $name 队列名称
     * @param array $data 数据
     * @param int $execTime 执行时间
     * @return bool|int
     */
    public function queueAdd($name, $data, $execTime = 0) {
        $execTime === 0 && $execTime = time();
        return Redis::zAdd($name . '_queue', $execTime, base64_encode(json_encode($data)));
    }

    /**
     * 从队列读取消息
     * @param string $name
     * @param int $time
     * @param int $limit
     * @return mixed
     */
    public function queueRead($name, $time = 0, $limit = 1) {
        $time = $time ?: time();
        $data = Redis::zRangeByScore($name . '_queue', 0, $time, ['limit' => [0, $limit]]);
        if (!empty($data)) {
            return $limit === 1 ? $data[0] : $data;
        }
        return $data;
    }

    /**
     * 从队列删除消息
     * @param string $name
     * @param string $value
     * @return mixed
     */
    public function queueDel($name, $value) {
        return Redis::zRem($name . '_queue', $value);
    }

}
