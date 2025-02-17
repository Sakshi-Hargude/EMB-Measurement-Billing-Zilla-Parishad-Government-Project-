<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Fundhdm;
use App\Models\Subdivm;
Use App\Models\User;
use App\Models\Userperm;
use App\Models\Workmaster;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class UserpermController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
    **/

    public function ajaxRequestcreateview(Request $request)
        {
            if($request->fundeda != ''){
                $request->fundeda;
                $rsFundedList =  DB::table('fundhdms')
                ->select(DB::raw("CONCAT(F_H_CODE,' ',Fund_Hd_M) AS Fund_Hd_M"))
                ->where('F_H_CODE', 'like', '%' . $request->fundeda . '%')
                ->orWhere('Fund_Hd_M', 'like', '%' . $request->fundeda . '%')
                ->get();
                return response()->json(array('msg'=> $rsFundedList), 200);
            } else{
                return response()->json(array('msg'=> null), 200);
            }
        }


    // Get Selected User Permission
    public function ajaxRequestUserPermission(Request $request)
        {
            if($request->puid){ // Selected User ID
             $rsUserPermissionsList =  DB::table('userperms')
                ->select('userperms.User_Id','userperms.Unique_Id','userperms.F_H_CODE','userperms.Sub_Div_Id','userperms.Work_Id','subdivms.Sub_Div_M')
                ->leftJoin('subdivms', 'userperms.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                ->where('userperms.User_Id', '=', $request->puid)
                ->where('userperms.Removed', '=', 1)
                ->get();
                return response()->json(array('msg'=> $rsUserPermissionsList), 200);
            }else{
                return response()->json(array('msg'=> null), 200);
            }

        }

        // Remove User Permission

    public function ajaxRemoveUserPermission(Request $request)
    {
        if($request->puid){ // Selected User ID
            $rsRemoveUserPermission =  DB::table('userperms')
            ->where('Unique_Id', '=', $request->puid)
            ->update(['Removed' => 0]);

            if($request->puserid){ // Selected User ID
                $rsUserPermissionsList =  DB::table('userperms')
                ->select('userperms.User_Id','userperms.Unique_Id','userperms.F_H_CODE','userperms.Sub_Div_Id','userperms.Work_Id','subdivms.Sub_Div_M')
                ->leftJoin('subdivms', 'userperms.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                ->where('userperms.User_Id', '=', $request->puserid)
                ->where('userperms.Removed', '=', 1)
                ->get();
                   return response()->json(array('msg'=> $rsUserPermissionsList), 200);
               }else{
                   return response()->json(array('msg'=> null), 200);
               }

        }

    }

    public function createview()
       {
            // login user session Data----------------------------
            $usercode = auth()->user()->usercode;
            $divid = auth()->user()->Div_id;
            $subdivid = auth()->user()->Sub_Div_id;
            $usertypes= auth()->user()->usertypes;
            //dd($usertypes);
            // login user session Data----------------------------

            $rsDiv=DB::table('divisions')->where('div_id' , $divid)->get(); 
   $rsDesignation=DB::table('designations')->get();
   $rsAllUserList=[];
if($usertypes === 'PA' || $usertypes === 'EE')
{
     $rsAllUserList = User::get()->where('Div_id' , $divid)->whereIn('usertypes', ['EE','PA','admin','audit','DYE','PO', ]);
    // dd($rsAllUserList);
    $rsSubDevisionList = DB::table('subdivms')
    ->where('Div_Id','=',$divid)->get();

     $rsWorkMaster =DB::table('workmasters')
     ->where('Div_Id','=',$divid)->get();

}
      
if($usertypes === 'DYE')
{
     $rsAllUserList = User::get()->where('Sub_Div_id','=',$subdivid)->whereIn('usertypes', ['DYE','SO','SDE']);

    
     $rsSubDevisionList = DB::table('subdivms')
     ->where('Div_Id','=',$divid)->where('Sub_Div_Id','=',$subdivid)->get();
     $rsWorkMaster =DB::table('workmasters')
     ->where('Sub_Div_Id','=',$subdivid)->get();
}

     $rsFundedList = DB::table('fundhdms')->get();
    
    
            return view('User.addperm',['rsUser'=>$rsAllUserList,'rsFund'=>$rsFundedList,'rsSubDiv'=>$rsSubDevisionList,'rsWorkMaster'=>$rsWorkMaster]);
       }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function InsertTB(Request $request)
    {
        $data = $request->input();
 // dd($data);
        $request->validate([
            'User_Id' => 'required|string',
            // 'F_H_CODE' => 'required|string',
            // 'Sub_Div_Id'=> 'required|string',
            // 'Work_Id' => 'required|string',
        ]);
        for ($i = 0; $i < count($request->F_H_CODE); $i++){ 
 //dd($request->F_H_CODE);
            // Auto Increament Userpermission Id
                $SQLNewPKID = DB::table('userperms')
                ->selectRaw('Unique_Id + 1 as Unique_Id')
                ->orderBy('Unique_Id', 'desc')
                ->limit(1)
                ->get();
                $RSNewPKID = json_decode($SQLNewPKID);
                if(isset($RSNewPKID[0]->Unique_Id) && !empty($RSNewPKID[0]->Unique_Id)){
                    $PrimaryNumber=$RSNewPKID[0]->Unique_Id;
                }else{
                    $PrimaryNumber=1;
                }

                $codeToCheck = $request->F_H_CODE[$i];

                $fundData = DB::table('fundhdms')
                ->where('F_H_CODE', 'LIKE', $codeToCheck . '%')
                ->get();
                
                //dd($fundData);
                //$Period_From = Input::get('Period_From');
                $objUserPermission = new Userperm();
                $objUserPermission->Unique_Id  = $PrimaryNumber;
                $objUserPermission->User_Id = $data['User_Id'];
               // Assuming $i represents the index and $request->F_H_CODE is an array
               if($fundData->isEmpty())
               {
                $objUserPermission->F_H_CODE = "all";
               }
                elseif ($request->F_H_CODE[$i] === "ALL") {
                    $objUserPermission->F_H_CODE = "all"; // Set the value to 'all' if input is 'ALL'
                } 
                else {
                    $objUserPermission->F_H_CODE = $request->F_H_CODE[$i]; // Set the value to the input otherwise
                }
                $objUserPermission->Sub_Div_Id = $request->Sub_Div_Id[$i];
                $objUserPermission->Work_Id = $request->Work_Id[$i];
                $objUserPermission->save();
        }

        $userid= $data['User_Id'];
        //dd($userid);
        return redirect('addperm')->with(['status',"Permission Grant Successfully" , 'User_Id' => $userid]);
    }
}
