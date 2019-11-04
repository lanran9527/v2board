<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\User;
use App\Models\Plan;
use App\Utils\Helper;

class CheckOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '订单检查任务';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $order = Order::get();
        foreach ($order as $item) {
            switch ($item->status) {
                case 0:
                    if (strtotime($item->created_at) <= (time() - 1800)) {
                        $item->status = 2;
                        $item->save();
                    }
                    break;
                case 1:
                    $this->orderHandle($item);
                    break;
            }
            
        }
    }
    
    private function orderHandle ($order) {
        $user = User::find($order->user_id);
        if (!$user->plan_id || $order->plan_id == $user->plan_id) {
            return $this->buy($order, $user);
        }
    }
    
    private function buy ($order, $user) {
        $plan = Plan::find($order->plan_id);
        $user->transfer_enable = $plan->transfer_enable * 1073741824;
        $user->enable = 1;
        $user->plan_id = $plan->id;
        $user->group_id = $plan->group_id;
        $user->expired_at = $this->getTime($order->cycle, $user->expired_at);
        if ($user->save()) {
            $order->status = 3;
            $order->save();
        }
    }
    
    private function getTime ($str, $timestamp) {
        if ($timestamp < time()) {
            $timestamp = time();
        }
        switch ($str) {
            case 'month_price': return strtotime('+1 month', $timestamp);
            case 'quarter_price': return  strtotime('+3 month', $timestamp);
            case 'quarter_price': return  strtotime('+6 month', $timestamp);
            case 'quarter_price': return  strtotime('+12 month', $timestamp);
        }
    }
}