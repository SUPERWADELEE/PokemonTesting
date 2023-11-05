<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTAuthCookieMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 尝试从 cookie 中获取 token
        $token = $request->cookie('jwt');
    
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        } 

        try {
            // 使用 token 认证用户
            $user = JWTAuth::setToken($token)->authenticate();
            // 如果认证通过，将用户信息设置到请求对象中
            Auth::setUser($user);
        } catch (JWTException $e) {
            // 如果在此过程中出现任何异常，返回错误响应
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // 继续处理请求
        return $next($request);
    }
    
}
