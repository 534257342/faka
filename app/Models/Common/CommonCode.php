<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */

namespace App\Models\Common;

use App\Events\Sms\CodeEvent;
use App\Events\Sms\SmsEvent;
use Illuminate\Support\Facades\Log;
use Larfree\Models\Api;
use App\Scopes\Common\CommonCodeScope;

class CommonCode extends Api {
    use CommonCodeScope;

    /**
     * 短信验证码
     * @param $phone
     * @param $code
     * @param $type
     * @return array|null
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function sendCode($phone, $code, $type) {
        $content = '您的验证码是：' . $code . '，如非本人操作，请忽略此短信';
        $data = event(new SmsEvent($phone, $content, $type));
        $this->addCode($phone, $code);
        return $data;
    }

    /**
     * @param $phone
     * @param $code
     * @param $type
     * 往数据库添加验证码
     */
    public function addCode($phone, $code) {
        $data = [
            'phone'        => $phone,
            'code'         => $code,
            'type'         => 1,
            'status'       => 1,
            'overdue_time' => date("Y-m-d H:i:s", time() + 300),
        ];
        $this->create($data);
    }

    /**
     * @param $phone
     * 语音验证码
     */
    public function sendVoiceCode($phone, $code, $type) {
        $data = event(new SmsEvent($phone, $code, $type));
        $this->addCode($phone, $code);
        return $data;
    }
}
