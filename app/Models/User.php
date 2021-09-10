<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Response;
use App\Models\User;
use App\Models\UserVerify;
use Auth;
use Illuminate\Support\Facades\Validator;
use Hash;
use DB;
use Str;
use Mail;



class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'number',
        'email',
        'password',
        'otp',
        'otp_flag',
        'otp_time',
        'status',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'otp_flag',
        'otp_time',
        'company_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    function loginWithPassword($request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $data = $validator->errors();
            return array("data" => $data, "status_code" => 204);
        } else {

            $email = $request->email;
            $password = $request->password;

            $auth_data = [
                'email' => $email,
                'password' => $password
            ];


            if (Auth::attempt($auth_data)) {
                if (Auth::user()->is_email_verified == 0) {

                    DB::beginTransaction();
                    try {
                        $token = Str::random(64);

                        UserVerify::where('user_id', Auth::user()->id)->delete();
                        UserVerify::create([
                            'user_id' => Auth::user()->id,
                            'token' => $token
                        ]);

                        Mail::send('mail.signup_success', ['token' => $token], function ($message) use ($request) {
                            $message->to(Auth::user()->email);
                            $message->subject('Email Verification Mail');
                        });

                        Auth::logout();
                        DB::commit();
                        return array("message" => 'You need to confirm your account. We have sent you an activation code again, please check your email.', "data" => [], "status_code" => 200);
                    } catch (\Throwable $th) {
                        DB::rollback();
                        return array("message" => 'something went wrong in sending reverification link', "data" => [], "status_code" => 204);
                    }
                }
                $data = Auth::user()->toArray();
                // dd($data);
                return array("data" => $data, "status_code" => 200);
            } else {
                return array("message" => "Email Or Password Not Matched", "data" => [], "status_code" => 204);
            }
        }
    }

    function sendOtp($request)
    {

        $validator = Validator::make($request->all(), [
            'number' => 'required|numeric|exists:users',
        ]);

        if ($validator->fails()) {
            $data = $validator->errors();
            return array("data" => $data, "status_code" => 204);
        } else {
            $number = $request->number;
            $this->where('number', $number)->update([
                'otp' => otp(4),
                'otp_flag' => 0,
                'otp_time' => date('Y-m-d H:i:s'),
            ]);
            return array('message' => 'Otp Sent Successfully', 'data' => [], 'status_code' => 200);
        }
    }

    public function verifyOtp($request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|numeric|exists:users',
            'otp' => 'required|numeric|digits:4',
        ]);

        if ($validator->fails()) {
            $data = $validator->errors();
            return array("data" => $data, "status_code" => 204);
        } else {
            $otp = $request->otp;
            $number = $request->number;

            $matchOtp = $this->where('number', $number)->where('otp', $otp)->first();
            if (@$matchOtp && !empty($matchOtp)) {
                $db_otp = $matchOtp->otp;
                $otp_time = $matchOtp->otp_time;
                $otp_flag = $matchOtp->otp_flag;
                $otpTimestamp = explode(' ', $otp_time);
                $currentTimestamp = explode(' ', date('Y-m-d H:i:s'));
                $currentdate = $currentTimestamp[0];
                $otpdate = $otpTimestamp[0];
                if ($otp_flag == 1) {
                    return array('message' => 'OTP already used, please try again', "data" => [], "status_code" => 204);
                }
                if ($otpdate == $currentdate) {
                    $datetime1 = strtotime($otp_time);
                    $datetime2 = strtotime(date('Y-m-d H:i:s'));
                    $interval  = abs($datetime2 - $datetime1);
                    $minutes   = round($interval / 60);
                    if ($minutes > 15) {
                        return array('message' => 'OTP is expired, please try again', "data" => [], "status_code" => 204);
                    } else {
                        $data = array(
                            'otp_flag' => 1,
                        );
                        self::where('number', '=', $number)->update($data);
                        $data = array('id' => $matchOtp->id, 'role_id' => $matchOtp->role_id, 'first_name' => $matchOtp->first_name, 'number' => $number);
                        $result = [
                            'status' => 200,
                            'message' => 'OTP verified Successfully',
                            'data' => $data
                        ];
                        return $result;
                    }
                } else {
                    $result = [
                        'status' => 204,
                        'message' => 'OTP is expired, please try again',
                    ];
                    return $result;
                }
            } else {
                $result = [
                    'status' => 204,
                    'message' => 'OTP is not valid, please try again',
                ];
                return $result;
            }
        }
    }

    public function signup($request)
    {

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|regex:/^[a-zA-Z]+$/u',
            'last_name' => 'required|regex:/^[a-zA-Z]+$/u',
            'number' => 'required|numeric|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $data = $validator->errors();
            return array("data" => $data, "status_code" => 204);
        } else {
            $first_name = $request->first_name;
            $last_name = $request->last_name;
            $email = $request->email;
            $number = $request->number;
            $password = $request->password;
            // $device_type = $request->device_type;
            // $device_token = $request->device_token;



            DB::beginTransaction();
            try {
                $data = [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'number' => $number,
                    'password' => Hash::make($password),
                    // 'company_id' => $company_id,
                    // 'device_type' => $device_type,
                    // 'device_token' => $device_token,
                ];

                $user = User::create($data)->toArray();

                $token = Str::random(64);

                UserVerify::where('user_id', $user['id'])->delete();
                UserVerify::create([
                    'user_id' => $user['id'],
                    'token' => $token
                ]);

                Mail::send('mail.signup_success', ['token' => $token], function ($message) use ($request) {
                    $message->to($request->email);
                    $message->subject('Email Verification Mail');
                });

                DB::commit();
                return array("message" => "Signup Successfully, verify your email", "data" => $data, "status_code" => 200);
            } catch (\Exception $ex) {
                DB::rollback();
                return array("message" => 'Something went wrong. Please try again', "data" => [], "status" => 204);
            }
        }
    }

    function verifyEmail($token)
    {
        $verifyUser = UserVerify::where('token', $token)->first();

        $message = 'Sorry your email cannot be identified.';

        if (!is_null($verifyUser)) {
            $user = $verifyUser->user;

            if (!$user->is_email_verified) {
                $verifyUser->user->is_email_verified = 1;
                $verifyUser->user->status = 1;
                $verifyUser->user->save();
                $message = "Your e-mail is verified. You can now login.";
            } else {
                $message = "Your e-mail is already verified. You can now login.";
            }
        }
        return array("message" => $message, "data" => [], "status" => 200);
    }

    function sendForgetPasswordLink($request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
        ]);

        if ($validator->fails()) {
            $data = $validator->errors();
            return array("data" => $data, "status_code" => 204);
        } else {

            $token = Str::random(64);

            DB::table('password_resets')->where('email', $request->email)->delete();
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            Mail::send('email.forgetPassword', ['token' => $token], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Reset Password');
            });

            return array("message" =>  'We have e-mailed your password reset link!', "data" => [], "status" => 200);
        }
    }

    function changePassword($request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $data = $validator->errors();
            return array("data" => $data, "status_code" => 204);
        } else {

            $updatePassword = DB::table('password_resets')
                ->where([
                    'email' => $request->email,
                    'token' => $request->token
                ])
                ->first();

            if (!$updatePassword) {
                return array("message" => 'Invalid token!', "data" => [], "status" => 204);
            }

            $user = User::where('email', $request->email)
                ->update(['password' => Hash::make($request->password)]);

            DB::table('password_resets')->where(['email' => $request->email])->delete();

            return array("message" => 'Your password has been changed!', "data" => [], "status" => 200);
        }
    }
}
