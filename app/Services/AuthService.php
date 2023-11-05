<?php
namespace App\Services;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function attemptLogin(array $credentials)
    {
        // 檢查用戶是否已經驗證了電子郵箱
        $user = User::where('email', $credentials['email'])->first();
        if ($user && is_null($user->email_verified_at)) {
            return ['error' => '信箱未驗證', 'status' => 403];
        }

        // 嘗試使用憑證獲取 JWT token
        $token = JWTAuth::attempt($credentials);
        if (!$token) {
            return ['error' => "Invalid credentials", 'status' => 401];
        }

        // 獲取 token 的有效期
        $tokenTTL = JWTAuth::factory()->getTTL() * 60; // 轉換為秒

        // 返回 token 和其有效期
        return [
            'token' => $token,
            'token_ttl' => $tokenTTL,
            'status' => 200
        ];
    }
}
