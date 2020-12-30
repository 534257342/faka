<?php
/**
 * Crontab助手
 *
 * @project snto_yay
 * @author xiaopeng<xiaopeng@snqu.com>
 * @time 2019/12/21 13:51
 */

namespace App\Support\Traits;

trait HelpContab {

    /**
     * 统计日期
     * @var int
     */
    protected $date;

    /**
     * 日期零点时间戳
     * @var int
     */
    protected $time;

    /**
     * Redis连接
     * @var \Redis
     */
    protected $redis;

    /**
     * 队列服务
     * @var vApp\lib\YmQueue
     */
    protected $queue = null;

    /**
     * 是否回溯统计前一天
     * 用于补全凌晨没有统计完成的部分数据
     * @var bool
     */
    //protected $backdate = false;

    /**
     * 日志文件
     * @var string
     */
    protected $log = 'job.log';

    /**
     * 键名前缀
     * @var string
     */
    protected $prefix = 'yay:job:count:';

    /**
     * 回溯标记后缀
     * @var string
     */
    protected $suffix = 'yay:job:count:backdate';

    /**
     * AsyncJobHelper constructor.
     */
    public function __construct() {
        $this->limitWorker();
        $date = date('Ymd');
        $this->log = strtr($this->log, ['.log' => "_$date.log"]);
    }

    /**
     * 获取数据统计的日期
     * @param int $time
     * @return int
     * @author winson.wong<wangwx@snqu.com>
     */
    protected function date(int $time = null) {
        if (empty($this->date)) {
            $time = empty($time) ? time() : $time;
            $cache = $this->suffix . crc32(__CLASS__);
            if (!empty($this->backdate) && date('H', $time) == '00' && !v\Cache::get($cache)) {
                $time -= 86400;
                $this->hookDayBefore($time);
                v\Cache::set($cache, time(), 7200);
            }
            $this->date = intval(date('Ymd', $time));
            $this->time = strtotime($this->date);
        }
        return $this->date;
    }

    /**
     * 获取/设置上次运行的时间
     * @param int $time
     * @param int $expire
     * @return mixed
     * @author winson.wong<wangwx@snqu.com>
     */
    protected function last($time = null, $expire = 86400) {
        $cache = $this->suffix . md5(__CLASS__);
        if (!empty($time)) {
            v\Cache::set($cache, time(), $expire);
            return $this;
        }
        return v\Cache::get($cache);
    }

    /**
     * 获取Redis服务
     * @return object|\Redis
     * @throws v\ClassException
     * @author winson.wong<wangwx@snqu.com>
     */
    protected function redis() {
        if (!$this->redis) {
            $this->redis = v\Redis::object();
        }
        return $this->redis;
    }

    /**
     * 获取队列服务
     * @return vApp\lib\YmQueue
     * @throws v\ClassException
     * @author winson.wong<wangwx@snqu.com>
     */
    protected function queue() {
        if (empty($this->queue)) {
            $this->queue = vApp\lib\YmQueue::object();
        }
        return $this->queue;
    }

    /**
     * 处理前一天数据钩子
     * @param int $time 前一天的时间戳
     * @author winson.wong<wangwx@snqu.com>
     */
    protected function hookDayBefore(&$time) {
        ;
    }

    /**
     * 生成与文件关联的键名
     * @param string $func
     * @param string|int $date
     * @return string
     * @author winson.wong<wangwx@snqu.com>
     */
    protected function key($func, $date = '') {
        return $this->prefix . crc32(__FILE__) . "$date$func";
    }

    /**
     * 记录日志
     * @param $string
     * @param $file
     * @param $line
     * @param array ...$data
     * @author winson.wong<wangwx@snqu.com>
     */
    protected function log($string, $file, $line, ...$data) {
        $string = "$string. $file $line";
        foreach ($data as $idx => $datum) {
            $string .= " \r\n $idx:" . is_array($datum) ? json_encode($datum, JSON_UNESCAPED_UNICODE) : $datum;
        }
        v\App::log($string, $this->log);
    }

    /**
     * 进程数量限制
     * 注意: 此处包含当前进程
     * @param int $workers
     * @author winson.wong<wangwx@snqu.com>
     */
    protected function limitWorker($workers = null) {
        $workers = $workers ?: $this->conf('worker_num', 1);
        $name = v\Req::controller();
        $count = exec("ps -ax | grep '[index.php] $name' | wc -l");
        ($count >= $workers + 1) && die("process limit $workers, exists process $count (include self) \r\n");
        // 延迟执行若干微秒,避免多个进程同步并发
        usleep(rand(100, 100000));
    }

    /**
     * 尝试执行逻辑方法
     * @param callable $callable
     * @param mixed $args
     * @return mixed
     * @author WilsonWong<wangwx@snqu.com>
     */
    protected function triLogicCall($callable, $args = null) {
        try {
            $args = arrayval($args);
            return call_user_func($callable, ...$args);
        } catch (\Exception $exception) {
            $err = $exception->getMessage()
                . ' (' . $exception->getCode() . '), '
                . $exception->getFile() . ' (' . $exception->getLine() . ')'
                . "\n" . $exception->getTraceAsString();
            $err .= ', ' . implode(' ', array_value($_SERVER, 'argv', []));
            echo $err, PHP_EOL;
         //   v\App::log($err, 'error.log');
        }
    }
}
