<?php

namespace App\Console\Commands\Crontab;

trait CrontabHelper {
    /**
     * 间隔时间，秒
     * 取值0.000001 --- 600
     * >=600秒，则每次调用只执行一次
     * @var int
     */
    public $interval = 600;
    public $worker_name = 'example';

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
     * 交给子类实现逻辑
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function start() {

    }

    /**
     * 是否有该进程执行中
     * 有必须单进程允许的任务调用该方法判断
     * @return boolean
     */
    public function haveRun() {
        $count = exec("ps -ax | grep '[php] artisan $this->worker_name' | wc -l");
        return $count > 1;
    }

    /**
     * 进程数量限制
     * @param $workers
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    protected function limitWorker($workers) {
        $count = exec("ps -ax | grep '[php] artisan $this->worker_name' | wc -l");
        ($count >= $workers + 1) && die("process limit $workers, exists process $count (include self) \r\n");
        usleep(rand(100, 100000));
    }

}
