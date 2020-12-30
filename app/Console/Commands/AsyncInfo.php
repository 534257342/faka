<?php

namespace App\Console\Commands;


use App\Models\Common\CommonOrder;
use App\Support\Traits\MyRedis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

/**
 * 同步订单之后的逻辑
 * Class AsyncOrder
 * @package App\Console\Commands
 */
class AsyncInfo extends Command {
    use  MyRedis;
    protected $signature = 'async:info';
    protected $description = 'Command description';
    public $interval = 0.5;
    protected $rKey = 'order';
    protected $tris = 3;

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct() {
        // 进程数量
        parent::__construct();
    }

    /**
     *
     * @throws \Exception
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function handle() {
        $this->limitWorker(4);
        $this->runMission();
    }

    public function start() {
        $task = $this->queueRead($this->rKey);
        $task && $this->doing($task);
        echo microtime(true), PHP_EOL;
    }

    /**
     * 同一个任务只允许单线程执行，100秒内必须完成
     * @param $task
     * @throws \Exception
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    protected function doing($task) {
        // 需要锁定任务
        $lock = md5($task);
        if ($this->lock("async_order_doing_{$lock}", 50)) {
            $data = json_decode(base64_decode($task), true);
            // 使用回调执行任务
            if (!empty($data['callback'])) {
                $id = empty($data['id']) ? $data['id'] : $data['id'];
                $param = $data['extend'] ?? null;
                // 成功移除任务;失败放回队列尝试再次执行
                $order = (new CommonOrder());
                if ($order->{$data['callback']}($id, $param)) {
                    $this->queueDel($this->rKey, $task);
                } else {
                    $key = "{$lock}_job_fail_num";
                    $fails = Redis::get($key);
                    if (empty($fails)) {
                        Redis::set($key, 1, $fails + 10);
                    } else if ($fails < $this->tris) {
                        Redis::incr($key);
                    } else {
                        $this->queueDel($this->rKey, $task);
                    }
                }
            }
            $this->unlock("async_order_doing_{$lock}");
        }
    }

    /**
     * 按时间间隔执行任务
     */
    public function runMission() {
        $starttime = microtime(true);
        $endtime = $starttime + 600;
        while (true) {
            $starttime += $this->interval;
            $this->start();
            if ($starttime >= $endtime)
                break;
            // sleep时间 = 下次执行时间 - 当前时间，程序执行时间不做计算，比如间隔10秒钟执行，程序执行8秒钟，则sleep 2 秒钟
            $microtime = microtime(true);
            if ($starttime > $microtime) {
                $sleeptime = $starttime - microtime(true);
                usleep($sleeptime * 1000000);
            } else {
                $starttime = $microtime;
            }
        }
    }

    /**
     * 进程数量限制
     * 注意: 此处包含当前进程
     * @author winson.wong<wangwx@snqu.com>
     */
    protected function limitWorker($workers) {
        $count = exec("ps -ax | grep '[php] artisan $this->signature' | wc -l");
        ($count >= $workers + 1) && die("process limit $workers, exists process $count (include self) \r\n");
        // 延迟执行若干微秒,避免多个进程同步并发
        usleep(rand(100, 100000));
    }
}
