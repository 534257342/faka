<?php
/**
 * 消息队列
 * @author xiaopeng
 * @time 2019/12/13 11:52
 */

namespace App\Support;


class ElasticService {

    /**
     * 配置定义
     * @var array
     */
    protected static $configs = [
        'host'     => 'http://101.201.141.138',
        'port'     => 9200,
        'username' => null,
        'password' => null,
    ];

    /**
     * Curl对象
     * @var resource
     */
    protected $curl = null;

    /**
     * es主机与端口dsn
     * @var string
     */
    protected $host = null;

    /**
     * 错误详情
     * @var string
     */
    protected $error = null;

    /**
     * 调试模式
     * @var bool
     */
    protected $debug = false;

    /**
     * 索引名称 即db
     * @var string
     */
    protected $index = 'member';

    /**
     * 索引类型 即表名
     * @var string
     */
    protected $type = '_doc';

    /**
     * 条数
     * @var int
     */
    protected $limit = 0;

    /**
     * 跳过多少条
     * @var int
     */
    protected $skip = 0;

    /**
     * 获取查询详情
     * @var bool
     */
    protected $detail = false;

    /**
     * 排序
     * @var array
     */
    protected $sort = [];

    /**
     * 搜索条件
     * @var array
     */
    protected $query = [];

    /**
     * 过滤条件 不带权重
     * @var array
     */
    protected $filter = [];

    /**
     * ElasticSearchService constructor.
     */
    public function __construct() {
        $this->host();
        $this->curl = curl_init();
    }

    /**
     * 生成host
     * @param string $host
     * @return string
     */
    protected function host($host = null) {
        if (!empty($host)) {
            $conf = self::$configs;
            $this->host = (empty($conf['username']) ? '' : "--user {$conf['username']}:{$conf['password']}") . "{$host}:{$conf['port']}";
            // 加入host协议
            if (substr($this->host, 0, 4) !== 'http') {
                $this->host = 'http://' . $this->host;
            }
        } elseif (empty($this->host)) {
            // 随机使用host
            $hosts = explode(',', self::$configs['host']);
            $host = $hosts[0];
            // 多个host代表读写分离，随机使用host
            if (count($hosts) > 1) {
                array_shift($hosts);
                $host = array_rand($hosts);
            }
            return $this->host($host);
        }
        return $this->host;
    }

