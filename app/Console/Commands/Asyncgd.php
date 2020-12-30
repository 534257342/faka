<?php
/**
 * 异步任务处理
 *
 * @project snto_yay_dev
 * @author xiaopeng<xiaopeng@snqu.com>
 * @time 2019/8/13 21:58
 */

namespace App\Console\Commands;


use App\Console\Commands\Crontab\CrontabHelper;
use App\Models\Common\CommonOrder;
use App\Support\Traits\MyRedis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AsyncGD extends Command {
    use  MyRedis;
    use CrontabHelper;
    /**
     * 运行间隔时长
     * @var bool
     */
    public $interval = 1;
    protected $signature = 'async:guard';
    protected $description = 'Command description';
    protected $rKey = 'guard';
    protected $tris = 3;

    /**
     * 输入调试
     * @var bool
     */
    protected $console = false;


    /**
     * 参数配置
     * @var array
     */
    protected static $configs = [
        'worker_num' => 1
    ];

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct() {
        // 进程数量
        parent::__construct();
    }


    /**
     * 运行异常
     * @var array
     */
    protected $mistakes = [];

    /**
     * 开始工作
     * @return bool
     * @throws v\ClassException
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function start() {
        $taskCode = $this->queue()->read($this->rKey);
        // 任务必须指定回调信息
        $taskInfo = $taskCode ? json_decode(base64_decode($taskCode), true) : false;
        if (empty($taskInfo) || empty($taskInfo['callback'])) {
            $taskInfo && Log::debug(__METHOD__ . ' invalid call args' . $taskCode);

            $this->consoleInfo('empty or invalid task', $taskCode);
            return true;
        }
        // 验证是否错误超过次数
        $taskId = md516(json_encode($taskInfo));
        if (!empty($this->mistakes[$taskId]) && $this->mistakes[$taskId] >= 3) {
            $this->queue()->del($this->rKey, $taskCode);
            v\App::log(__METHOD__ . ' jod failed over 3 times:' . $taskCode, 'error.log');
            $this->consoleInfo('jod failed over 3 times', $taskId);
            return true;
        }
        // 尝试执行任务回调内容
        $this->consoleInfo('start doing job', $taskCode);
        return $this->doing($taskInfo, $taskId, $taskCode);
    }

    /**
     * 尝试执行回调任务
     * 同一个任务只允许单线程执行，20秒内必须完成
     * @param array $taskInfo
     * @param string $taskId
     * @param string $task
     * @return bool
     * @author WilsonWong<wangwx@snqu.com>
     */
    protected function doing(array $taskInfo, string $taskId, string $task) {
        try {
            // 锁定任务防止重复执行
            $redis = $this->redis();
            if (!$redis->lock("job_doing_{$taskId}", 20)) {
                $this->consoleInfo('set job lock failed', $taskId);
                return true;
            }
            $this->log(__METHOD__, __LINE__, 'locked and start job', $taskInfo);
            // 构造回调方法和参数
            $callback = $taskInfo['callback'];
            if (!empty($taskInfo['is_model'])) {
                $callback[0] = v\App::model($callback[0]);
            }
            // 执行指定的回调处理
            $exec = call_user_func($callback, array_unset($taskInfo, 'callback'));
            if (empty($exec)) {
                $this->log(__METHOD__, __LINE__, 'callback exec failed', $taskInfo, v\Err::getMessage());
                $key = "{$taskId}_fail_count";
                $fails = $redis->incr($key);
                if ($fails >= 3) {
                    $this->queue()->del(self::$this->rKey, $task);
                    v\App::log(__METHOD__ . " jod failed over 3 times:$task "
                        . json_encode(v\Err::getMessage(), JSON_UNESCAPED_UNICODE), 'error.log');
                }
                $redis->expireAt($key, time() + 600);
                $this->consoleInfo('job callback exec failed', $taskId);
                return false;
            }
            $redis->unlock("job_doing_{$taskId}", 20);
            $this->queue()->del(self::$this->rKey, $task);
            $this->log(__METHOD__, __LINE__, 'callback exec success', $taskInfo);
            $this->consoleInfo('job callback exec success', $taskId);
            return true;
        } catch (\Exception $exception) {
            v\App::log(__METHOD__ . '|exception|' . $exception->getMessage() . ' (' . $exception->getCode() . '), '
                . $exception->getFile() . ' (' . $exception->getLine() . ')' . "\n" . $exception->getTraceAsString()
                . "\r\n" . json_encode($taskInfo, JSON_UNESCAPED_UNICODE), 'error.log');
            !empty($this->mistakes[$taskId]) ? $this->mistakes[$taskId]++ : ($this->mistakes[$taskId] = 1);
            return false;
        }
    }

    /**
     * 打印出调试信息
     * @param string $desc
     * @param mixed $data
     * @return $this
     * @author WilsonWong<wangwx@snqu.com>
     */
    protected function consoleInfo(string $desc, $data = '') {
        if ($this->console) {
            $data = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
            echo date('m-d H:i:s'), " $desc ", $data, PHP_EOL;
        }
        return $this;
    }

    /**
     * 设置进程数量限制
     * @param int $workers
     * @author WilsonWong<wangwx@snqu.com>
     */
    protected function limitWorker($workers = null) {
        // 动态设置允许的程序数量
        $this->limitWorkerD($workers);
        $this->console = !empty($_SERVER['LS_COLORS']);
    }
}
