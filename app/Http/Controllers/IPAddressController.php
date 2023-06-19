<?php

namespace App\Http\Controllers;

use App\Models\IPAddress;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class IPAddressController extends Controller
{
    public function index()
    {
        JWTAuth::parseToken()->authenticate();
        $ipAddresses = IPAddress::all();
        return response()->json($ipAddresses);
    }

    public function store(Request $request)
    {
        JWTAuth::parseToken()->authenticate();
        $userip = $request->ip_address;
        $routerIp = preg_replace('/\.[0-9]+$/', '.1', $userip);
        $routeripAddress = IPAddress::create([
            'router_address' => $routerIp,
            'location' => $request->location
        ]);

        return response()->json($routeripAddress, 201);
    }

    public function show(IPAddress $ipAddress)
    {
        JWTAuth::parseToken()->authenticate();
        return response()->json($ipAddress);
    }

    public function update(Request $request, IPAddress $ipAddress)
    {
        JWTAuth::parseToken()->authenticate();
        $validatedData = $request->validate([
            'ip' => 'required|ip',
        ]);

        $ipAddress->update($validatedData);
        return response()->json($ipAddress);
    }

    public function destroy(IPAddress $ipAddress)
    {
        JWTAuth::parseToken()->authenticate();
        $ipAddress->delete();
        return response()->json(null, 204);
    }
}
