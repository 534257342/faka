<?php


function getCurrentSiteId() {
    $city = Request()->header('city') ?? 1;
    return intval($city);
}

function setCurrentSiteId($id) {
    Request()->headers->set('city', $id);
}

function getCurrentCity() {
    $ip = \Request()->getClientIp();
    $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=2TGbi6zzFm5rjYKqPPomh9GBwcgLW5sS&ip={$ip}&coor=bd09ll");
    $address = '未知';
    if (!empty($content)) {
        $json = json_decode($content, true);
        if (!empty($json)) {
            $address = $json['content']['address'];
        }
    }
    return $address;
}

/**
 * @param $name
 * @param $key
 * @return mixed
 * 获取配置名称
 */
function getSchemas($name, $key) {
    $schemas = \Larfree\Libs\Schemas::getSchemas($name);
    foreach ($schemas as $skey => $schema) {
        if ($skey == $key) {
            return $schema['name'];
        }
    }
}

/**
 * @return mixed
 * 根据IP定位城市
 */
function getCity() {
    $ip = request()->getClientIp();
    return app('address_parse')->getGeoIpCity($ip);
}


function array_is_column($array) {
    return isset($array[0]) && is_array($array[0]);
}

/**
 * 删除数据元素并返回删除的值
 * @param array $array
 * @param string | array $key
 * @return mixed 数组返回数组，字符串返回字符串
 */
function array_delete(&$array, $key) {
    if (is_array($key)) {
        $value = [];
        foreach ($key as $k) {
            $value[$k] = array_delete($array, $k);
        }
    } else {
        $value = null;
        if ($pos = strpos($key, '.')) {
            $pkey = substr($key, 0, $pos);
            $key = substr($key, $pos + 1);
            if (isset($array[$pkey]))
                return array_delete($array[$pkey], $key);
        } elseif (isset($array[$key])) {
            $value = $array[$key];
            unset($array[$key]);
        }
    }
    return $value;
}

/**
 * 数组KEY重命名
 * @param array $array
 * @param array $keys
 * @return array
 */
function array_rekey(&$array, $keys) {
    foreach ($keys as $key => $keyNew) {
        if (isset($array[$key])) {
            $array[$keyNew] = $array[$key];
            unset($array[$key]);
        }
    }
    return $array;
}

/**
 * 二位数组重命名列
 * @param array $array
 * @param array $keys
 * @return array
 */
function array_column_rekey(&$array, $keys) {
    if (!array_is_column($array)) {
        array_rekey($array, $keys);
    } else {
        foreach ($array as &$item) {
            array_rekey($item, $keys);
        }
    }
    return $array;
}


function getCityCode($site) {
    switch ($site) {
        case 1:
            $cityCode = '010';
            break;
        case 2:
            $cityCode = '021';
            break;
        case 3:
            $cityCode = '0755';
            break;
        case 4:
            $cityCode = '020';
            break;
    }
    return $cityCode;
}

/**
 * @param $address
 * @return mixed
 * 地址纠偏解析高德和百度然后计算相差距离
 */
function getLocation($address) {
    $city = getCurrentSite();
    return app('address_parse')->getAddress($address, $city['name'], 1);
}

/**
 * @param $filename
 * @param $width
 * @param $height
 * @param int $mode
 * @return string
 * 生成缩略图
 */
function getThumbs($filename, $width, $height, $mode = 0) {
    if (!$filename)
        return '';
    $type = env('UPLOAD_TYPE', 'qiniu');
    switch ($type) {
        case 'file':
            return env('APP_URL') . '/' . $filename . "?imageView2/{$mode}/w/{$width}/h/{$height}";
            break;
        default:
            $disk = \Storage::disk('qiniu'); //使用七牛云上传
            if ($mode == '-1') {
                return $disk->downloadUrl($filename)->__toString();//裁剪
            } else {
                return $disk->imagePreviewUrl($filename, "imageView2/{$mode}/w/{$width}/h/{$height}")->__toString();//裁剪
            }
        case 'oss':
            $filename = $filename . '?x-oss-process=image/resize,l_' . $width;
            return $filename . '&x-oss-process=image/crop,w_' . $width . ',h_' . $height . ',g_center';
            break;
    }
}

function getWeek() {
    $date = date('l');
    switch ($date) {
        case 'Monday':
            $name = 1;
            break;
        case 'Tuesday':
            $name = 2;
            break;
        case 'Wednesday':
            $name = 3;
            break;
        case 'Thursday':
            $name = 4;
            break;
        case 'Friday':
            $name = 5;
            break;
        case 'Saturday':
            $name = 6;
            break;
        case 'Sunday':
            $name = 7;
            break;
    }
    return $name;
}

if (!function_exists('LogData')) {
    function LogData($title = '') {
        //手动记录日志。Laravel 有些地方不知道模型事件
        $action = \Route::current();
        if (!$action) {
            $class = '未知';
            $method = '未知';
        } else {
            $action = $action->getActionName();
            list($class, $method) = explode('@', $action);
        }
        $ip = \Request()->getClientIp();
        $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=2TGbi6zzFm5rjYKqPPomh9GBwcgLW5sS&ip={$ip}&coor=bd09ll");
        $address = '未知';
        if (!empty($content)) {
            $json = json_decode($content, true);
            if (!empty($json)) {
                $address = $json['content']['address'];
            }
        }
        $data['model'] = $class;
        $data['method'] = $method;
        $data['ip'] = $ip;
        $data['user_id'] = getLoginUserID() ?? 0;
        $data['address'] = $address;
        $data['key'] = 0;
        $data['type'] = 1;
        $data['after_content'] = null;
        $data['title'] = $title;
        $data['before_content'] = null;
        $model = 'App\Models\User\\' . ucfirst('userActionLog');
        if (class_exists($model)) {
            $log = new $model();
            if ($log->create($data)) {
                return true;
            }
        }
    }
}

/**
 * 把返回的数据集转换成Tree
 * @access public
 *  User: xiao
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 */
if (!function_exists('listToTree')) {
    function listToTree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0) {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }
}

if (!function_exists('treeChild')) {
    function treeChild($id, $result = array()) {
        global $result;
        $list = \App\Models\Common\CommonUser::query()->where('parent_id', $id)->get();
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $result[] = $val->id;
                treeChild($val->id, $result);
            }
        }
        return $result;
    }
}
//获取客服端ip
if (!function_exists('get_client_ip')) {
    function get_client_ip($type = 0, $adv = false) {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL) return $ip[$type];
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) unset($arr[$pos]);
                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
}

if (!function_exists('array_unset')) {
    /**
     * 删除数组中某几列的值
     * @param array $array 数组
     * @param string|array $keys 列键，多个可用逗号隔开
     * @return array
     */
    function array_unset(&$array, $keys) {
        if (is_string($keys))
            $keys = explode(',', $keys);
        $keys = array_flip($keys);
        $array = array_diff_key($array, $keys);
        return $array;
    }
}
