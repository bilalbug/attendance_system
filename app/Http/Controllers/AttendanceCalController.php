<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\IPAddress;
use App\Models\TimewithIP;
use Tymon\JWTAuth\Facades\JWTAuth;

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

    private function startTimerHelper($user, $ip, $location)
    {
        $ipUser = $user->TimewithIP()->where('ip_address', $ip)->orderBy('created_at', 'desc')->first();

        if ($ipUser && $ipUser->intime && !$ipUser->outtime) {
            return response()->json(['message' => "You're already logged in!"]);
        }

        $timeWithIp = $user->TimewithIP()->create([
            'ip_address' => $ip,
            'intime' => Carbon::now(),
            'location' => $location
        ]);

        $status = "You're connected, time started!";
        $startTime = $timeWithIp->intime->toTimeString();

        return response()->json([
            'status' => $status,
            'IPAddress' => $ip,
            'StartTime' => $startTime,
            'location' => $location
        ]);
    }

    public function startTimer(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $ip = $request->ip();
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $ip);
        $ipAddress = IPAddress::where('router_address', $routerIp)->first();

        if ($ipAddress) {
            $ipUser = $user->TimewithIP()->where('ip_address', $ip)->orderBy('created_at', 'desc')->first();
            return $this->startTimerHelper($user, $ip, $ipAddress->location);
        }

        return $this->startTimerHelper($user, $ip, "remote");
    }

    private function stopTimerHelper($ipUser, $ip, $location)
    {
        if ($ipUser && $ipUser->outtime === null) {
            $ipUser->outtime = Carbon::now();
            $ipUser->save();

            $outTime = $ipUser->outtime->toTimeString();
            $totalMinutes = $ipUser->outtime->diffInMinutes($ipUser->intime);

            $ipUser->working_minutes = $totalMinutes;
            $ipUser->save();

            $status = "Your timer is stopped!";

            return response()->json([
                'status' => $status,
                'IPAddress' => $ip,
                'EndTime' => $outTime,
                'minutes' => $totalMinutes,
                'location' => $location
            ]);
        }

        return response()->json(['message' => "You're already logged out!"]);
    }

    public function stopTimer(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $ip = $request->ip();
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $ip);
        $ipAddress = IPAddress::where('router_address', $routerIp)->first();
        $ipUser = $user->TimewithIP()->where('ip_address', $ip)->orderBy('created_at', 'desc')->first();

        if ($ipAddress) {
            return $this->stopTimerHelper($ipUser, $ip, $ipAddress->location);
        }

        return $this->stopTimerHelper($ipUser, $ip, "remote");
    }

    public function attendanceStatus(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $ip = $request->ip();
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $ip);
        $ipAddress = IPAddress::where('router_address', $routerIp)->first();

        if ($ipAddress) {
            $ipUserSessions = TimewithIP::where('user_id', $user->id)->get();
            $totalMinutes = $this->calculateTotalMinutes($ipUserSessions);

            $dayStatus = $this->calculateDayStatus($totalMinutes);

            return response()->json([
                'ip' => $ip,
                'totalMinutes' => $totalMinutes,
                'location' => $ipAddress->location,
                'message' => $dayStatus
            ]);
        }

        $ipUserSessions = TimewithIP::where('user_id', $user->id)->get();
        $totalMinutes = $this->calculateTotalMinutes($ipUserSessions);
        $dayStatus = $this->calculateDayStatus($totalMinutes);

        return response()->json([
            'ip' => $ip,
            'totalMinutes' => $totalMinutes,
            'location' => "remote",
            'message' => $dayStatus
        ]);
    }

    private function calculateTotalMinutes($ipUserSessions)
    {
        $totalMinutes = 0;

        foreach ($ipUserSessions as $session) {
            if ($session->intime && $session->outtime) {
                $intime = Carbon::parse($session->intime);
                $outtime = Carbon::parse($session->outtime);
                $totalMinutes += $outtime->diffInMinutes($intime);
            }
        }

        return $totalMinutes;
    }

    private function calculateDayStatus($totalMinutes)
    {
        if ($totalMinutes >= 300 && $totalMinutes <= 450) {
            return 'FullDay';
        } elseif ($totalMinutes >= 180 && $totalMinutes <= 300) {
            return 'HalfDay';
        }
        return 'Absent';
    }
}
