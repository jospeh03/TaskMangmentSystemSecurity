<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
class AuthController extends Controller
{
protected $authService;
    public function __construct(AuthService $authService)
    {
    $this->authService = $authService;
    }
/**
* Register a User.
*
* @param RegisterRequest $request
* @return JsonResponse
*/
    public function register(RegisterRequest $request): JsonResponse
    {
    $user = $this->authService->register($request->validated());
    return response()->json($user, 201);
    }
/**
* Get a JWT via given credentials.
*
* @param LoginRequest $request
* @return JsonResponse
*/
    public function login(LoginRequest $request): JsonResponse
    {
    $token = $this->authService->login($request->validated());
    if (!$token) {
    return response()->json(['error' => 'Unauthorized'], 401);
    }
    return $this->respondWithToken($token);
    }
/**
* Get the authenticated User.
*
* @return \Illuminate\Http\JsonResponse
*/
    public function info()
    {
    return response()->json(auth()->user());
    }
/**
* Log the user out (Invalidate the token).
*
* @return \Illuminate\Http\JsonResponse
*/
    public function logout()
    {
    auth()->logout();
    return response()->json(['message' => 'Successfully logged out']);
    }
/**
* Refresh a token.
*
* @return \Illuminate\Http\JsonResponse
*/
    public function refresh()
    {
    return $this->respondWithToken(auth()->refresh());
    }
/**
* Get the token array structure.
*
* @param string $token
*
* @return \Illuminate\Http\JsonResponse
*/
    protected function respondWithToken($token)
    {
    return response()->json([
    'access_token' => $token,
    'token_type' => 'bearer',
    'expires_in' => auth()->factory()->getTTL() * 60
    ]);
    }
}
