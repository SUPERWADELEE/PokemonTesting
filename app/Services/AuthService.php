<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function attemptLogin(array $credentials)
    {
        // 檢查用戶是否已經驗證了電子郵箱
        $user = User::where('email', $credentials['email'])->first();
        if ($user && is_null($user->email_verified_at)) {
            return ['error' => config('error_messages.EMAIL_NOT_VERIFIED'),
            'status' => Response::HTTP_FORBIDDEN ];
        }
        

        // 嘗試使用憑證獲取 JWT token
        $token = JWTAuth::attempt($credentials);
        if (!$token) {
            return ['error' => config('error_messages.INVALID_CREDENTIALS'),
            'status' => Response::HTTP_UNAUTHORIZED  ];
        }

        // 獲取 token 的有效期
        $tokenTTL = JWTAuth::factory()->getTTL() * 60; // 轉換為秒

        // 返回 token 和其有效期
        return [
            'token' => $token,
            'token_ttl' => $tokenTTL,
            'status' => Response::HTTP_OK
        ];
    }

    public function logout($token)
    {
        try {
            // 将 token 设置为无效
            JWTAuth::setToken($token)->invalidate();

            // 清除客户端的 JWT cookie
            $cookie = Cookie::forget('jwt');

            return [
                'message' => config("success_messages.LOGOUT_SUCCESS"),
                'status' => Response::HTTP_OK,
                'cookie' => $cookie
            ];
        } catch (\Exception $e) {
            return [
                'error' => config("error_messages.LOGOUTFAILED"),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    }
}
