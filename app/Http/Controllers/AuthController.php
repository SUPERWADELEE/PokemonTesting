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
        'message' => "Login successful",
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
    public function logout(Request $request)
    {
        try {
            // 從 cookie 中取出 token
            $token = $request->cookie('jwt');
    
            // 將 token 設為無效
            JWTAuth::setToken($token)->invalidate();
    
            // 清除客戶端的 JWT cookie
            $cookie = Cookie::forget('jwt');
    
            $response = response()->json(['message' => "Successfully logged out"]);
    
            // 將清除 cookie 的響應附加到返回的響應中
            return $response->withCookie($cookie);
        } catch (\Exception $e) {
            return response()->json(['message' => "Failed to logout"], 500);
        }
    }
    



}
