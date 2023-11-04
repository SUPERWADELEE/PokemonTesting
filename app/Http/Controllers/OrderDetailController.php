<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderDetailRequest;
use App\Http\Resources\OrderDetailResource;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Race;

/**
 * @group OrderDetail
 * Operations related to orderDetais.
 * 
 * @authenticated
 */
class OrderDetailController extends Controller
{
    /**
     * 訂單細節新增
     * 
     * @bodyParam order_id int required 訂單的ID。
     * @bodyParam race_id int required 產品(Race)的ID。
     * 
     * 根據指定的race_id從使用者的購物車中提取數量，並根據race的價格計算出單價和總價。
     * 建立訂單細節後，減少相應的庫存，並清除使用者購物車中的該race項。
     * 
     * @param \App\Http\Requests\OrderDetailRequest $request 使用者輸入的請求數據。
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Order detail created successfully."
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Validation error.",
     *   "errors": {
     *     "order_id": [
     *       "The order id field is required."
     *     ],
     *     "race_id": [
     *       "The race id field is required."
     *     ]
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Race not found."
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "Server error."
     * }
     * 
     * @return void
     */
    public function store(OrderDetailRequest $request)
    {
        $validatedData = $request->validated();

        $race = Race::find($validatedData['race_id']);
        $unit_price = $race->price;
        $user = auth()->user();

        // 从关联的购物车项中获取特定的race_id的quantity
        $cartItem = $user->cartItems()->where('race_id', $validatedData['race_id'])->first();
        $quantity = $cartItem ? $cartItem->quantity : 0;  // 如果找不到购物车项，我们默认quantity为0

        OrderDetail::create([
            'order_id' => $validatedData['order_id'],
            'race_id' => $validatedData['race_id'],
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'subtotal_price' => $quantity * $unit_price
        ]);

        // 扣减库存
        if ($race && $quantity) {
            $race->stock -= $quantity;
            $race->save();
        }

        // 清空当前用户的特定race_id的购物车项
        $user->cartItems()->where('race_id', $validatedData['race_id'])->delete();
    }


    /**
     * 訂單細節列表
     * 
     * 用於獲取指定訂單的所有訂單詳情。
     * 
     * @urlParam order required 訂單的ID。用於指定查詢哪一個訂單的詳情。
     * 
     * @response {
     *     "data": [
     *         {
     *             "id": "訂單詳情的唯一ID",
     *             "race_name": "相應的種族名稱",
     *             "quantity": "訂購數量",
     *             "unit_price": "單位價格",
     *             "subtotal_price": "小計價格"
     *         },
     *         ...其他訂單詳情的資料
     *     ]
     * }
     * 
     * @param \App\Models\Order $order 指定的訂單。自動解析訂單ID並獲取對應的訂單模型實例。
     * 
     * @return \Illuminate\Http\Resources\Json\ResourceCollection 返回指定訂單的所有訂單詳情的集合。
     */
    public function index(Order $order)
    {
        // 直接從訂單中獲取所有的訂單詳情
        $orderDetails = $order->orderDetails()->with('race')->get();

        return OrderDetailResource::collection($orderDetails);
    }


    /**
     * 顯示指定的訂單詳情。
     *
     * @apiGroup 訂單詳情
     * 
     * @urlParam orderDetail required 訂單詳情的ID。
     * 
     * 此方法还验证指定的订单详情是否属于当前登录的用户。
     * 
     * @param \App\Models\OrderDetail $orderDetail 指定的訂單詳情。
     * 
     * @return \App\Http\Resources\OrderDetailResource|Illuminate\Http\Response 返回指定的訂單詳情或403錯誤。
     */

    public function show(OrderDetail $orderDetail)
    {
        $user = auth()->user();
        // 验证此 OrderDetail 是否属于当前用户
        if ($orderDetail->order->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new OrderDetailResource($orderDetail);
    }
}
