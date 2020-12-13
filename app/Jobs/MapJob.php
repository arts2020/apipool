<?php

namespace App\Jobs;

use App\User;
use App\Models\OrderMapTrack;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MapJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public $tries = 5;

    public $timeout = 60;

    public $order_id;
    /**
     * 创建一个新的任务实例
     *
     * @param  User  $user
     * @return void
     */
    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * 执行任务
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle(OrderMapTrack $orderMapTrack)
    {
        $mapTrack = $orderMapTrack->get(OrderMapTrack::KEY.$this->order_id) ?? array();

        Log::info('reids队列执行');

        if(empty($mapTrack) || $mapTrack == 'null'){
            $mapTrack = "";
        }else{
            $mapTrack = \GuzzleHttp\json_encode($mapTrack);
        }

        if(!empty($mapTrack)){
            $data = array(
                'order_id'=>$this->order_id,
                'detail'=>$mapTrack,
                'created_at'=>time()
            );
            $orderMapTrack->insert($data);
            $orderMapTrack->del(OrderMapTrack::KEY.$this->order_id);
        }

    }
}
