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
     * 訂單新增
     * 
     * @bodyParam total_price float required 訂單的總價格。
     * @bodyParam payment_status string required 訂單的付款狀態，只能是 'paid', 'unpaid', 或 'canceled'。
     * @bodyParam payment_method string required 訂單的付款方式，只能是 'credit_card' 或 'cash_on_delivery'。
     * 
     * @response 201 {
     *     "id": "Newly created order ID"
     * }
     * 
     * @response 400 {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "field_name": ["The field_name is required."]
     *     }
     * }
     * 
     * @response 401 {
     *     "message": "Unauthenticated."
     * }
     * 
     * @response 500 {
     *     "message": "Server Error"
     * }

     * 
     * @param \App\Http\Requests\OrderRequest $request 使用者輸入的請求數據。
     * 
     * @return \Illuminate\Http\Response 返回新創建的訂單ID的JSON響應或失敗的錯誤訊息。
     */
    public function store(OrderRequst $request)
    {
        $validatedData = $request->validated();
        $userId = auth()->user()->id;
        $order = Order::create([
            'user_id' => $userId,
            'total_price' => $validatedData['total_price'],
            'payment_status' => $validatedData['payment_status'],   // 自动应用 setPaymentStatusAttribute
            'payment_method' => $validatedData['payment_method'],   // 自动应用 setPaymentMethodAttribute
            'status' => 'Pending'
        ]);
        return response()->json(['id' => $order->id], 201);
    }

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
