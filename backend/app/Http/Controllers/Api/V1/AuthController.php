<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Авторизация
 * 
 * API для авторизации пользователей и управления токенами доступа
 */
class AuthController extends Controller
{
    /**
     * Авторизация пользователя
     * 
     * Авторизует пользователя по email и паролю, возвращает токен доступа
     * 
     * @unauthenticated
     * @bodyParam email string required Email пользователя Example: api@test.com
     * @bodyParam password string required Пароль пользователя Example: password123
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "API Test User",
     *       "email": "api@test.com"
     *     },
     *     "token": "1|abcdef123456..."
     *   }
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["Неверные учетные данные."]
     *   }
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверные учетные данные.'],
            ]);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Выход пользователя
     * 
     * Удаляет текущий токен доступа пользователя
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Выход выполнен успешно"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Выход выполнен успешно',
        ]);
    }

    /**
     * Получить информацию о текущем пользователе
     * 
     * Возвращает данные авторизованного пользователя
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "API Test User",
     *       "email": "api@test.com"
     *     }
     *   }
     * }
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }
} 