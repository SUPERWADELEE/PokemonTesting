<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @group Payment
 * Operations related to payments.
 * 
 * @authenticated
 */
class PaymentController extends Controller
{
    /**
     * 請求藍星金流結帳頁面
     * 
     * 主要使用者點選結帳後可以請求這個API，然後藍星金流會發結帳頁面給使用者，
     * 此方法主要功能如下：
     * 1. 驗證當前使用者。
     * 2. 更新與當前使用者關聯的購物車項目的結帳狀態。
     * 3. 生成與藍星金流相關的支付參數（包括加密和哈希）。
     * 4. 返回支付參數，以便前端將使用者重定向到藍星金流的支付頁面。
     * 
     * @apiGroup 支付
     * 
     * @bodyParam totalPrice float required 購物車中所有商品的總價格。
     * 
     * @param \Illuminate\Http\Request $request 用戶的HTTP請求。
     * 
     * @return \Illuminate\Http\JsonResponse 返回包含支付參數的JSON響應。
     */

    public function checkout(Request $request)
    {
        // 获取当前经过身份验证的用户
        $user = JWTAuth::parseToken()->authenticate();

        // 获取用户的ID
        $userId = $user->id;

        // 更新与该用户关联的购物车项目的结账状态
        CartItem::where('user_id', $userId)->update(['checkout_status' => 'checked']);

        $totalPrice = $request->input('totalPrice');

        $key = config('payment.key');
        $iv = config('payment.iv');
        $mid = config('payment.id');
        $notifyURL = config('payment.notify_url');
        $returnURL = config('payment.return_url');
        $payment = config('payment.payment_url');

        $tradeInfo = http_build_query(array(
            'MerchantID' => $mid,
            'RespondType' => 'JSON',
            'TimeStamp' => time(),
            'Version' => '2.0',
            'MerchantOrderNo' => "test0315001" . time(),
            'Amt' => $totalPrice,
            'ItemDesc' => 'test',
            'NotifyURL' => $notifyURL,
            'ReturnURL' => $returnURL,
        ));

        $encodedData = bin2hex(openssl_encrypt(
            $tradeInfo,
            "AES-256-CBC",
            $key,
            OPENSSL_RAW_DATA,
            $iv
        ));


        $hashs = "HashKey=" . $key . "&" . $encodedData . "&HashIV=" . $iv;
        $hash = strtoupper(hash("sha256", $hashs));



        return response()->json([
            'payment_url' => $payment,
            'mid' => $mid,
            'edata1' => $encodedData,
            'hash' => $hash
        ]);
    }
}
