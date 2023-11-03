<?php

namespace App\Http\Controllers;

use App\Events\TransactionSuccess;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\NewebpayMpgResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group PaymentResponse
 * 
 * 此為藍星金流在結帳完之後會通知後台的，接收結果用的API
 *
 * 
 * @authenticated
 */

class PaymentsResponseController extends Controller
{
   /**
     * 藍星金流結帳完後結果返回確認，寄通知信給使用者
     * 
     * 此方法主要功能如下：
     * 1. 從請求中提取支付相關的資訊。
     * 2. 驗證支付回調的數字簽名以確保資料的完整性。
     * 3. 根據支付結果記錄相關的日誌資訊。
     * 4. 在支付成功後向使用者發送通知郵件。
     * 
     * @apiGroup 支付
     * 
     * @bodyParam TradeInfo string required 支付相關的加密資料。
     * @bodyParam TradeSha string required 支付回調的數字簽名。
     * 
     * @param \Illuminate\Http\Request $request 使用者的HTTP請求，包含支付相關的資訊。
     * 
     * @return void
     */
    public function notifyResponse(Request $request)
    {
        $tradeInfo = $request->input('TradeInfo');
        $tradeSha = $request->input('TradeSha');
        $tradeData = new NewebpayMpgResponse($tradeInfo);

        // 比對hash(數位簽章)
        if ($this->isHashMatched($tradeInfo, $tradeSha)) {
            Log::info('Payment Callback Received:', ['Result' => 'Hash Matched']);
        }

        // 觀察結帳狀態是否成功
        if (!$tradeData->isSuccess()) {
            Log::info('Payment Callback Received:', ['Result' => 'Failure']);
        }

        // 得到結帳的人的email
        $emails = $this->getEmailsOfCheckedOutUsers();


        $userData = [
            'name' => 'wade',
        ];

        // 寄信
        event(new TransactionSuccess($emails, $userData));
    }

    private function isHashMatched($tradeInfo, $tradeSha)
    {
        $key = config('payment.key');
        $iv = config('payment.iv');
        $hashs = "HashKey=$key&$tradeInfo&HashIV=$iv";
        $hash = strtoupper(hash("sha256", $hashs));

        return $hash == $tradeSha;
    }


    public function getEmailsOfCheckedOutUsers()
    {
        $checkedOutUserId = CartItem::where('checkout_status', 'checked')->pluck('user_id')->unique()->first();
        Log::info('Payment Callback Received:', ['userId' => $checkedOutUserId]);

        // 如果没有找到用户，您可能想要记录一个错误或执行其他逻辑
        if ($checkedOutUserId === null) {
            Log::error('No checked out user found.');
            return null;
        }

        $email = User::where('id', $checkedOutUserId)->value('email');
        Log::info('Payment Callback Received:', ['email' => $email]);
        CartItem::where('user_id', $checkedOutUserId)->update(['checkout_status' => 'finished']);

        return $email;
    }
}
