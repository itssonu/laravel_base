<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Response;
use App\Models\User;
use Auth;
use DB;

class Generalcontroller extends Controller
{
    public function signup(Request $request)
    {
        $user = new User();
        $return = $user->signup($request);
        exit(json_encode($return));
    }


    public function login(Request $request)
    {
        $login_type = $request->login_type;


        if ($login_type == 1) {   // email password login
            $user = new User();
            $return = $user->loginWithPassword($request);
            exit(json_encode($return));
        } elseif ($login_type == 2) {  //  send otp 
            $user = new User();
            $return = $user->sendOtp($request);
            exit(json_encode($return));
        } elseif ($login_type == 3) {
            $user = new User();
            $return = $user->verifyOtp($request);
            exit(json_encode($return));
        }
    }

    public function verifyUser($token)
    {
        $user = new User();
        $return = $user->verifyEmail($token);
        exit(json_encode($return));
    }

    public function sendForgetPasswordLink(Request $request)
    {
        $user = new User();
        $return = $user->sendForgetPasswordLink($request);
        exit(json_encode($return));
    }

    public function changePassword($token = null)
    {
        $email = DB::table('password_resets')->where('token', $token)->first();
        if (empty($email)) {
            $return = array('message' => 'Invalid token, Please request new password reset!', 'data' => [], 'status' => 204);
            exit(json_encode($return));
        }
        return view('common.changePassword', ['token' => $token, 'email' => $email->email]);
    }

    public function changePasswordGet($token = null)
    {
        $email = DB::table('password_resets')->where('token', $token)->first();
        if (empty($email)) {
            $return = array('message' => 'Invalid token, Please request new password reset!', 'data' => [], 'status' => 204);
            exit(json_encode($return));
        }
        return view('common.changePassword', ['token' => $token, 'email' => $email->email]);
    }

    public function changePasswordPost(Request $request)
    {
        $user = new User();
        $return = $user->changePassword($request);
        exit(json_encode($return));
    }
}
