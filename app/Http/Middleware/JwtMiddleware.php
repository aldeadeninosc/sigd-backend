<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired', "exception" => $e->getMessage()], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token is invalid', "exception" => $e->getMessage()], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token not provided', "exception" => $e->getMessage()], 401);
        } catch (Exception $e) {
            return response()->json(['error' => 'Unauthorized', "exception" => $e->getMessage()], 401);
        }
        return $next($request);
    }
}
