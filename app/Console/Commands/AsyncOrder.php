<?php

namespace App\Console\Commands;


use App\Console\Commands\Crontab\CrontabHelper;
use App\Models\Common\CommonOrder;
use App\Support\Traits\MyRedis;
use Illuminate\Support\Facades\Redis;
use Illuminate\Console\Command;

/**
 * 同步订单之后的逻辑
 * Class AsyncOrder
 * @package App\Console\Commands
 */
class AsyncOrder extends Command {
    use  MyRedis;
    use CrontabHelper;
    public $interval = 0.5;
    protected $signature = 'async:order';
    protected $description = 'Command description';
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
        $this->worker_name = $this->signature;
        $this->interval = 0.5;
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
                    } elseif ($fails < $this->tris) {
                        Redis::incr($key);
                    } else {
                        $this->queueDel($this->rKey, $task);
                    }
                }
            }
            $this->unlock("async_order_doing_{$lock}");
        }
    }
}
