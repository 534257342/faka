<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */

namespace App\Models\Auth;

use App\Models\Express\ExpressChannel;
use App\Models\Express\ExpressFleet;
use App\Models\Express\ExpressLayer;
use App\Models\Express\ExpressProduct;
use App\Models\Express\ExpressSpot;
use App\Models\Express\ExpressTeam;
use App\Models\System\SystemSite;
use Larfree\Models\Api;
use App\Scopes\Auth\AuthScopeScope;

class AuthScope extends Api {
    use AuthScopeScope;

    protected $casts = [
        'param' => 'array',
    ];


    public function buildProductSql() {
        $expressProducts = ExpressProduct::withoutGlobalScope('site')->select('id', 'title', 'site')->get();
        if (@$expressProducts) {
            return $this->preInfo($expressProducts, 'authProduct', '产品', 'Express.ExpressOrder');
        } else {
            return '没有产品可以生成';
        }
    }


    public function buildCitySql() {
        $citys = SystemSite::select('id', 'name')->get();
        if (@$citys) {
            return $this->preInfo($citys, 'authCity', '城市', 'Express.ExpressOrder', 0);
        } else {
            return '没有城市可以生成';
        }
    }


    public function buildChanelSql() {
        $channel = ExpressChannel::withoutGlobalScope('site')->select('id', 'name', 'site')->where('status', 1)->get();
        if (@$channel) {
            foreach ($channel as $k => $item) {
                $data[$k] = [
                    'name'       => '产品:' . $item->name,
                    'scope'      => 'authChannel',
                    'param'      => json_encode([$item->id]),
                    'rule'       => '{}',
                    'status'     => '1',
                    'site'       => $item->site,
                    'created_at' => date('Y-m-d H:i:s'),
                    'model'      => 'Express.ExpressOrder'
                ];
                $userData[$k] = [
                    'name'       => '用户:' . $item->name,
                    'scope'      => 'authChannel',
                    'param'      => json_encode([$item->id]),
                    'rule'       => '{}',
                    'status'     => '1',
                    'site'       => $item->site,
                    'created_at' => date('Y-m-d H:i:s'),
                    'model'      => 'Common.CommonUser'
                ];
            }
            $finalData = array_merge($data, $userData);

            $sure = self::where('name', $finalData[0]['name'])->where('scope', $finalData[0]['scope'])->where('model', $finalData[0]['model'])->first();
            if (!$sure) {
                $res = self::insert($finalData);
                if ($res) {
                    return '渠道 success';
                }
            } else {
                return '渠道已经存在';
            }
        } else {
            return '没有渠道可以生成';
        }

    }


    public function buildLayerSql() {
        $expressLayer = ExpressLayer::withoutGlobalScope('site')->select('id', 'name', 'site')->where('status', 1)->get();
        if (@$expressLayer) {
            return $this->preInfo($expressLayer, 'authLayer', '运力', 'Express.ExpressOrder');
        } else {
            return 'Layer 没有运力可以生成';
        }
    }

    public function buildTeam() {
        $expressLayer = ExpressTeam::withoutGlobalScope('site')->select('id', 'title', 'site')->get();
        if (@$expressLayer) {
            return $this->preInfo($expressLayer, 'authTeam', '连队订单', 'Express.ExpressOrder');
        } else {
            return '没有车队可以生成';
        }
    }

    public function buildFleet() {
        $expressSpot = ExpressFleet::withoutGlobalScope('site')->select('id', 'title', 'site')->get();
        if (@$expressSpot) {
            return $this->preInfo($expressSpot, 'authFleet', '接驳点', 'Express.ExpressOrder');
        } else {
            return '没有车队可以生成';
        }
    }

    public function buildSpot() {
        $expressSpot = ExpressSpot::withoutGlobalScope('site')->select('id', 'name', 'site')->get();
        if (@$expressSpot) {
            return $this->preInfo($expressSpot, 'authSpot', '接驳点', 'Express.ExpressSpot');
        } else {
            return '没有接驳点可以生成';
        }
    }

    public function preInfo($info, $scope, $name, $model, $site = true) {
        foreach ($info as $k => $item) {
            $data[$k] = [
                'name'       => ($item->name ?: $item->title),
                'scope'      => $scope,
                'param'      => json_encode([$item->id]),
                'rule'       => $site === true ? "{}" : $item->id,
                'status'     => '1',
                'site'       => $site === true ? $item->site : $site,
                'created_at' => date('Y-m-d H:i:s'),
                'model'      => $model
            ];
        }
        if (@$data) {
            $sure = self::where('name', $data[0]['name'])->where('scope', $data[0]['scope'])->where('model', $data[0]['model'])->first();
            if (!$sure) {
                self::insert($data);
                return $name . 'success';
            } else {
                return $name . '已经存在';
            }

        }
    }

    public function buildAll() {
        $res1 = $this->buildProductSql();
        $res2 = $this->buildCitySql();
        $res3 = $this->buildChanelSql();
        $res4 = $this->buildLayerSql();
        $res5 = $this->buildFleet();
        $res6 = $this->buildTeam();
        return $res1 . $res2 . $res3 . $res4 . $res5 . $res6;
    }

}
