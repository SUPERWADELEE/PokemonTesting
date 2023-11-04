<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequst;
use App\Http\Resources\OrderResource;
use App\Models\Order;

/**
 * @group Order
 * Operations related to orders.
 * 
 * @authenticated
 */
class OrderController extends Controller
{
    /**
     * 訂單列表
     * 獲取當前登錄用戶的所有訂單列表。
     * 
     * @response {
     *     "data": [
     *         {
     *             "id": "訂單的唯一ID",
     *             "user_name": "下訂單的用戶名稱",
     *             "total_price": "訂單的總價格",
     *             "payment_method": "訂單的付款方式 (例如: credit_card, cash_on_delivery)",
     *             "payment_status": "訂單的付款狀態 (例如: paid, unpaid, canceled)"
     *         },
     *         ...其他訂單的資料
     *     ]
     * }
     * 
     * @return \Illuminate\Http\Resources\Json\ResourceCollection 返回當前使用者的所有訂單的集合。
     */
    public function index()
    {
        $user = auth()->user();
        $orders = $user->orders;
        return OrderResource::collection($orders);
    }
}
