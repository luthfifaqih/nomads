<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class Main extends Controller
{
    public static function numberToRomanRepresentation()
    {
        $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if ($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }

    public static function handleFcm($limit = 10)
    {
        try {
            $url = 'https://fcm.googleapis.com/fcm/send';
            $headers = array(
                'Authorization:key = AAAAfeLcbYo:APA91bHw_04Mk2MF4G9b__8G8fOP-DKBjcEDbJHIw2q0Sql5SNO5bncxXPTqN6uVwkWDNU9oNT4DQwBi8cmRAkZVYjCUv9g687haSBppvS6RvYqV32y_EYQbD5qFGWzjVb_q776wob3Z',
                'Content-Type: application/json'
            );

            $get_data = DB::table('notifications')->where('is_send', 0)->orderBy('is_urgent', 'DESC')->orderBy('created_at', 'ASC')->limit($limit)->get();
            if (@count($get_data) > 0) {
                $res = array();
                $i = 0;
                foreach ($get_data as $row) {
                    $fields = array(
                        'notification' => [
                            'request_type' => $row->type,
                            'request_time' => date('Y-m-d H:i:s'),
                            'sound' => 'default',
                            'title' => @strip_tags($row->title),
                            'body' => @strip_tags($row->message),
                        ],
                    );
                    $encode_payload = json_decode($row->payload);
                    $fields['data'] = [
                        'url' => url($row->link),
                        'payload' => json_decode($row->payload)
                    ];

                    //get token fcm
                    $user = DB::table('m_admin')->select('fcm')->where('id', $row->user_id)->first();
                    $fields['registration_ids'] = [$user->fcm];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                    $result = curl_exec($ch);
                    curl_close($ch);
                    $res = json_decode($result);

                    //update
                    DB::table('notifications')->where('id', $row->id)->update([
                        'is_send' => '1',
                    ]);
                    $i++;
                }
            }
            return true;
        } catch (\Throwable $error) {
            Log::critical($error);
            return true;
        }
    }

    public static function getIdSessionWithCustom($nameSession)
    {
        return Session::get($nameSession);
    }

    public static function generateRandomString($length = 10, $type = 'normal')
    {
        if ($type === 'normal') {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        } elseif ($type === 'alphabet') {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        } elseif ($type === 'numeric') {
            $characters = '0123456789';
        } else {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function sensor_nik($nik)
    {
        $jumlah_sensor = 6;
        $setelah_angka_ke = 6;

        $censored = mb_substr($nik, $setelah_angka_ke, $jumlah_sensor);

        $nik2 = explode($censored, $nik);

        $nik_new = $nik2[0] . "******" . $nik2[1];

        return $nik_new;
    }

    public static function encodeId($value)
    {
        return Self::generateRandomString(20) . bin2hex($value);
    }

    public static function decodeId($value)
    {
        return hex2bin(substr($value, 20));
    }

    public static function getNameSession()
    {
        return Session::get('loginData')->admin_nama;
    }

    public static function getIdSession()
    {
        return Session::get('loginData')->admin_id;
    }

    public static function getRealIdSession()
    {
        return Session::get('loginData')->id;
    }

    public static function getRoleIDSession()
    {
        return Session::get('loginData')->admin_role;
    }

    public static function getEndWeek()
    {
        $now = Carbon::now('Asia/Jakarta')->addDays(30);
        // $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i');
        $weekEndDate = $now->endOfWeek()->format('Y-m-d');
        return $weekEndDate;
    }
}
