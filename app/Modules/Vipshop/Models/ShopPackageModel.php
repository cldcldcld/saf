<?php

namespace App\Modules\Vipshop\Models;

use App\Modules\Shop\Models\ShopModel;
use App\Modules\User\Model\UserDetailModel;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Vipshop\Models\PackageModel;

class ShopPackageModel extends Model
{
    
    protected $table = 'shop_package';

    protected $primaryKey = 'id';

    protected $fillable = [

        'shop_id', 'package_id', 'uid', 'username', 'duration', 'price', 'start_time','end_time','status','created_at','updated_at','privileges_package'

    ];

    public function shop()
    {
        return $this->belongsTo('App\Modules\Shop\Models\ShopModel', 'id', 'shop_id');
    }


    
    static function packageInfo(){
        $packageInfo = PackageModel::withTrashed()->select('id','title')->orderBy('list','asc')->get()->toArray();
        return $packageInfo;
    }


    
    static function shopPackageList($data){
        $shopPackageList = ShopPackageModel::join('shop','shop_package.shop_id','=','shop.id')->select('shop_package.*','shop.shop_name');
        if(isset($data['user_name'])){
            $shopPackageList = $shopPackageList->where('shop_package.username','like','%'.$data['user_name'].'%');
        }
        if(isset($data['shop_name'])){
            $shopPackageList = $shopPackageList->where('shop.shop_name','like','%'.$data['shop_name'].'%');
        }
        if(isset($data['package_id']) && $data['package_id']){
            $shopPackageList = $shopPackageList->where('shop_package.package_id',intval($data['package_id']));
        }
        if(isset($data['status']) && $data['status']){
            switch($data['status']){
                case '1':
                    $status = 0;
                    $shopPackageList = $shopPackageList->where('shop_package.status',$status);
                    break;
                case '2':
                    $status = 1;
                    $shopPackageList = $shopPackageList->where('shop_package.status',$status);
                    break;
            }

        }
        $shopPackageList = $shopPackageList->orderBy('shop_package.created_at','desc')->paginate(10);
        if($shopPackageList->total()){
            $package_id = array_pluck($shopPackageList->items(),'package_id');
            $packageInfo = PackageModel::withTrashed()->whereIn('id',$package_id)->select('id','title')->get()->toArray();
            $packageInfo = collect($packageInfo)->pluck('title','id')->all();
            foreach($shopPackageList->items() as $k=>$v){
                if(in_array($v->package_id,array_keys($packageInfo))){
                    $v->package_name = $packageInfo[$v->package_id];
                }else{
                    $v->package_name = '';
                }

            }
        }
        return $shopPackageList;

    }


    
    static function shopPackageInfo($id){
        $shopPackageInfo = ShopPackageModel::find(intval($id));
        if(empty($shopPackageInfo)){
            return false;
        }
        $shopInfo = ShopModel::where('id',$shopPackageInfo->shop_id)->select('shop_name')->first();
        if(empty($shopInfo)){
            return false;
        }
        $userInfo = UserDetailModel::where('uid',$shopPackageInfo->uid)->select('mobile')->first();
        if(empty($userInfo)){
            return false;
        }
        $packageInfo = PackageModel::withTrashed()->where('id',$shopPackageInfo->package_id)->select('title')->first();
        if(empty($packageInfo)){
            return false;
        }
        $shopPackageInfo->shop_name = $shopInfo->shop_name;
        $shopPackageInfo->mobile = $userInfo->mobile;
        $shopPackageInfo->package_name = $packageInfo->title;
        $privileges_ids = json_decode($shopPackageInfo->privileges_package,true);
        $privileges = PrivilegesModel::whereIn('id',$privileges_ids)->select('title','desc')->get()->toArray();
        $shopPackageInfo->privileges = $privileges;
        return $shopPackageInfo;
    }


    
    static function updateEndTime($id,$end_time){
        $shopPackage = ShopPackageModel::find(intval($id));
        if(empty($shopPackage)){
            return 2;                        
        }
        if($end_time < $shopPackage->end_time){
            return 3;                       
        }
        $data = [
            'end_time' => $end_time,
            'updated_at' => date('Y-m-d H:i:s',time())
        ];
        if($end_time > $shopPackage->end_time && $shopPackage->status == 1){
            $data['status'] = 0;
        }
        $res = $shopPackage->update($data);
        return $res?1:0;                  
    }
}