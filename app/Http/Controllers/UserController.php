<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Hash;
use Illuminate\Support\Arr;


class UserController extends Controller
{

    function __construct()
    {
        $array=array();
        $routename="users";
        $array=$this->getMiddleRoute($routename);
        $index= implode("|",$array);  

            $this->middleware('permission:'.$index, ['only' => ['index','store']]); 
            $this->middleware('permission:'.$array['create'], ['only' => ['create','store']]);
            $this->middleware('permission:'.$array['edit'],  ['only' => ['edit','update']]);
            $this->middleware('permission:'.$array['delete'], ['only' => ['destroy']]);    
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $LUser=array();
        $user="users";
        $LUser=$this->getPermissionRoute($user);
        $users=User::orderBy('id','DESC')->paginate(5);
        return view('pages.users.index',compact('users','LUser'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
           $roles=Role::pluck('name','name')->all();
           return view('pages.users.create',compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
     $role= $request->input('roles');

    //  print_r($role);
        $this->validate($request,[
            'name'=>'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
            'roles' => 'required'
        ]);

        $input=$request->all();
        $input['password']= Hash::make($input['password']);

        $user=User::create($input);
        $user->assignRole($role);

        return redirect()->route('users.index')->with('success','User created successfully');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user=User::find($id);

        return view('pages.users.show',compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user= User::find($id);
        $roles=Role::pluck('name','name')->all();
        $userRole= $user->roles->pluck('name','name')->all();

        return view('pages.users.edit',compact('user','roles','userRole'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request,[
            'name'=>'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' =>'same:confirm-password',
            'roles' => 'required'
        ]);

        $input=$request->all();
        if(!empty($input['password'])){
            $input['password']= Hash::make($input['password']);
        }else{
            $input=Arr::except($input,array('password'));
        }

        $user=User::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();

        $user->assignRole($request->input('roles'));

        return redirect()->route('users.index')->with('success','User updated successfully');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::find($id)->delete();

        return redirect()->route('users.index')->with('success','User deleted successfully');
    }
}
