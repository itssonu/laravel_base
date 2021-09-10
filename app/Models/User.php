<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Response;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Validator;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'firts_name',
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
}
