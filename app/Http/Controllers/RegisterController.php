<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


/**
 * @group Register
 * Operations related to register.
 */
class RegisterController extends Controller
{
    /**
     * 處理新使用者的註冊並寄送註冊信。
     *
     * 此方法會驗證輸入的資料，並在成功驗證後在`users`表中創建一個新的使用者紀錄。
     * 之後，它會觸發一個`Registered`事件，並返回一個成功的響應，包括新創建的使用者的資料。
     *
     * @bodyParam name string required 使用者的名字。示例：John Doe
     * @bodyParam email string required 使用者的電子郵件地址。必須是唯一的並且符合電子郵件格式。示例：john.doe@example.com
     * @bodyParam password string required 使用者的密碼。必須至少有6個字符長並且與`password_confirmation`參數匹配。示例：password123
     * @bodyParam password_confirmation string required 密碼確認。必須與`password`參數匹配。示例：password123
     *
     * @response 201 {
     *   "message": "User registered successfully!",
     *   "user": {
     *     "name": "John Doe",
     *     "email": "john.doe@example.com",
     *     // other user fields...
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": [
     *       "The email has already been taken."
     *     ],
     *     // other validation errors...
     *   }
     * }
     */
    public function register(Request $request)
    {

        // 1. 驗證輸入
        // 每個email在users表單都是唯一的
        // 密碼需要做確認
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // 將使用者資料輸入資料庫, 目前role的部分預設為user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'user'
        ]);

        event(new Registered($user));

        return response(['message' => 'User registered successfully!', 'user' => $user], 201);
    }


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

        // 到這一步就去將他的user表的email欄位標注日期
        $user->markEmailAsVerified();
            // event(new Verified($user));
        return redirect('https://wade.monster');
        

        
    }
}