    /**
     * 执行命令
     * @param string $method
     * @param string $url
     * @param array $data
     * @return bool|mixed
     * @author WilsonWong<wangwx@snqu.com>
     */
    protected function exec($method, $url, $data = null) {
        $url = "{$this->host}/" . ltrim($url, '/');
        curl_setopt_array($this->curl, [
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: */*'],
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_NOBODY         => false,
            CURLOPT_URL            => $url,
            CURLOPT_TIMEOUT        => 2,
        ]);
        // 请求体
        if (!is_null($data)) {
            if (!array_is_column($data)) {
                // 非批量
                $content = empty($data) ? '{}' : json_encode($data);
            } else {
                // 批量插入或更新
                $content = '';
                foreach ($data as $item) {
                    $content .= json_encode($item) . "\n";
                }
            }
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $content);
        }
        // 请求结果
        $content = curl_exec($this->curl);
        if (!empty($content)) {
            return json_decode($content, true);
        }
        $this->error = curl_error($this->curl);
        return false;
    }

    /**
     * 解析查询条件
     * @param array $criteria 查询条件 mongodb条件书写方式
     * @return array
     */
    protected function parseQuery($criteria = []) {
        $query = [];
        $range = [];
        foreach ($criteria as $k => $v) {
            if ($k == '$or') { // or关系
                // 简化查询体
                foreach ($v as $v1) {
                    // 简化查询体
                    $query['bool']['minimum_should_match'] = 1;
                    $query1 = $this->parseQuery($v1);
                    if ((count($query1['bool']) === 1) && isset($query1['bool']['must']) && count($query1['bool']['must']) === 1) {
                        $query1 = $query1['bool']['must'][0];
                    }
                    $query['bool']['should'][] = $query1;
                }
            } elseif (is_array($v)) { // 数组计算逻辑符
                foreach ($v as $k1 => $v1) {
                    switch ($k) {
                        case '$in': // 做精确查询处理
                            $query['bool']['filter']['bool']['must'][]['terms'][$k1] = $v1;
                            break;
                        case '$ne':
                            $query['bool']['must_not'][]['match'][$k1] = $v1;
                            break;
                        case '$lt':
                        case '$gt':
                        case '$lte':
                        case '$gte':
                            $range[$k][substr($k1, 1)] = $v1;
                            break;
                        case '$eq':
                            $query['bool']['must'][]['match'][$k1] = $v1;
                            break;
                        default :
                            $query['bool']['must'][]['match_phrase'][$k1] = $v1;
                    }
                }
            } else {  // 普通and关系
                $query['bool']['must'][]['match_phrase'][$k] = $v;
            }
        }
        // 范围查询处理，范围查询数据精确匹配不用评分，使用filter过滤器
        if (!empty($range)) {
            foreach ($range as $field => $set) {
                $query['bool']['filter']['bool']['must'][]['range'][$field] = $set;
            }
        }
        return $query;
    }

    /**
     * 设置调试模式
     * @param bool $debug
     * @return $this
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function debug($debug = true) {
        $this->debug = $debug;
        return $this;
    }

    /**
     * 设置|取得当前索引
     * @param string $index
     * @return $this|string
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function index($index = null) {
        $envKey = env('APP_DEBUG') ? 'dev_' : 'prd_';
        if (!empty($index)) {
            $this->index = $envKey . $index;
            return $this;
        }
        return $envKey . $this->index;
    }

    /**
     * 删除索引
     * @param string $index
     * @return $this
     */
    public function drop($index = null) {
        $index = $index ?: $this->index;
        if (!empty($index)) {
            $this->exec('DELETE', $index);
        }
        return $this;
    }

    /**
     * 设置类型映射
     * 同索引下的类型字段名不能有冲突
     * 类型不适合完全不同类型的数据,类型只是不同集合中的细分
     * @param string $type
     * @return $this|string
     */
    public function type($type = null) {
        if (!empty($type)) {
            $this->type = $type;
            return $this;
        }
        return $this->type;
    }

    /**
     * 保存一条数据
     * @param array $data 必须包含_id数据
     * @param string $index
     * @return int
     * @throws v\Exception
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function addOne($data) {
        $id = array_delete($data, '_id');
        $rs = $this->exec('PUT', "{$this->index}/{$this->type}/{$id}", $data);
        if (!empty($rs['error']))
            apiError('ElasticSearch insert error:' . json_encode($rs));
        return $rs;
    }

    /**
     * 保存多条数据
     * @param array $data
     * @param string $index
     * @return int
     * @throws v\Exception
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function addAll($data) {
        $items = [];
        foreach ($data as $item) {
            $items[] = ['index' => ['_id' => array_delete($item, '_id')]];
            $items[] = $item;
        }
        $rs = $this->exec('PUT', "$this->index/{$this->type}/_bulk", $items);
        if (!empty($rs['errors']))
            throw new v\Exception('ElasticSearch bulk error:' . json_encode($rs));
        return count($rs['items']);
    }

    /**
     * 更新一条数据
     * 该方法必须在url上指定id
     * @param array $data
     * @param string $index
     * @return int
     * @throws v\Exception
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function upOne($data) {
        $id = array_delete($data, '_id');
        if (empty($id)) {
            v\Err::add('请指定_id');
            return false;
        }
        $rs = $this->exec('POST', "{$this->index}/{$this->type}/{$id}/_update", ['doc' => $data]);
        if (!empty($rs['error'])) {
            v\Err::add('更新失败');
            return false;
        }
        return $rs;
    }

    /**
     * 更新多条数据
     * 不能采用_bulk方法
     * @param array $data
     * @param string $index
     * @return int  更新成功的数量
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function upAll($data) {
        $criteria = [];
        // 分页处理
        $query = $this->query;
        // 查询条件 带权重
        $criteria['query'] = $this->parseQuery($query);
        if (empty($criteria['query'])) {
            v\Err::add("请指定查询条件");
            return false;
        }
        $content = '';
        $count = count($data);
        $i = 0;
        foreach ($data as $k => $datum) {
            $i++;
            if ($i == $count) {
                $content .= "ctx._source['{$k}']={$datum}";
            } else {
                $content .= "ctx._source['{$k}']={$datum}" . ";";
            }
        }
        $criteria['script']['inline'] = $content;
        $rs = $this->reset()->exec('GET', "{$this->index}/{$this->type}/_update_by_query", $criteria);
        if (!empty($rs['total'])) {
            return $rs['total'];
        }
        return false;
    }

    /**
     * 删除一个或多个文档
     * @param string $id 要删除的数据ID
     * @return bool|int|mixed
     */
    public function delete($id) {
        $ids = arrayval($id);
        if (count($ids) === 1) {
            $rs = $this->exec('DELETE', "{$this->index}/{$this->type}/{$id}");
            if (!empty($rs))
                $rs = 1;
        } else {
            $items = [];
            foreach ($ids as $id) {
                $items[] = ['delete' => ['_id' => $id]];
            }
            $rs = $this->exec('PUT', "{$this->index}/{$this->type}/_bulk", $items);
            if (!empty($rs))
                $rs = count($rs['items']);
        }
        return $rs;
    }

    /**
     * 按ID取得一条数据
     * @param string $id
     * @return bool|mixed|null
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function getByID($id) {
        $rs = $this->reset()->exec('GET', "{$this->index}/{$this->type}/{$id}");
        if (!empty($rs['found']) && !empty($rs['_source'])) {
            return array_merge(['_id' => $id], $rs['_source']);
        }
        return null;
    }

    /**
     * 按ID查询多条数据
     * @param string|array $ids
     * @return array
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function getByIDs($ids) {
        $items = [];
        $rs = $this->reset()->exec('GET', "{$this->index}/{$this->type}/_mget", ['ids' => arrayval($ids)]);
        if (!empty($rs['docs'])) {
            foreach ($rs['docs'] as $doc) {
                if (!empty($doc['found']) && !empty($doc['_source'])) {
                    $items[] = array_merge(['_id' => $doc['_id']], $doc['_source']);
                }
            }
        }
        return $items;
    }

    /**
     * 取得所有数据
     * @return array
     */
    public function getAll() {
        return $this->search([]);
    }

    /**
     * 设置搜索条件
     * @param array $query
     * @return $this
     */
    public function query($query) {
        $this->query = $query;
        return $this;
    }

    /**
     * 过滤条件，过滤条件不带权重
     * @param array $filter
     * @return $this
     */
    public function filter($filter) {
        $this->filter = $filter;
        return $this;
    }

    /**
     * 设置是否获取详情
     * @param $detail
     * @return $this
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function detail($detail) {
        $this->detail = $detail;
        return $this;
    }

    /**
     * 搜索查询
     * @param array $query
     * @return mixed
     */
    public function search($query = null) {
        $criteria = [];
        // 分页处理
        $query = $query ?: $this->query;
        if (!empty($this->limit)) {
            $criteria['from'] = $this->skip;
            $criteria['size'] = $this->limit;
        }
        // 排序处理
        if (!empty($this->sort)) {
            foreach ($this->sort as $k => $v) {
                $criteria['sort'][][$k]['order'] = ($v == -1 ? 'desc' : 'asc');
            }
            if (!empty($query)) // 有相关性排序查询，按评分排序
                $criteria['sort'][]['_score']['order'] = 'desc';
        }
        // 查询条件 带权重
        $criteria['query'] = $this->parseQuery($query);
        // 过滤条件 不带权重 性能好
        if (!empty($this->filter)) {
            $filter = ['bool' => ['filter' => $this->parseQuery($this->filter)]];
            array_extend($criteria['query'], $filter);
        }
        if (empty($criteria['query'])) {
            unset($criteria['query']);
        }
        $rs = $this->reset()->exec('GET', "{$this->index}/{$this->type}/_search", $criteria);
        if (!empty($rs['hits'])) {
            foreach ($rs['hits']['hits'] as &$doc) {
                $doc = array_merge(['_id' => $doc['_id']], $doc['_source']);
            }
            return $this->detail ? $rs['hits'] : $rs['hits']['hits'];
        }
        return false;
    }

    /**
     * 取得分页数据
     * @param array $options 选项，包含 limit skip field sort row page all
     *         row 每页条数, page 页码,  all 最大统计条数，模糊查询的时候使用
     * @param bool $fuzzyCount 是否模糊统计数据
     * @return array
     */
    public function getPaging($options = []) {
        $page = intval(array_value($options, 'page', 1));
        $row = !empty($options['row']) ? intval($options['row']) : ($this->limit ?: 10);
        $data = $this->skip($row * ($page - 1))->limit($row)->detail(true)->search();
        $result = ['count' => 0, 'page' => 0, 'data' => []];
        if (!empty($data['hits']) && !empty($data['total'])) {
            $result['data'] = $data['hits'];
            $result['count'] = $data['total']['value'];
            $result['page'] = ceil($result['count'] / $row);
        }
        return $result;
    }

    /**
     * 判断文档是否存在
     * @param string $id
     * @return bool
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function exists($id) {
        $url = "{$this->host}/{$this->index}/{$this->type}/{$id}";
        curl_setopt_array($this->curl, [
            CURLOPT_NOBODY        => true,
            CURLOPT_URL           => $url,
            CURLOPT_CUSTOMREQUEST => 'HEAD',
        ]);
        curl_exec($this->curl);
        $resInfo = curl_getinfo($this->curl);
        return !empty($resInfo) && $resInfo['http_code'] == 200;
    }

    /**
     * 服务是否运行
     * @param string $host
     * @return bool
     * @author WilsonWong<wangwx@snqu.com>
     */
    public function ping($host = null) {
        $host = $host ?: $this->host;
        curl_setopt_array($this->curl, [
            CURLOPT_NOBODY        => true,
            CURLOPT_URL           => $host,
            CURLOPT_CUSTOMREQUEST => 'HEAD',
            CURLOPT_NOSIGNAL      => 1,
            CURLOPT_TIMEOUT_MS    => 200,
        ]);
        curl_exec($this->curl);
        $resInfo = curl_getinfo($this->curl);
        return !empty($resInfo) && $resInfo['http_code'] == 200;
    }

    /**
     * 排序方式
     * @param array $sort
     * @return $this
     */
    public function sort($sort) {
        $this->sort = $sort;
        return $this;
    }

    /**
     * 要查找的条数
     * @param int $limit
     * @return $this
     */
    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 要跳过的条数，数据太多可能会有性能问题
     * @param int $skip
     * @return $this
     */
    public function skip($skip) {
        $this->skip = $skip;
        return $this;
    }

    /**
     * 重设查询参数
     * @return $this
     */
    public function reset() {
        $this->filter = [];
        $this->sort = [];
        $this->limit = 0;
        $this->skip = 0;
        return $this;
    }
}
