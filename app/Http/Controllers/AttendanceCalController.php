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
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $clientIp);

        return response()->json([
            'ip' => $clientIp,
            'router' => $routerIp
        ]);
    }

    public function startTimer(Request $request)
    {
        $ip = $request->ip();
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $ip);
        $ipAddress = IPAddress::where('router_address', $routerIp)->first();

        if ($ipAddress) {
            $ipUser = TimewithIP::where('ip_address', $ip)->orderBy('created_at', 'desc')->first();

            if ($ipUser && $ipUser->intime && !$ipUser->outtime) {
                return response()->json(['message' => "You're already logged in!"]);
            } else {
                $timeWithIp = TimewithIP::create([
                    'ip_address' => $ip,
                    'intime' => Carbon::now(),
                    'location' => $ipAddress->location
                ]);

                $status = "You're connected, time started!";
                $startTime = $timeWithIp->intime->toTimeString();
                $location = $ipAddress->location;

                return response()->json([
                    'status' => $status,
                    'IPAddress' => $ip,
                    'StartTime' => $startTime,
                    'location' => $location
                ]);
            }
        } else {
            return response('You are working remote!');
        }
    }

    public function stopTimer(Request $request)
    {
        $ip = $request->ip();
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $ip);
        $ipAddress = IPAddress::where('router_address', $routerIp)->first();

        if ($ipAddress) {
            $ipUser = TimewithIP::where('ip_address', $ip)->orderBy('created_at', 'desc')->first();

            if ($ipUser && $ipUser->outtime === null) {
                $ipUser->outtime = Carbon::now();
                $ipUser->save();

                $outTime = $ipUser->outtime->toTimeString();
                $totalMinutes = $ipUser->outtime->diffInMinutes($ipUser->intime);

                $ipUser->working_minutes = $totalMinutes;
                $ipUser->save();

                $status = "Your timer is stopped!";
                $location = $ipAddress->location;

                return response()->json([
                    'status' => $status,
                    'IPAddress' => $ip,
                    'EndTime' => $outTime,
                    'minutes' => $totalMinutes,
                    'location' => $location
                ]);
            } else {
                return response()->json(['message' => "You're already logged out!"]);
            }
        } else {
            return response('IP Address not found!');
        }
    }

    public function attendanceStatus(Request $request)
    {
        $ip = $request->ip();
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $ip);
        $ipAddress = IPAddress::where('router_address', $routerIp)->first();

        if ($ipAddress) {
            $ipUserSessions = TimewithIP::where('ip_address', $ip)->get();
            $totalMinutes = 0;

            foreach ($ipUserSessions as $session) {
                if ($session->intime && $session->outtime) {
                    $intime = Carbon::parse($session->intime);
                    $outtime = Carbon::parse($session->outtime);
                    $totalMinutes += $outtime->diffInMinutes($intime);
                }
            }

            if ($totalMinutes >= 300 && $totalMinutes <= 450) {
                $dayStatus = 'FullDay';
            } elseif ($totalMinutes >= 180 && $totalMinutes <= 300) {
                $dayStatus = 'HalfDay';
            } else {
                $dayStatus = 'Absent';
            }

            return response()->json([
                'ip' => $ip,
                'totalMinutes' => $totalMinutes,
                'location' => $ipAddress->location,
                'message' => $dayStatus
            ]);
        }

        return response()->json(['message' => 'No IP Address found']);
    }
}
