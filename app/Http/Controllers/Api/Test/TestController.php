<?php
/**
 * Larfree Api类
 * @author xiao
 */

namespace App\Http\Controllers\Api\Test;

use App\Events\ExpressMission\MissionArrived;
use App\Events\ExpressOrder\OrderCompleted;
use App\Listeners\MainVrp\Arrived;
use App\Models\Common\CommonBanner;
use App\Models\Express\ExpressMission;
use App\Models\Express\ExpressOrder;
use App\Support\Facades\Elastic;

use App\Support\SupportClass\Client;
use Illuminate\Http\Request;
use Larfree\Controllers\ApisController as Controller;
use  \App\Support\Traits\MyRedis;
use Rap2hpoutre\FastExcel\FastExcel;

class TestController extends Controller {
    use  MyRedis;

    public function __construct(CommonBanner $model) {
        $this->model = $model;
        parent::__construct();
    }

    public function test(Request $request) {
        $keyword = $request->keyword;
        $email = Elastic::index('163email')->limit(10)
            ->query(['$or' => [['account' => $keyword], ['phone' => $keyword]]])->search();
        $jobs = Elastic::index('job')->limit(10)
            ->query(['$or' => [['user_name' => $keyword], ['user_phone' => $keyword]]])->search();
        //格式化工作信息
        if (!empty($jobs)) {
            $this->formatJobData($jobs);
        }
        $momo = Elastic::index('momo')->limit(10)->query(['account' => $keyword])->search();
        return array_collapse([$email, $jobs, $momo]);

    }

    public function test2() {
        $file = (storage_path("app/public/beijing.xlsx"));
        (new FastExcel)->import($file, function ($line) {
            $data = [
                'user_name'    => $line['username'] ? trim($line['username']) : '',
                'birth'        => $line['birth'] ?? '',
                'educate'      => $line['educate'] ? trim($line['educate']) : '',
                'user_phone'   => $line['phone'] ? (string)($line['phone']) : '',
                'city'         => $line['city'] ? (trim($line['city'])) : '',
                'place_detail' => $line['place_detail'] ? ($line['place_detail']) : '',
                'company'      => $line['company'] ? trim($line['company']) : '',
                'job_detail'   => $line['job_detail'] ? trim($line['job_detail']) : '',
                'job'          => $line['job'] ? trim($line['job']) : '',
                'school'       => $line['school'] ? trim($line['school']) : '',
                'major'        => $line['major'] ? trim($line['major']) : '',
                'start'        => $line['start'] ? trim($line['start']) : '',
                'from'         => 'boss'
            ];
            if (!empty($line['phone'])) {
                $data['_id'] = md5($line['phone']);
            }
            Elastic::index('job')->addOne($data);
        });
    }

    public function test3() {
        $postData = [
            'msgtype' => 'news',
            'news'    => [
                'articles' => [
                    [
                        'title'       => '测试',
                        'description' => '测试',
                        'url'         => 'www.baidu.com',
                        'picurl'      => 'http://res.mail.qq.com/node/ww/wwopenmng/images/independent/doc/test_pic_msg1.png'
                    ]
                ]
            ]
        ];
        $client = new Client();
        $client->setUrl("https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=317a7fd1-213f-434c-9452-705544b8aedc");
        $client->setMethod('post');
        $client->setData($postData);
        $res = $client->sendMessage();
        dd($res);
        exit();
        /*        MyQueue::add('guard', [
                    'is_model' => 1,
                    'order'    => ['id' => 12313],
                    'callback' => ['App\Models\Common\CommonBanner', 'test2']
                ], time() + 10);*/
    }

    public function formatJobData(&$data) {
        array_column_rekey($data, [
            'user_name'    => '姓名',
            'from'         => '来源',
            'educate'      => '学历',
            'user_phone'   => '手机号',
            'city'         => '户籍地',
            'place_detail' => '住址',
            'company'      => '层任职公司',
            'job_detail'   => '职业',
            'job'          => '行业',
            'school'       => '学校',
            'major'        => '专业',
            'start'        => '入学年份'
        ]);
    }
}
