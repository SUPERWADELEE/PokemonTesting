<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Tymon\JWTAuth\Facades\JWTAuth;


/**
 * @group Auth
 * Operations related to auth.
 */
class AuthController extends Controller
{
    /**
     * 登入
     * 
     * 此端點允許用戶使用他們的電子郵件和密碼來登入系統，並返回一個JWT令牌。
     * 
     * 會確認信箱是否已驗證，如果尚未驗證返回錯誤
     * 會設置cookie和http only來傳token
     *
     * @param Request $request 請求物件，包含電子郵件和密碼
     * 
     * @bodyParam email string required 用戶的電子郵件地址。example：user@example.com
     * @bodyParam password string required 用戶的密碼。Example: e123456
     *
     * @response 200 {
     *   "message": "Login successful",
     *   "user": "Authenticated user object"
     * }
     * 
     * @response 401 {
     *   "error": "Invalid credentials"
     * }
     * 
     * @response 403 {
     *   "error": "信箱未驗證"
     * }
     */
    public function login(Request $request, AuthService $authService)
    {
        // 驗證表單
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // 使用 AuthService 處理登錄
        $loginResponse = $authService->attemptLogin($credentials);

        if (array_key_exists('error', $loginResponse)) {
            // 如果有錯誤，返回相應的錯誤信息
            return response()->json(['error' => $loginResponse['error']], $loginResponse['status']);
        }

        // 生成響應
        $response = response()->json([
            'message' => config('success_messages.LOGIN_SUCCESS'),
        ], $loginResponse['status']);




        // 創建 cookie
        $cookie = cookie('jwt', $loginResponse['token'], $loginResponse['token_ttl'], null, null, false, true);

        // 將 cookie 附加到響應
        return $response->cookie($cookie);
    }




    /**
     * 登出
     * 
     * 此端點允許已經登入的用戶登出，它會使當前的JWT令牌失效。
     
     *成功要把cookie清掉。 200.  
     *
     * @response 200 {
     *   "message": "Successfully logged out"
     * }
     * 
     * @response 500 {
     *   "message": "Failed to logout"
     * }
     */
    public function logout(Request $request, AuthService $authService)
    {
        // 从 cookie 中取出 token
        $token = $request->cookie('jwt');

       
        // 使用 AuthService 来处理注销逻辑
        $logoutResponse = $authService->logout($token);

        // 检查是否有错误信息
        if (isset($logoutResponse['error'])) {
            return response()->json([
                'error' => $logoutResponse['error']
            ], $logoutResponse['status']);
        }

        $response = response()->json([
            'message' => $logoutResponse['message']
        ], $logoutResponse['status']);

        // 如果注销成功，清除 cookie
        if (isset($logoutResponse['cookie'])) {
            $response = $response->withCookie($logoutResponse['cookie']);
        }

        return $response;
    }
}
