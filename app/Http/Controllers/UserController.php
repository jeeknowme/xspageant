<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Event;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $user = User::where('role_id','!=',1)->get();
        return view('users.index',['users'=>$user]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('auth.register');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->all(),[

        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        // 
        $user = User::find($user->id);
        return view('users.edit',['users'=>$user]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
        if(Auth::check()):
            if(Auth::user()->role->id == 1) :
                
                $user = User::find($user->id)
                        ->update([
                            'name'=>$request->input('name'),
                            'email'=>$request->input('email'),
                            'role_id'=>$request->input('role_id')
                        ]);

                if($user){
                    return redirect()->route('users.index')
                            ->with('success','User Info Udpated');
                }
            else
                return redirect()->route('users.index')
                        ->with('Error','You do not have permission to edit users');
            endif;
        endif;


        return redirect()->route('users.index')
                        ->with('error','You don\'t have permission to edit users');
            



    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
        $user = User::find($user->id);
        if($user->delete()){
            return redirect()->route('users.index')
                            ->with('success','User removed successfully');
        }
        return redirect()->route('users.index')
                        ->with('error','Error in removing user');
    }

   

   
}
