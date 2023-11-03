<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function login(Request $request)
    {
        // 先針對輸入的部分做驗表單驗證       
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // 根據進來的guard設定,去預先的表單設定查看輸入的資料是否存在
        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => Auth::user()
        ], 200);
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
    public function logout()
    {
        try {
            // 取出token 將其失效
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to logout'], 500);
        }
    }

    /**
     * 註冊email驗證信確認
     * 
    
     * 電子郵件驗證確認
     *
     * 此端點用於確認用戶的電子郵件驗證(和前端較無關聯）。
     * 它會比對提供的hash值和用戶的電子郵件生成的hash值。
     * 如果驗證成功，該用戶的電子郵件將被標記為已驗證，並且將觸發一個已驗證的事件。
     * 系統會將email驗證的日期存入資料庫
     *
     * @param Request $request HTTP請求
     * @param int $id 用戶ID
     * @param string $hash 從驗證郵件中提供的hash值
     * 
     * @throws AuthorizationException 當提供的hash值不匹配時
     * 
     * @response 200 {
     *   "message": "Email verified successfully."
     * }
     * 
     * @response 200 {
     *   "message": "Email already verified."
     * }
     */

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // 此方法通常用來判斷文件是否被串改
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException();
        }

        // 判斷這個email是否已經驗證過
        if ($user->hasVerifiedEmail()) {
            return response(['message' => 'Email already verified.']);
        }

        // 到這一步就去將他的email驗證
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response(['message' => 'Email verified successfully.']);
    }

}
