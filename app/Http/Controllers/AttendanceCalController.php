<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\IPAddress;
use App\Models\TimewithIP;

class AttendanceCalController extends Controller
{
    public function showIPandRouter(Request $request)
    {
        $clientIp = $request->ip();

        // Remove the last octet from the IP address
//        $routerIp = preg_replace('/\.[^.]+$/', '', $clientIp);
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $clientIp);
        return response()->json([
            'ip' => $clientIp,
            'router'=> $routerIp
        ]);
    }


    public function StartTime(Request $request)
    {
        $ip = $request->ip();
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $ip);
        $ipAddress = IPAddress::where('ip_address', $ip)->first();

        if ($ipAddress) {

            TimewithIP::create(["ip_address"=>$ip, "intime"=>Carbon::now()]);

            $status = "You're connected, time started!";
            $dateAndTime = Carbon::now();
            $startTime = $dateAndTime->toTimeString();
            $this->starttime = $startTime;
            $location = $ipAddress->location;

            return response()->json([
                'status' => $status,
                'IPAddress' => $ip,
                'StartTime' => $startTime,
                'location' => $location,]);
        }
        else {
            return response('You are working remote!');
        }
    }

    public function EndTime(Request $request)
    {
        $ip = $request->ip();
        $ipAddress = IPAddress::where('ip_address', $ip)->first();

        if ($ipAddress) {

            $ipUser = TimewithIP::where("ip_address",$ip)->first();
            $ipUser->outtime = Carbon::now();
            $ipUser->save();

            $outtime = Carbon::now();
            $total_minutes = $outtime->diffInMinutes($ipUser->intime);
            $ipUser->working_hours = $total_minutes;
            $ipUser->save();


            $status = "You're timer is stop!";
            $dateAndTime = Carbon::now();
            $endtime = $dateAndTime->toTimeString();
            $location = $ipAddress->location;
            return response()->json([
                'status' => $status,
                'IPAddress' => $ip,
                'EndTime' => $endtime,
                'minutes' => $total_minutes,
                'location' => $location,]);
        }
        else {
            return response('IP Address not found!');
        }
    }

    public function TimeCalculator(Request $request)
    {

    }
}

