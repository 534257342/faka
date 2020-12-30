<?php
/**
 * 异步任务处理
 *
 * @project snto_yay_dev
 * @author xiaopeng<xiaopeng@snqu.com>
 * @time 2020/12/30 14:17
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
    protected $signature = 'async:guard';
    protected $description = 'Command description';
    protected $rKey = 'guard';
    protected $tris = 3;

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
        parent::__construct();
    }


    /**
     * 输入调试
     * @var bool
     */
    protected $console = false;

    /**
     * 运行异常
     * @var array
     */
    protected $mistakes = [];

    /**
     *
     * @throws \Exception
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function handle() {
        $this->worker_name = $this->signature;
        $this->interval = 1;
        $this->limitWorker(1);
        $this->runMission();
    }

    /**
     * 开始工作
     * @return bool
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function start() {
        $taskCode = $this->queueRead($this->rKey);
        // 任务必须指定回调信息
        $taskInfo = $taskCode ? json_decode(base64_decode($taskCode), true) : false;
        if (empty($taskInfo) || empty($taskInfo['callback'])) {
            $taskInfo && Log::debug(__METHOD__ . ' invalid call args' . $taskCode);
            $this->consoleInfo('empty or invalid task', $taskCode);
            return true;
        }
        // 验证是否错误超过次数
        $taskId = md5(json_encode($taskInfo));
        if (!empty($this->mistakes[$taskId]) && $this->mistakes[$taskId] >= 3) {
            $this->queueDel($this->rKey, $taskCode);
            Log::debug(__METHOD__ . ' jod failed over 3 times:' . $taskCode);
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
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    protected function doing($taskInfo, $taskId, $task) {
        try {


            if (!$this->lock("job_doing_{$taskId}", 20)) {
                $this->consoleInfo('set job lock failed', $taskId);
                return true;
            }
            // 构造回调方法和参数
            $callback = $taskInfo['callback'];
            if (!empty($taskInfo['is_model'])) {
                $callback[0] = (new $callback[0]);
            }
            $exec = call_user_func($callback, array_unset($taskInfo, 'callback'));
            if (empty($exec)) {
                $key = "{$taskId}_fail_count";
                $fails = Redis::incr($key);
                if ($fails >= 3) {
                    $this->queueDel($this->rKey, $task);
                }
                Redis::expireAt($key, time() + 600);
                $this->consoleInfo('job callback exec failed', $taskId);
                return false;
            }
            $this->unlock("job_doing_{$taskId}", 20);
            $this->queueDel($this->rKey, $task);
            $this->consoleInfo('job callback exec success', $taskId);
            return true;
        } catch (\Exception $exception) {
            Log::debug(__METHOD__ . '|exception|' . $exception->getMessage());
            !empty($this->mistakes[$taskId]) ? $this->mistakes[$taskId]++ : ($this->mistakes[$taskId] = 1);
            return false;
        }
    }

    /**
     * 打印出调试信息
     * @param string $desc
     * @param mixed $data
     * @return $this
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    protected function consoleInfo(string $desc, $data = '') {
        if ($this->console) {
            $data = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
            echo date('m-d H:i:s'), " $desc ", $data, PHP_EOL;
        }
        return $this;
    }

}
