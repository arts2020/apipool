<?php
/**
 * 订单
 * @author: alan
 * @Date: 17/5/2
 */
namespace App\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Order"))
 */
class Order extends Base {

    protected $table = 'order';

    protected $primarykey = 'id';

    public $mp_status = [
        1  => '待付款',        //初始状态
        3  => '待沟通',        //用户付款完成
        5  => '待确定',     //客服派单
        7  => '待出发',        //护士确认接单
        9  => '已出发',        //护士确认出发
        11 => '服务中',        //用户确认到达
        13 => '服务完成',      //用户确定完成
        15 => '已评价',        //用户评价
        17 => '已取消',
        19 => '已删除',
        21 => '退款中',
        23 => '退款失败',
        25 => '退款成功',
    ];

    public $show_status = [
        1  => '待付款',
        3  => '待服务',
        5  => '待服务',
        7  => '待服务',
        9  => '待服务',
        11 => '服务中',
        13 => '待评价',
        15 => '交易完成',
        17 => '交易取消',
        19 => '已删除',
        21 => '退款中',
        23 => '退款失败',
        25 => '退款成功',
    ];

    public $activity = [
        1  => '搭配套餐',
        2  => '预购套餐',
        3  => '首单优惠',
        4  => '打折',
        5  => '优惠券',
    ];

    protected $fillable = [
        'hospital_id',
        'keshi_id',
        'order_no',
        'parent_order_no',
        'order_fee',
        'discount_fee',
        'pay_fee',
        'current_fee',
        'service_fee',
        'added_service',
        'added_service_money',
        'award_fee',
        'shangmen_fee',
        'status',
        'order_type',
        'product_id',
        'activity_type',
        'activity_id',
        'member_id',
        'patient_id',
        'nurse_id',
        'book_time',
        'order_time',
        'pay_type',
        'pay_time',
        'paidan_time',
        'order_address',
        'give_service',
        'medical',
        'lng',
        'lat',
        'medical_desc',
        'medical_special_desc',
        'patient_phone',
        'change_book_time',
        'change_order_address',
        'sub_price',
        'cancel_reason',
        'cancel_time',
        'delete_flag',
        'delete_time',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'status'=> 'int',
    ];

    // public function hospital()
    // {
    //     return $this->belongsTo('\App\Models\Hospital', 'hospital_id', 'id');
    // }

    public function items(){
        return $this->hasMany('\App\Models\OrderItems','order_id');
    }

    public function patient(){
        return $this->hasOne('\App\Models\MemberPatient','id','patient_id');
    }

    public function product(){
        return $this->hasOne('\App\Models\Product','id','product_id');
    }

    // public function member(){
    //     return $this->hasOne('\App\Models\Member','id','member_id');
    // }

    // public function nurse(){
    //     return $this->hasOne('\App\Models\Nurse','id','nurse_id');
    // }

    public function comment(){
        return $this->hasOne('\App\Models\OrderComment','order_id');
    }

    public function yizhu(){
        return $this->hasOne('\App\Models\MemberPatientYizhu','id','yizhu_id');
    }

    /*
     * 套餐类数据获取父订单信息
     */
    public function parent(){
        return $this->hasOne('\App\Models\Order','order_no','parent_order_no');
    }

    /*
    * 套餐类数据获取子订单信息
    */
    public function childrens(){
        return $this->hasMany('\App\Models\Order','parent_order_no','order_no');
    }

    //优惠活的关联数据
    public function taocan()
    {
        return $this->hasOne('\App\Models\Taocan', 'id','activity_id');
    }

    //优惠券关联数据
    public function ticket()
    {
        return $this->hasOne('\App\Models\Ticket', 'id','activity_id');
    }

    //获取订单患者数据
    public function orderPatient(){
        return $this->hasOne('\App\Models\OrderPatient','order_id','id');
    }

    //获取赠送服务数据
    public function giveService(){
        return $this->hasOne('\App\Models\GiveService','id','give_service');
    }

    //获取赠送服务数据
    public function services(){
        return $this->hasMany('\App\Models\GiveService','product_id','product_id');
    }

    //意向护士
    public function orderNurse(){
        return $this->hasMany(OrderNurse::class,'order_id','id');
    }

    /**
     * 获取附属订单数据
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasManySubOrders()
    {
        return $this->hasMany(OrderSub::class,'order_id','id')->where("order_sub.is_del" , "=" , "1");
    }

    /**
     * 获取产品的耗材数据
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasManyHaocai()
    {
        return $this->hasMany(ProductHaoCai::class,'product_id','product_id')->where("product_haocai.is_del" , "=" , "1");
    }

    /**
     * 获取病情描述的图片
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasOrderDescImg()
    {
        return $this->hasMany(OrderDescImg::class,'order_id','id');
    }

    /**
     * 获取病情描述的语音
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasOrderDescVideo()
    {
        return $this->hasMany(OrderDescVideo::class,'order_id','id');
    }

    public function hasManyJiangmen()
    {
        return $this->hasMany(OrderJiangmen::class,'order_no','order_no');
    }

    public function getChangeBookTimeAttribute($date)
    {
        if ($date > 0)
            return date("Y-m-d H:i", $date);
    }

    public function getBookTimeAttribute($date)
    {
        if ($date > 0)
            return date("Y-m-d H:i", $date);
    }

}