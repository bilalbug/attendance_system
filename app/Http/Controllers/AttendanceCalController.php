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


    public function StartTimer(Request $request)
    {
        $ip = $request->ip();
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $ip);
        $ipAddress = IPAddress::where('router_address', $routerIp)->first();

        if ($ipAddress) {
//            $ipUser = TimewithIP::where("ip_address",$ip)->first();
            $ipUser = TimewithIP::where("ip_address",$ip)->orderBy('created_at', 'desc')->first();
//            if($ipUser->outtime && empty($ipUser->intime))
            if(!empty($ipUser->intime) and empty($ipUser->outtime))
            {
                return response()->json(['message'=>"You're already logged in!"]);
            }
            else{
                TimewithIP::create(["ip_address"=>$ip, "intime"=>Carbon::now()]);
                $status = "You're connected, time started!";
                $dateAndTime = Carbon::now();
                $startTime = $dateAndTime->toTimeString();
                $this->starttime = $startTime;
                $location = $ipAddress->location;
            }

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

    public function StopTimer(Request $request)
    {
        $ip = $request->ip();
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $ip);
        $ipAddress = IPAddress::where('router_address', $routerIp)->first();

        if ($ipAddress) {
            $ipUser = TimewithIP::where("ip_address",$ip)->orderBy('created_at', 'desc')->first();

            if ($ipUser->outtime===null){
                $ipUser->outtime = Carbon::now();
                $ipUser->save();
                $outtime = Carbon::now();
                $total_minutes = $outtime->diffInMinutes($ipUser->intime);
                $ipUser->working_minutes = $total_minutes;
                $ipUser->save();
            }
            else{
                return response()->json(['message'=>"You're already logged out!"]);
            }

            $status = "You're timer is stop!";
            $dateAndTime = Carbon::now();
            $endtime = $dateAndTime->toTimeString();
            $location = $ipAddress->location;
            return response()->json([
                'status' => $status,
                'IPAddress' => $ip,
                'EndTime' => $endtime,
                'minutes' => $total_minutes,
                'location' => $location]);
        }
        else {
            return response('IP Address not found!');
        }
    }

    public function TimeCalculator(Request $request)
    {

    }
}

