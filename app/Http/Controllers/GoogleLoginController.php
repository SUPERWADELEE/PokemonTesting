<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @group GoogleLogin
 * Operations related to googleLogin.
 */

class GoogleLoginController extends Controller
{


    /**
     * 重定向到Google进行身份验证
     *
     * 调用此端点後端會回傳授權的url，前端再將用户重定向到Google的登录页面进行身份验证。
     * 成功后，Google会将用户重定向回应用的回调URL也就是以下的API。
     *
     * 
     *
     * @response 200 {
     *  "url": "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=..."
     * }
     * @response 500 {
     *  "error": "Unable to redirect to Google. Please try again later."
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirectToProvider()
    {

    //     try {
    //         return Socialite::driver('google')->redirect();
    //     } catch (\Exception $e) {
    //         // Log::error('Error redirecting to Google: ' . $e->getMessage());

    //         return response()->json(['error' => '無法重定向到Google。請稍後再試。'], 500);
    //     }
    }



    /**
     * 處理從 Google 第三方認證服務頁面返回的回調。
     *
     * 此處就是可以從google拿到使用者資訊並儲存在資料庫
     * 然後反回帶token的cookie
     * @response 200 {
     *     "message": "Login successful via Google",
     *     "user": "使用者的資料"
     * }
     * 
     * @return \Illuminate\Http\Response 用 JSON 格式返回的成功消息、JWT 令牌和使用者資訊。
     */


    public function handleProviderCallback()
    {
        $socialUser = Socialite::driver('google')->user();

        // 使用 email 查找本地使用者
        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName(),
                // 你也可以保存其他從 Google 取得的資訊，例如頭像、ID 等
                // 'avatar' => $socialUser->getAvatar(),
                // 'google_id' => $socialUser->getId(),

                // 你可能需要一個隨機密碼，因為某些驗證方法需要它，即使使用者從未使用它
                'password' => bcrypt(Str::random(16))
            ]
        );

        // 為用戶生成 JWT
        $token = JWTAuth::fromUser($user);

        // 返回令牌給前端
        return response()->json([
            'message' => 'Login successful via Google',
            'token' => $token,
            'user' => $user
        ], 200);
        // return redirect("/?token={$token}");
    }
}
