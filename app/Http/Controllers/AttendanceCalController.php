<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\IPAddress;
use App\Models\TimewithIP;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Jobs\CheckIPContinuity;

class AttendanceCalController extends Controller
{
    public function showIPandRouter(Request $request)
    {
//        JWTAuth::parseToken()->authenticate();
//        $clientIp = dd($request->ip());
        $clientIp = $request->server('REMOTE_ADDR');
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $clientIp);

        return response()->json([
            'ip' => $clientIp,
            'router' => $routerIp
        ]);
    }

    private function startTimerHelper($user, $ip, $location)
    {
        $existingSession = $user->TimewithIP()->whereNotNull('intime')->whereNull('outtime')->first();

        if ($existingSession) {
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
            $continuityCheck = $this->checkIPAddressAndSessionContinuity($user, $ip, $ipAddress->location);
            if ($continuityCheck) {
                return $continuityCheck;
            }
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
        $ipUserSessions = TimewithIP::where('user_id', $user->id)->get();

        $timeByLocation = $this->calculateTimeByLocation($ipUserSessions);
        $totalMinutes = $this->calculateTotalMinutes($ipUserSessions);
        $dayStatus = $this->calculateDayStatus($totalMinutes);

        $response = [
            'ip' => $ip,
            'totalMinutes' => $totalMinutes,
            'message' => $dayStatus,
            'timeByLocation' => $timeByLocation
        ];

        return response()->json($response);
    }

    private function calculateTimeByLocation($ipUserSessions)
    {
        $timeByLocation = [];
        $locations = $ipUserSessions->pluck('location')->unique();

        foreach ($locations as $location) {
            $sessions = $ipUserSessions->where('location', $location);
            $minutes = $this->calculateTotalMinutes($sessions);
            $timeByLocation[$location] = $minutes;
        }

        return $timeByLocation;
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

    private function checkIPAddressAndSessionContinuity($user, $ip, $location)
    {
        $existingSession = $user->TimewithIP()->whereNotNull('intime')->whereNull('outtime')->first();

        if ($existingSession) {
            if ($existingSession->ip_address !== $ip) {
                $existingSession->outtime = Carbon::now();
                $existingSession->save();
                $outTime = $existingSession->outtime->toTimeString();
                $totalMinutes = $existingSession->outtime->diffInMinutes($existingSession->intime);
                $existingSession->working_minutes = $totalMinutes;
                $existingSession->save();

                return response()->json([
                    'message' => "Your session has been stopped due to IP change.",
                    'IPAddress' => $ip,
                    'EndTime' => $outTime,
                    'minutes' => $totalMinutes,
                    'location' => $location
                ]);
            }
        }

        return null;
    }

    public function checkIPContinuity()
    {
        $users = User::all();

        foreach ($users as $user) {
            $lastSession = $user->TimewithIP()->orderBy('created_at', 'desc')->first();
            if ($lastSession && $lastSession->outtime === null) {
                $ip = $lastSession->ip_address;
                $currentIP = $this->getCurrentIPAddress($user);
                $routerIp = preg_replace('/\.[0-9]+$/', '.1', $currentIP);
                $ipAddress = IPAddress::where('router_address', $routerIp)->first();

                if ($ipAddress && $ip !== $currentIP) {
                    $lastSession->outtime = Carbon::now();
                    $lastSession->save();
                    $outTime = $lastSession->outtime->toTimeString();
                    $totalMinutes = $lastSession->outtime->diffInMinutes($lastSession->intime);
                    $lastSession->working_minutes = $totalMinutes;
                    $lastSession->save();

                    $newSession = $user->TimewithIP()->create([
                        'ip_address' => $currentIP,
                        'intime' => Carbon::now(),
                        'location' => $ipAddress->location
                    ]);

                    $status = "Your session has been stopped due to IP change.";
                    $startTime = $newSession->intime->toTimeString();
                    $newIP = $newSession->ip_address;
                    $newLocation = $newSession->location;

                    response()->json([
                        'status' => $status,
                        'IPAddress' => $newIP,
                        'StartTime' => $startTime,
                        'EndTime' => $outTime,
                        'minutes' => $totalMinutes,
                        'location' => $newLocation
                    ]);
                }
            }
        }
    }

    private function getCurrentIPAddress($user)
    {
        // Replace this with your logic to get the current IP address of the user
        // You can use libraries or services to fetch the IP address
        // For example, you can use the following code:
        /*
        $ip = $_SERVER['REMOTE_ADDR'];
        return $ip;
        */

        // For testing purposes, return a dummy IP address
        return '192.168.0.100';
    }
}
