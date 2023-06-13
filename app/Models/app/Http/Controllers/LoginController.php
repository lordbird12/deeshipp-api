<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use \Firebase\JWT\JWT;

class LoginController extends Controller
{
    public $key = "police_key";

    public function genToken($id, $name)
    {
        $payload = array(
            "iss" => "police",
            "aud" => $id,
            "lun" => $name,
            "iat" => Carbon::now()->timestamp,
            "exp" => Carbon::now()->timestamp + 86400,
           // "exp" => Carbon::now()->timestamp + 31556926,
            "nbf" => Carbon::now()->timestamp,
        );

        $token = JWT::encode($payload, $this->key);
        return $token;
    }

    public function checkLogin(Request $request)
    {
        $header = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $header);

        try {

            if ($token == "") {
                return $this->returnError('Token Not Found', 401);
            }

            $payload = JWT::decode($token, $this->key, array('HS256'));
            $payload->exp = Carbon::now()->timestamp + 86400;
            $token = JWT::encode($payload, $this->key);

            return response()->json([
                'code' => '200',
                'status' => true,
                'message' => 'Active',
                'data' => [],
                'token' => $token,
            ], 200);
        } catch (\Firebase\JWT\ExpiredException $e) {

            list($header, $payload, $signature) = explode(".", $token);
            $payload = json_decode(base64_decode($payload));
            $payload->exp = Carbon::now()->timestamp + 86400;
            $token = JWT::encode($payload, $this->key);

            return response()->json([
                'code' => '200',
                'status' => true,
                'message' => 'Token is expire',
                'data' => [],
                'token' => $token,
            ], 200);

        } catch (Exception $e) {
            return $this->returnError('Can not verify identity', 401);
        }
    }


    
    public function login(Request $request)
    {
    
        if (!isset($request->email)) {
            return $this->returnErrorData('Emailไม่ถูกต้อง', 404);
        } else if (!isset($request->password)) {
            return $this->returnErrorData('passwordไม่ถูกต้อง', 404);
        }

       
        $user = User::with('permission')
            ->where('email', $request->email)
            ->where('password', md5($request->password))
            ->where('status', 1)
            ->first();

        if ($user) {

            //log
            $username = $user->user_id;
            $log_type = 'เข้าสู่ระบบ';
            $log_description = 'ผู้ใช้งาน ' . $username . ' ได้ทำการ ' . $log_type;
            $this->Log($username, $log_description, $log_type);
            //

            return response()->json([
                'code' => '200',
                'status' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'data' => $user,
                'token' => $this->genToken($user->id, $user),
            ], 200);
        } else {
            return $this->returnError('รหัสผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง', 401);
        }

    }

}
