<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use \Firebase\JWT\JWT;

class CheckJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public $key = "police_key";

    public function handle($request, Closure $next)
    {
        try {
            $header = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $header);
//dd($header );
            if (!$token) {
                return response()->json([
                    'code' => '401',
                    'status' => false,
                    'message' => 'Token Not Found',
                    'data' => [],
                ], 401);
            }

            $payload = JWT::decode($token, $this->key, array('HS256'));

            //dd($payload);
            $request->request->add([
                'login_id' => $payload->aud,
                'login_by' => $payload->lun,
                
            ]);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'code' => '401',
                'status' => false,
                'message' => 'Token is expire',
                'data' => [],
            ], 401);
        } catch (Exception $e) {
            return response()->json([
                'code' => '401',
                'status' => false,
                'message' => 'Can not verify identity',
                'data' => [],
            ], 401);
        }

        return $next($request);
    }
}
