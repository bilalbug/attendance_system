<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        // TOKEN IS WORKING. IMPLEMENT SPATIE ROLES AND PERMISSIONS

        $user = JWTAuth::parseToken()->authenticate();

        if ($user->hasPermissionTo('read users')) {
            $selectedColumns = ['id', 'username', 'email', 'role', 'created_at'];
            if ($user->hasRole('Super-Admin')) {
                $users = User::select($selectedColumns)->get();
                return response()->json($users);
            } else {
                $users = User::whereDoesntHave('roles', function ($query) {
                    $query->where('name', 'Super-Admin');
                })->select($selectedColumns)->get();
                return response()->json($users);
            }
        } else {
            return response()->json(["message"=>"You do not have sufficient permissions"]);
        }

    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = User::create([
            'email'=>$request->email,
            'password'=>bcrypt($request->password),
        ]);

        return $user;
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $authUser = JWTAuth::parseToken()->authenticate();
        $user = User::findOrFail($id);
        if ($authUser->hasPermissionTo('read user')) {
            if ($authUser->id != $user->id && $authUser->role =='user') {
                return response()->json(["Message"=>"You do not have sufficient permissions to view other users"]);
            }

            if ($authUser->id != $user->id && $authUser->role =='admin' && $user->role =='Super-Admin') {
                return response()->json(["Message"=>"You do not have sufficient permissions to view this user"]);
            }

            $data = $user->only(['username', 'email', "role", "created_at"]);
            return response()->json($data);
        }

        return response()->json(['error'=>'Unauthorized'], 403);
    }

    /**
     * Update a user role
     */
    public function update(Request $request, string $id)
    {
        //
        $authUser = JWTAuth::parseToken()->authenticate();

        if ($authUser->hasRole('Super-Admin')) {
            $request->validate([
                'role'=>'sometimes|in:admin,user'
            ]);

            $user = User::findOrFail($id);
            $data = $request->only('role');
            $user->syncRoles($data['role']);
            $user->role = $data['role'];
            $user->save();
            return response()->json(["Message"=>"Successfully updated user.", "User details"=>$user], 403);

        } else {
            return response()->json(["Message"=>"You do not have sufficient permissions to update roles"], 403);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $authUser = JWTAuth::parseToken()->authenticate();
        $user = User::findOrFail($id);

        if ($authUser->hasPermissionTo('delete users')) {
            if (($user->role === 'Super-Admin' || $user->role == 'admin') && $authUser->role != 'Super-Admin') {
                return response()->json(["Message"=>"You do not have sufficient permissions to delete this user"]);
            }
            $user->delete();
            return response()->json(['Status'=>'S', 'Message'=>'Successfully Deleted']);
        }

        return response()->json(['error'=>'You are unauthorized, or you do not have sufficient permisssions']);
    }
}
