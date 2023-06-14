<?php

namespace App\Http\Controllers;

use App\Models\IPAddress;
use Illuminate\Http\Request;

class IPAddressController extends Controller
{
    public function index()
    {
        $ipAddresses = IPAddress::all();
        return response()->json($ipAddresses);
    }

    public function store(Request $request)
    {
        $ipAddress = IPAddress::create([
            'ip_address' => $request->ip_address,
            'location' => $request->location,
        ]);

        return response()->json($ipAddress, 201);
    }

    public function show(IPAddress $ipAddress)
    {
        return response()->json($ipAddress);
    }

    public function update(Request $request, IPAddress $ipAddress)
    {
        $validatedData = $request->validate([
            'ip' => 'required|ip',
        ]);

        $ipAddress->update($validatedData);
        return response()->json($ipAddress);
    }

    public function destroy(IPAddress $ipAddress)
    {
        $ipAddress->delete();
        return response()->json(null, 204);
    }
}
