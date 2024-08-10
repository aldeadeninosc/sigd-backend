<?php

namespace App\Http\Controllers;


use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{

    public function register(RegisterRequest $request) : JsonResponse
    {
        try{
            $user = User::create([
                'name' => $request->name,
                'last_name' => $request->lastname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type' => $request->user_type
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'user' => $user,
                'token' => $token
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Registration failed', "exception" => $e->getMessage()], 500);
        }
    }

    public function login(LoginRequest $request) : JsonResponse
    {
        try{
            $credentials = $request->only('email', 'password');

            if(!$token = Auth::attempt($credentials)){
                return response()->json(['error', 'Unauthorized'], 401);
            }

            $user = auth()->user();

            $token = JWTAuth::fromUser($user, ['user_id' => $user->id]);

            return $this->respondWithToken($token);

        }catch(Exception $e){
            return response()->json(['error' => 'Login failed', "exception" => $e->getMessage()], 500);
        }
    }

    protected function respondWithToken($token) : JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function logout() : JsonResponse
    {
        try{
            Auth::logout();
    
            return response()->json(['message' => 'Successfully logged out']);
        }catch(Exception $e){
            return response()->json(['error' => 'Logout failed'], 500);
        }
    }

    public function getCurrentUser() : JsonResponse
    {
        try{
            return response()->json(Auth::user());
        } catch (Exception $e) {
            return response()->json(['error' => 'Could not fetch user details'], 500);
        }
    }
    
    public function getUsers(Request $request) : JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            $query = User::query();
            
            $users = $query->paginate($perPage, ['*'], 'page', $page);

            $response = [
                'items' => $users->items(),
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'totalCounts' => $users->total(),
            ];

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json(['error' => 'Could not fetch users', 'exception' => $e->getMessage()], 500);
        } 
    }
    
    public function updateUser(Request $request, $id)
    {
    try {
        $user = User::findOrFail($id);

        $user->name = $request->input('name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');
        
        if(!empty($request->input('user_type'))){
            $user->user_type = $request->input('user_type');
        }
        
        if(!empty($request->input('password'))){
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return response()->json(['message' => 'Usuario actualizado correctamente', 'user' => $user], 200);
    } catch (Exception $e) {
        return response()->json(['error' => 'Fallo actualizacion', 'exception' => $e->getMessage()], 500);
    }
    }
    
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
        
            return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Borrado correctamente', 'exception' => $e->getMessage()], 500);
        }
    }

}
