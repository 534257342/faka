<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */
namespace App\Scopes\User;
trait UserDriverScope
{

    public $names = [
        0 =>[
            'name'=>'司机总数',
            'twoName'=>[
                0=>['name'=>'自营'],
                1=>['name'=>'外包'],
            ]
        ],
        1 => [
            'name'=>'上班司机',
            'twoName'=>[
               0=>['name'=>'休息司机'],
            ]
        ],
        2 => ['name'=>'司机异常超区'],
        3 => ['name'=>'司机异常停留'],
        4 => ['name'=>'司机1小时无任务'],
        5 => ['name'=>'司机今日任务小于5'],
    ];
    public function scopeOrderCount($query,$start_time='',$end_time=''){
        if($start_time && $end_time){
            return $this->scopeTimeCount($query,$start_time,$end_time);
        }else{
            return $this->scopeNotTimeCount($query);
        }
    }

    /**
     * @param $query
     * @param $start_time
     * @param $end_time
     * @return mixed
     * 带时间参数的统计
     */
    public function scopeTimeCount($query,$start_time,$end_time){
        return $query->withCount(["pick",'delivery','transfer',
            'pick'=>function($query)use($start_time,$end_time){
                return $query->where('type',1)
                    ->where('complete_time','>=',$start_time)
                    ->where('complete_time','<=',$end_time);
            },
            'delivery'=>function($query)use($start_time,$end_time){
                return $query->where('type',2)
                    ->where('complete_time','>=',$start_time)
                    ->where('complete_time','<=',$end_time);
            },
            'transfer'=>function($query)use($start_time,$end_time){
                return $query->where('type',3)
                    ->where('complete_time','>=',$start_time)
                    ->where('complete_time','<=',$end_time);
            }
        ]);

    }

    /**
     * @param $query
     * @return mixed
     * 不带时间参数的统计
     */
    public function scopeNotTimeCount($query){
        return $query->withCount(["pick",'delivery','transfer',
            'pick'=>function($query){
                return $query->where('type',1);
            },
            'delivery'=>function($query){
                return $query->where('type',2);
            },
            'transfer'=>function($query){
                return $query->where('type',3);
            }
        ]);
    }

    public function scopeDriverCount($model){
        $site = getCurrentSiteId();
        $model = $model->where('site',$site);
        $names = $this->names;
        foreach ($names as $key=>$name){

            if(isset($name['twoName'])){
                foreach ($name['twoName'] as $tk=>$tValue){
                    $twoCount = $this->scopeTwoCount($model,$key,$tk);
                    $name['twoName'][$tk]['count'] = $twoCount;
                }

            }

            $count = $this->scopeCountName($model,$key);
            $name['count'] = $count;
            $names[$key] = $name;
        }
        return $names;
    }

    protected function scopeCountName($model,$key){
        $count = 0;
        switch ($key){
            case 0:
                $count = (clone $model)->count();
                break;
            case 1:
                $count = (clone $model)->whereHas('driverSign')->count();
                break;
            case 2:

                break;
            case 3:

                break;
            case 4:

                break;
            case 5:
                $count = (clone $model)->has('driverMission','<',5)->count();
                break;
        }

        return $count;
    }

    protected function scopeTwoCount($model,$key,$tk){
        if($key == 0){

            if($tk == 0){

                $count = (clone $model)->where('type',1)->count();

            }else{
                $count = (clone $model)->where('type','>',1)->count();
            }

        }else{
            $count = (clone $model)->whereHas('restDriver')->count();
        }
        return $count;
    }
}