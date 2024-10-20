<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use App\Models\User;
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Users=User::all();
        return response()->json(['Users'=>$Users]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $data=$request->validated();
        $user=User::create($data);
        return response()->json([
            'status' =>'success',
            'message'=>'The user had been created successfully',
            'user'=>$user
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $User=User::findOrFail($id);
        return response()->json($User);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $User=User::findOrFail($id);
        $data=$request->validated();
        $User::update($data);
        return response()->json([
            'status' =>'success',
            'message'=>'The user had been updated successfully',
            'user'=>$User
        ],201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user=User::findOrFail($id);
        $user->delete();
        return response()->json([
            'status' =>'success',
            'message'=>'The user had been deleted successfully',
            'user'=>$user
        ],204);
    }
}
