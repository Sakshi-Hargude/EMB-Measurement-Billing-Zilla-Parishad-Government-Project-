<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subdivms;
use Illuminate\Http\Request;
use App\Helpers\PublicDivisionId;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Mail;
use App\Mail\NewUserNotification;


//    user insert, update, delete , view functions
class UserController extends Controller
{
    //Grid Display join users an subdivms table  
    public function indexleftjoin() 
    {

         // Perform a left join between 'users' and 'subdivms' tables based on 'Sub_Div_id'
        $users = DB::table('users')
        ->leftJoin('subdivms','users.Sub_Div_id','=','subdivms.Sub_Div_Id')
        ->select('users.name','users.usertypes','users.Designation','subdivms.Sub_Div_M')
        ->get();

//     // Return the view with the list of users and their associated subdivision details
         return view('viewuser', ['users' => $users]);

     } 

     //retrive the data of user
     public function leftjoinuser(Request $request) 
     {
        //dd($request);

          // Retrieve users based on specific criteria from the 'subdivms' table
        $users = DB::table('subdivms')
        // ->leftJoin('subdivms','users.Sub_Div_id','=','subdivms.Sub_Div_Id')
         ->where('subdivms.Sub_Div_Id',$request->Sub_Div_Id)
         ->where('subdivms.Sub_Div_M',$request->Sub_Div_M)
         ->where('subdivms.designation',$request->designation)
         ->select('subdivms.Sub_Div_Id','subdivms.Sub_Div_M','subdivms.designation')

         ->get();
    //   dd($users);
          // Return the view with the filtered list of users
          return view('user', ['users' => $users]);
    }

    //user add form page open 
    public function addForm()
    {
// dd('ok');
        
        $rsDiv=DB::table('divisions')->get();
        // dd($rsDiv);
        $rsSubDiv=DB::table('subdivms')->get();
        $rsDesignation=DB::table('designations')->get();
        //dd($rsDiv);
        // You can pass any data you need to the view here
        return view('User.add' , compact('rsDiv' , 'rsSubDiv' , 'rsDesignation')); //
    }

    
public function register(Request $request)
{
    $name = $request->input('name');
    $email = $request->input('email');
    $usertypes = $request->input('usertypes');

    $divid = $request->input('Div_id');
    $subdivid = $request->input('Sub_Div_id');
    $designation = $request->input('Designation');

    $mbno = $request->input('mobileno');
    $usernm = $request->input('Usernm');
    $passwrd = $request->input('password');

    $passwrdconfm = $request->input('password_confirmation');
   
 DB::table('users')->insert([

    
 ]);

}


//designation list view function for individual user type
public function FunGetRelatedDesignation(Request $request)
{
     // Extract user type from the request
    $usertypes = $request->input('usertypes');
    // dd($usertypes);
    $rsDesignation = null;

    // Retrieve designations based on user type
    if($usertypes === "EE" || $usertypes === "PA")
    {
    // $rsDesignation = DB::table('designations')
    // ->whereIn('Designation_code', 1)
    // ->get();

    $rsDesignation = DB::table('designations')
    ->select('Designation')
    ->where('Designation_code', 1)
    ->get();
     } 
     elseif ($usertypes === "DYE"){
        // dd('ok');

            // $rsDesignation = DB::table('designations')
            // ->whereIn('Designation_code', 2)
            // ->get();

            $rsDesignation = DB::table('designations')
            ->select('Designation')
            ->where('Designation_code', 2)
            ->get();
             } 

             elseif ($usertypes === "PO"){
                // dd('ok');
        
                    // $rsDesignation = DB::table('designations')
                    // ->whereIn('Designation_code', 2)
                    // ->get();
        
                    $rsDesignation = DB::table('designations')
                    ->select('Designation')
                    ->where('Designation_code', 3)
                    ->get();
                     } 

                     elseif ($usertypes === "SO")
                     {
                        // dd('ok');
                
                            // $rsDesignation = DB::table('designations')
                            // ->whereIn('Designation_code', 2)
                            // ->get();
                
                            $rsDesignation = DB::table('designations')
                            ->select('Designation')
                            ->where('Designation_code', 3)
                            ->get();
                             } 
        



                     elseif ($usertypes === "AAO"){
                        // dd('ok');
                
                            // $rsDesignation = DB::table('designations')
                            // ->whereIn('Designation_code', 2)
                            // ->get();
                
                            $rsDesignation = DB::table('designations')
                            ->select('Designation')
                            ->where('Designation_code', 4)
                            ->get();
                             } 

                             elseif ($usertypes === "audit"){
                                // dd('ok');
                        
                                    // $rsDesignation = DB::table('designations')
                                    // ->whereIn('Designation_code', 2)
                                    // ->get();
                        
                                    $rsDesignation = DB::table('designations')
                                    ->select('Designation')
                                    ->where('Designation_code', 5)
                                    ->get();
                                     } 

                                     elseif ($usertypes === "SDC"){
                                        // dd('ok');
                                
                                            // $rsDesignation = DB::table('designations')
                                            // ->whereIn('Designation_code', 2)
                                            // ->get();
                                
                                            $rsDesignation = DB::table('designations')
                                            ->select('Designation')
                                            ->where('Designation_code', 6)
                                            ->get();
                                             } 
             elseif($usertypes === "Agency")
             {
                // $rsDesignation = DB::table('designations')
                // ->whereIn('Designation_code', 7)
                // ->get();

                $rsDesignation = DB::table('designations')
                ->select('Designation')
                ->where('Designation_code', 7)
                ->get();
                 } 
    // dd($rsDesignation);
    // return view('User.add',['rsDesignation'=>$rsDesignation]);
    return response()->json(['designations' => $rsDesignation]);

}


// create user view page open
public function createview()
{
    // dd('ok');
     // login user session Data----------------------------
     $usercode = auth()->user()->usercode;
     $divid = auth()->user()->Div_id;
     $subdivid = auth()->user()->Sub_Div_id;
     $usertypes = auth()->user()->usertypes;
     // login user session Data----------------------------

      // Retrieve divisions data based on the user's division ID
    $rsDiv=DB::table('divisions')->where('div_id' , $divid)->get();

    // Retrieve all users excluding those with 'EE' or 'PA' user types
   $rsAllUserList = User::get()->whereNotIn('usertypes', ['EE','PA']);
    
      // Retrieve all records from the 'fundhdms' table
       $rsFundedList = DB::table('fundhdms')->get();

       // Retrieve subdivisions based on the user's division ID
       $rsSubDevisionList = DB::table('subdivms')
       ->where('Div_Id','=',$divid)->get();
        // Retrieve work masters based on the user's subdivision ID
       $rsWorkMaster =DB::table('workmasters')
       ->where('Sub_Div_Id','=',$subdivid)->get();



      // Return the view with the retrieved data
     return view('User.add',['rsUser'=>$rsAllUserList,'rsFund'=>$rsFundedList,'rsSubDiv'=>$rsSubDevisionList,'rsWorkMaster'=>$rsWorkMaster , 'rsDiv'=>$rsDiv , 
    //  'rsDesignation'=>$rsDesignation
    ]);
}


  public function storeUsersData(Request $request)
    {
        
        // Validate the incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'Usernm' => 'required|regex:/^\S*$/u|string|max:15|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'mobileno'=> 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:users',
            'password' => 'required|string|min:6',
             //'signature' => 'required|file|mimes:jpeg,jpg|max:2048', // Adjust file validation as needed
        ]);

        // Retrieve a constant division ID
        $divisionId = PublicDivisionId::DIVISION_ID;

        // Check Division Or SubDivision ID
        if($request->Div_id){
          $concatDivORSubDivID = $request->Div_id;
        }
        if($request->Sub_Div_id){
          $concatDivORSubDivID = $request->Sub_Div_id;
        }

           // Adjust the length of Division ID to ensure it has four digits
        $DivisionIDLength = strlen($concatDivORSubDivID);
        if((int)$DivisionIDLength === 1){
            $DivisionID = $concatDivORSubDivID."000";
        }else if((int)$DivisionIDLength === 2){
            $DivisionID = $concatDivORSubDivID."00";
        }else if((int)$DivisionIDLength === 3){
            $DivisionID = $concatDivORSubDivID."0";
        }else if((int)$DivisionIDLength === 4){
            $DivisionID = $concatDivORSubDivID;
        }


         // Generate a new user code by finding the maximum existing user code and incrementing it
        $SQLNewPKID = DB::table('users')
        ->selectRaw(" MAX(CAST(right(IFNULL(usercode,'0'),4)AS UNSIGNED)) as usercode ")
        ->limit(1)
        ->get();
        $RSNewPKID = json_decode($SQLNewPKID);
        if(isset($RSNewPKID[0]->usercode) && !empty($RSNewPKID[0]->usercode)){
            $PrimaryNumber=$RSNewPKID[0]->usercode + 1;
        }else{
            $PrimaryNumber='1';
        }
         // Ensure the user code has four digits
        $lenght = strlen($PrimaryNumber);  //Calculate Lenght
        if((int)$lenght === 1){ //Places Zero Functionality
            $placezero = '000';
        }else if((int)$lenght === 2){
            $placezero = '00';
        }else if((int)$lenght === 3){
            $placezero = '0';
        }else{
            $placezero = '';
        }

        // $file = $request->file('signature');
        // //dd($file);
        // // Use storeAs to generate a unique filename
        // $filePath = time() . '_' . $file->getClientOriginalName();
        // //dd($filePath);
        // // Move the file to the desired directory
        // $file->move(public_path('Uploads/signature'), $filePath);

         // Handle the file upload for signature
        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
           // dd($file);
           $filePath = time() . '_' . $file->getClientOriginalName();

           //dd($filePath);
           $file->move(public_path('Uploads/signature'), $filePath);// Adjust storage path as needed
           
            // Save $filePath to the database or associate it with the user record as needed
            // For example: $user->signature_path = $filePath; $user->save();
        }
        // Determine the subdivision ID based on the user role
        $role=$request->usertypes;
        // dd($role);
        if($role === 'PA' || $role === 'EE' ||  $role === 'AAO' || $role === 'audit' || $role === 'PO' || $role === 'Agency')
        {
          $subDivid=$divisionId ."0";
        }
        else
        {
            $subDivid=$request->Sub_Div_id;
        }
             // Generate the complete user code// dd($request);
        $usercode = $DivisionID.$placezero.$PrimaryNumber;

        // Create a new user record
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobileno'=>$request->mobileno,
            'password' => Hash::make($request->password),
            'Div_id'=>$request->Div_id,
            'Sub_Div_id'=>$subDivid,
            'Designation'=>$request->Designation,
            'usercode'=>$usercode,
            'usertypes'=>$request->usertypes,
            'Usernm'=>$request->Usernm,
            'period_from'=>$request->period_from,
            'DefaultUnmPass'=>1
        ]);


// Check if the user inserted successfully
         if ($user) {

            // Send email notification to the new user
        Mail::to($user->email)->queue(new NewUserNotification($user, $request->password));
        
         }

          // Retrieve the ID of the last inserted user
        $lastInsertedUserId = $user->id; 
      // dd($lastInsertedUserId);
//      dd($role);
// dd($request);
     // Additional role-specific functionality
    if($role === 'Agency')
    {
    //    dd('ok');
    $DivID = $request->Div_id;
    //$SubDivID = $request->Sub_Div_id;
    // dd($DivID,$SubDivID);

    //    dd($lastid);
    $lastid = DB::table('agencies')->max('id');

// dd($lastid);
    // if ($lastid) {
    //     $numericPart = $lastid->AB_Id;
    //     $newid = str_pad($numericPart + 1, 3, '0', STR_PAD_LEFT);
    // } else {
    //     $newid = '001';
    // }
if ($lastid !== null && is_numeric($lastid)) {
    $incrementedLastTwoDigit = str_pad((int)substr($lastid, -4) + 1, 4, '0', STR_PAD_LEFT);
    // dd($incrementedLastTwoDigit);
    $newid = $DivID . $incrementedLastTwoDigit;
} else {
    // If max value is not found or is not numeric, set a default value
    $newid = $DivID . '0001';
}        
// dd($newid);
               // Create a new agency record
               DB::table('agencies')->insert([
               'id'=> $newid,
               'agency_nm' => $request->name,
               'Agency_Mail' => $request->email,
               'Contact_Person1'=>$request->mobileno,
               'User_Name'=>$request->Usernm,
               //'agencysign'=>$filePath,
               'userid'=>$lastInsertedUserId,
           ]);
    }

    // if role executive engineer
     if($role === 'EE')
     {
        $Designation=$request->Designation;
        // dd($Designation);

        // $lasteeid = DB::table('eemasters')->orderBy('eeid', 'desc')->first();

        $DivID = $request->Div_id;
        $SubDivID = $request->Sub_Div_id;
        // dd($DivID,$SubDivID);

    // $lastid = DB::table('sdcmasters')->orderBy('SDC_id', 'desc')->first();
    $lastid = DB::table('eemasters')
    ->where('divid',$DivID)
    ->max('eeid');
// dd($lastid);


 //generate new id
    if ($lastid !== null && is_numeric($lastid)) {
        $incrementedLastTwoDigit = str_pad((int)substr($lastid, -2) + 1, 2, '0', STR_PAD_LEFT);
        // dd($incrementedLastTwoDigit);
        $newid = $DivID . $incrementedLastTwoDigit;
    } else {
        // If max value is not found or is not numeric, set a default value
        $newid = $DivID . '01';
    }        


//       if ($lasteeid) {
//     $numericPart = $lasteeid->eeid; // Extract last two digits
//     $neweeid = $numericPart + 1; // Increment and ensure two digits
// } else {
//     $neweeid = 1; // If no record exists, start with 1
// }
                   // new executive engineer record insert
                DB::table('eemasters')->insert([
                'eeid'=> $newid,
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'divid'=>$request->Div_id,
                // 'subdiv_id'=>$subDivid,
                'Designation'=>$request->Designation,
                'user_name'=>$request->Usernm,
                //'sign'=>$filePath,
                'userid'=>$lastInsertedUserId,
                'period_from'=>$request->period_from,
            ]);
     }


     //role is deputy and PA 
     if($role === 'DYE' || $role === 'PA')
     {
        // $lastid = DB::table('dyemasters')->orderBy('dye_id', 'desc')->first();

        $DivID = $request->Div_id;
        //$SubDivID = $request->Sub_Div_id;
        // dd($DivID,$SubDivID);

    // $lastid = DB::table('sdcmasters')->orderBy('SDC_id', 'desc')->first();
    $lastid = DB::table('dyemasters')
    ->where('div_id',$DivID)
    ->where('subdiv_id',$subDivid)
    ->max('dye_id');
// dd($lastid);

   //generate new id 
    if ($lastid !== null && is_numeric($lastid)) {
        $incrementedLastTwoDigit = str_pad((int)substr($lastid, -2) + 1, 2, '0', STR_PAD_LEFT);
        // dd($incrementedLastTwoDigit);
        $newid = $subDivid . $incrementedLastTwoDigit;
    } else {
        // If max value is not found or is not numeric, set a default value
        $newid = $subDivid . '01';
    }        


        // if ($lastid) {
        //     $numericPart = $lastid->dye_id;
        //     $newid = str_pad($numericPart + 1, 3, '0', STR_PAD_LEFT);
        // } else {
        //     $newid = '001';
        // }
        
        // if($role === 'PA')
        // {
        //   $subdivid=1470;
        // }
        // else
        // {
        //     $subdivid=$request->Sub_Div_id;
        // }
       // dd($subdivid);
              //new record insert deputy
                DB::table('dyemasters')->insert([
                'dye_id'=> $newid,
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                'subdiv_id'=> $subDivid,
                'designation'=>$request->Designation,
                'user_name'=>$request->Usernm,
                //'sign'=>$filePath,
                'userid'=>$lastInsertedUserId,
                'period_from'=>$request->period_from,
            ]);
     }

     // role junior engineer and project officer  new record
     if($role === 'SO' || $role === 'PO')
     {
        // $lastid = DB::table('jemasters')->orderBy('jeid', 'desc')->first();

        $DivID = $request->Div_id;

        
        // dd($DivID,$SubDivID);

    // $lastid = DB::table('sdcmasters')->orderBy('SDC_id', 'desc')->first();
    $lastid = DB::table('jemasters')
    ->where('div_id',$DivID)
    ->where('subdiv_id',$subDivid)
    ->max('jeid');
// dd($lastid);

    if ($lastid !== null && is_numeric($lastid)) {
        $incrementedLastTwoDigit = str_pad((int)substr($lastid, -3) + 1, 3, '0', STR_PAD_LEFT);
        // dd($incrementedLastTwoDigit);
        $newid = $subDivid . $incrementedLastTwoDigit;
    } else {
        // If max value is not found or is not numeric, set a default value
        $newid = $subDivid . '001';
    }        
        // if ($lastid) {
        //     $numericPart = $lastid->jeid;
        //     $newid = str_pad($numericPart + 1, 4, '0', STR_PAD_LEFT);
        // } else {
        //     $newid = '0001';
        // }
        
        //new record insert in junior engineer
                DB::table('jemasters')->insert([
                'jeid'=> $newid,
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                'subdiv_id'=>$subDivid,
                'designation'=>$request->Designation,
                'username'=>$request->Usernm,
                //'sign'=>$filePath,
                'userid'=>$lastInsertedUserId,
                'period_from'=>$request->period_from,
            ]);
     }


     //role of  division accountant officer
     if($role === 'AAO')
     {

        $DivID = $request->Div_id;
        // $SubDivID = $request->Sub_Div_id;
        // dd($DivID,$SubDivID);

        // $lastid = DB::table('daomasters')->orderBy('DAO_id', 'desc')->first();
        $lastid = DB::table('daomasters')
        ->where('div_id',$DivID)
        ->max('DAO_id');

        //new id created
        if ($lastid !== null && is_numeric($lastid)) {
            $incrementedLastTwoDigit = str_pad((int)substr($lastid, -2) + 1, 2, '0', STR_PAD_LEFT);
            // dd($incrementedLastTwoDigit);
            $newid = $DivID . $incrementedLastTwoDigit;
        } else {
            // If max value is not found or is not numeric, set a default value
            $newid = $DivID . '01';
        }        
    // dd($newid);
    


        // if ($lastid) {
        //     $numericPart = $lastid->DAO_id;
        //     $newid = str_pad($numericPart + 1, 3, '0', STR_PAD_LEFT);
        // } else {
        //     $newid = '001';
        // }
        
        // new divisional accountant record inserted
                DB::table('daomasters')->insert([
                'DAO_id'=> $newid,
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                // 'subdiv_id'=>$request->Sub_Div_id,
                'designation'=>$request->Designation,
                'username'=>$request->Usernm,
                //'sign'=>$filePath,
                'userid'=>$lastInsertedUserId,
                'period_from'=>$request->period_from,
            ]);
     }


     //role audit data 
     if($role === 'audit')
     {
        $DivID = $request->Div_id;
        // $SubDivID = $request->Sub_Div_id;
        // dd($DivID,$SubDivID);

        // $lastid = DB::table('abmasters')->orderBy('AB_Id', 'desc')->first();
        $lastid = DB::table('abmasters')
        ->where('div_id',$DivID)
        ->max('AB_Id');

// dd($lastid);
        // if ($lastid) {
        //     $numericPart = $lastid->AB_Id;
        //     $newid = str_pad($numericPart + 1, 3, '0', STR_PAD_LEFT);
        // } else {
        //     $newid = '001';
        // }
        //new id created
    if ($lastid !== null && is_numeric($lastid)) {
        $incrementedLastTwoDigit = str_pad((int)substr($lastid, -2) + 1, 2, '0', STR_PAD_LEFT);
        // dd($incrementedLastTwoDigit);
        $newid = $DivID . $incrementedLastTwoDigit;
    } else {
        // If max value is not found or is not numeric, set a default value
        $newid = $DivID . '01';
    }        
// dd($newid);

                //new auditor record insert
                DB::table('abmasters')->insert([
                'AB_Id'=> $newid,
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                'subdiv_id'=>$subDivid,
                'designation'=>$request->Designation,
                'username'=>$request->Usernm,
                //'sign'=>$filePath,
                'userid'=>$lastInsertedUserId,
                'period_from'=>$request->period_from,
            ]);
     }

     // role sub divisional clerk
     if($role === 'SDC')
     {
            $DivID = $request->Div_id;
            //$SubDivID = $request->Sub_Div_id;
            // dd($DivID,$SubDivID);
  
        // $lastid = DB::table('sdcmasters')->orderBy('SDC_id', 'desc')->first();
        $lastid = DB::table('sdcmasters')
        ->where('subdiv_id',$subDivid)
        ->max('SDC_id');
// dd($lastid);

         // new id created
        if ($lastid !== null && is_numeric($lastid)) {
            $incrementedLastTwoDigit = str_pad((int)substr($lastid, -2) + 1, 2, '0', STR_PAD_LEFT);
            // dd($incrementedLastTwoDigit);
            $newid = $subDivid . $incrementedLastTwoDigit;
        } else {
            // If max value is not found or is not numeric, set a default value
            $newid = $subDivid . '01';
        }        
// dd($newid);


        // if ($lastid) {
        //     $numericPart = $lastid->SDC_id;
        //     $newid = str_pad($numericPart + 1, 2, '0', STR_PAD_LEFT);
        // } else {
        //     $newid = '01';
        // }

                //new sub divisional clerk record inserted
                DB::table('sdcmasters')->insert([
                'SDC_id'=> $newid,
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                'subdiv_id'=>$subDivid,
                'designation'=>$request->Designation,
                'username'=>$request->Usernm,
                //'sign'=>$filePath,
                'userid'=>$lastInsertedUserId,
                'period_from'=>$request->period_from,
            ]);
     }
       // Prepare the data for SweetAlert
    $userDetails = [
        'username' => $request->Usernm,
        'password' => $request->password,
        'email' => $request->email,
    ];
     
        //event(new Registered($user));
        //return to list page
        return redirect('userslist')->with('success','Record save successfully.')->with('userDetails', $userDetails);
    }


    // Retrive All Records
function allrecords(){
    // dd('ok');
    // login user session Data----------------------------

    $usercode = auth()->user()->usercode;
    $divid = auth()->user()->Div_id;
    $subdivid = auth()->user()->Sub_Div_id;

    $usertype = auth()->user()->usertypes;
    //dd($usertype);
    // login user session Data----------------------------
    //data list related to ee and PA
    if($usertype === 'EE' || $usertype === 'PA')
    {
        $data= User::select('*')
    ->whereIn('usertypes', ['EE','PA','AAO','audit','PO','DYE'])
    ->where('Div_id', '=', $divid)
    ->get();

    }
    //data related to DYE
    if($usertype === 'DYE')
    {
        $data= User::select('*')
    ->whereIn('usertypes', ['DYE','JE','SDC','SO'])
    ->where('Div_id', '=', $divid)
    ->Where('Sub_Div_id','=', $subdivid)
    ->get();

    }
    //dd($data);
    //return to userlist
    return view('User.userslist',['users'=>$data]);
}


//edit user data
public function editUsersData($id)
{ 
//    dd('ok');
     // login user session Data----------------------------
     $usercode = auth()->user()->usercode;
     $divid = auth()->user()->Div_id;
     $subdivid = auth()->user()->Sub_Div_id;
    //  $usertypes = auth()->user()->usertypes;

     // login user session Data----------------------------
//   dd($usertypes,$id);

    $rsDiv=DB::table('divisions')->where('div_id' , $divid)->get();


    // if($usertypes === "EE" || $usertypes === "PA")
    // {
    // // $rsDesignation = DB::table('designations')
    // // ->whereIn('Designation_code', 1)
    // // ->get();

    // $rsDesignation = DB::table('designations')
    // ->select('Designation')
    // ->where('Designation_code', 1)
    // ->get();
    //  } 
    //  elseif ($usertypes === "DYE"){
    //     // dd('ok');

    //         // $rsDesignation = DB::table('designations')
    //         // ->whereIn('Designation_code', 2)
    //         // ->get();

    //         $rsDesignation = DB::table('designations')
    //         ->select('Designation')
    //         ->where('Designation_code', 2)
    //         ->get();
    //          } 

    //          elseif ($usertypes === "PO"){
    //             // dd('ok');
        
    //                 // $rsDesignation = DB::table('designations')
    //                 // ->whereIn('Designation_code', 2)
    //                 // ->get();
        
    //                 $rsDesignation = DB::table('designations')
    //                 ->select('Designation')
    //                 ->where('Designation_code', 3)
    //                 ->get();
    //                  } 

    //                  elseif ($usertypes === "SO")
    //                  {
    //                     // dd('ok');
                
    //                         // $rsDesignation = DB::table('designations')
    //                         // ->whereIn('Designation_code', 2)
    //                         // ->get();
                
    //                         $rsDesignation = DB::table('designations')
    //                         ->select('Designation')
    //                         ->where('Designation_code', 3)
    //                         ->get();
    //                          } 
        



    //                  elseif ($usertypes === "AAO"){
    //                     // dd('ok');
                
    //                         // $rsDesignation = DB::table('designations')
    //                         // ->whereIn('Designation_code', 2)
    //                         // ->get();
                
    //                         $rsDesignation = DB::table('designations')
    //                         ->select('Designation')
    //                         ->where('Designation_code', 4)
    //                         ->get();
    //                          } 

    //                          elseif ($usertypes === "audit"){
    //                             // dd('ok');
                        
    //                                 // $rsDesignation = DB::table('designations')
    //                                 // ->whereIn('Designation_code', 2)
    //                                 // ->get();
                        
    //                                 $rsDesignation = DB::table('designations')
    //                                 ->select('Designation')
    //                                 ->where('Designation_code', 5)
    //                                 ->get();
    //                                  } 

    //                                  elseif ($usertypes === "SDC"){
    //                                     // dd('ok');
                                
    //                                         // $rsDesignation = DB::table('designations')
    //                                         // ->whereIn('Designation_code', 2)
    //                                         // ->get();
                                
    //                                         $rsDesignation = DB::table('designations')
    //                                         ->select('Designation')
    //                                         ->where('Designation_code', 6)
    //                                         ->get();
    //                                          } 
    //          elseif($usertypes === "Agency")
    //          {
    //             // $rsDesignation = DB::table('designations')
    //             // ->whereIn('Designation_code', 7)
    //             // ->get();

    //             $rsDesignation = DB::table('designations')
    //             ->select('Designation')
    //             ->where('Designation_code', 7)
    //             ->get();
    //              } 
    // dd($rsDesignation);





    // if($usertypes === "EE" || $usertypes === "PA"){

    // $rsDesignation = DB::table('designations')
    // ->whereIn('designationid', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 19])
    // ->get();

    //  } 

    //  if($usertypes === "EE" || $usertypes === "PA"){

    //     $rsDesignation = DB::table('designations')
    //     ->whereIn('designationid', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 19])
    //     ->get();
        
    //      } 
    
        //  if($usertypes === "DYE"){

        //     $rsDesignation = DB::table('designations')
        //     ->whereIn('designationid', [12, 13, 14, 15, 16 , 20])
        //     ->get();
            
        //      } 
//     //dd($rsDesignation);
//designationn of user
$selectedDesignationname=DB::table('users')
->where('id',$id)
->value('Designation');
// dd($selectedDesignationname);

//designation code of user
$Designationcode=DB::table('designations')
->where('Designation',$selectedDesignationname)
->value('Designation_code');
// dd($selectedDesignationname,$Designationcode);

//designationlist
$designationlist=DB::table('designations')
->select('Designation')
->where('Designation_code',$Designationcode)
->get('Designation');
// dd($selectedDesignationname,$Designationcode,$designationlist);



//all user list related usertype
   $rsAllUserList = User::get()->whereNotIn('usertypes', ['EE','PA']);
       $rsFundedList = DB::table('fundhdms')->get();
       $rsSubDevisionList = DB::table('subdivms')
       ->where('Div_Id','=',$divid)->get();
       $rsWorkMaster =DB::table('workmasters')
       ->where('Sub_Div_Id','=',$subdivid)->get();

       $user = User::find($id);
       $usertype= $user->usertypes;
    
       //get sign related user
      if($usertype === 'SDC')
      {
        $sign=DB::table('sdcmasters')->where('userid' , $user->id)->value('sign');
      }
      if($usertype === 'audit')
      {
        $sign=DB::table('abmasters')->where('userid' , $user->id)->value('sign');
      }
      if($usertype === 'AAO')
      {
        // dd($usertype);
        $sign=DB::table('daomasters')->where('userid' , $user->id)->value('sign');
      }
      if($usertype === 'SO' || $usertype === 'PO') 
      {
        $sign=DB::table('jemasters')->where('userid' , $user->id)->value('sign');
        //dd($sign);
      }
      if($usertype === 'DYE' || $usertype === 'PA')
      {
        // dd('ok');
        $sign=DB::table('dyemasters')->where('userid' , $user->id)->value('sign');
        // dd($sign);
      }
      if($usertype === 'EE')
      {
        $sign=DB::table('eemasters')->where('userid' , $user->id)->value('sign');
      }
      if($usertype === 'Agency')
      {
        $sign=DB::table('agencies')->where('userid' , $user->id)->value('agencysign');
      }
     //dd($sign);
   $imagePath =  $sign;
   $imageUrl = url('Uploads/signature/' . $imagePath);
   //$imageData = base64_encode(file_get_contents($imagePath));
   //$imageSrc = 'data:image/jpeg;base64,' . $imageData;
  //   dd($imageUrl);
  //return to user edited page
    return view('User.edituser',['user'=>$user ,
     'rsUser'=>$rsAllUserList,
    'rsFund'=>$rsFundedList,
    'rsSubDiv'=>$rsSubDevisionList,
    'rsWorkMaster'=>$rsWorkMaster , 
    'rsDiv'=>$rsDiv , 
    'imagePath' => $imageUrl,
    'selectedDesignationname'=>$selectedDesignationname,
    'designationlist'=>$designationlist]);
}

//Update user data    
public function storeeditUsersData(Request $request)
    {

        $userId = $request->input('user_id');
        //dd($userId);
        $request->validate([
            //'name' => 'required|string|max:255',
            'Usernm' => [
                'required',
                'regex:/^\S*$/u',
                'string',
                'max:15',
                Rule::unique('users')->ignore($userId),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'mobileno' => [
                'required',
                'regex:/^([0-9\s\-\+\(\)]*)$/',
                'min:10',
                Rule::unique('users')->ignore($userId),
            ],
            //'password' => 'required|string|confirmed|min:6',
             //'signature' => 'required|file|mimes:jpeg,jpg|max:2048', // Adjust file validation as needed
        ]);
// dd($request);

 //get public define division id
$divisionId = PublicDivisionId::DIVISION_ID;
// dd($divisionId);
        // Check Division Or SubDivision ID
        if($request->Div_id){
          $concatDivORSubDivID = $request->Div_id;
        }
        if($request->Sub_Div_id){
          $concatDivORSubDivID = $request->Sub_Div_id;
        }


        $DivisionIDLength = strlen($concatDivORSubDivID);
        if((int)$DivisionIDLength === 1){
            $DivisionID = $concatDivORSubDivID."000";
        }else if((int)$DivisionIDLength === 2){
            $DivisionID = $concatDivORSubDivID."00";
        }else if((int)$DivisionIDLength === 3){
            $DivisionID = $concatDivORSubDivID."0";
        }else if((int)$DivisionIDLength === 4){
            $DivisionID = $concatDivORSubDivID;
        }


        //User code Genration Functionality
        $SQLNewPKID = DB::table('users')
        ->selectRaw(" MAX(CAST(right(IFNULL(usercode,'0'),4)AS UNSIGNED)) as usercode ")
        ->limit(1)
        ->get();
        $RSNewPKID = json_decode($SQLNewPKID);
        if(isset($RSNewPKID[0]->usercode) && !empty($RSNewPKID[0]->usercode)){
            $PrimaryNumber=$RSNewPKID[0]->usercode + 1;
        }else{
            $PrimaryNumber='1';
        }
        $lenght = strlen($PrimaryNumber);  //Calculate Lenght
        if((int)$lenght === 1){ //Places Zero Functionality
            $placezero = '000';
        }else if((int)$lenght === 2){
            $placezero = '00';
        }else if((int)$lenght === 3){
            $placezero = '0';
        }else{
            $placezero = '';
        }


       
        $filePath = null;       // $filePath='';
        if ($request->hasFile('signature')) {
                    $file = $request->file('signature');
                    //dd($file);
                    // Use storeAs to generate a unique filename
                    $filePath = time() . '_' . $file->getClientOriginalName();
                    //dd($filePath);
                    // Move the file to the desired directory
                    $file->move(public_path('Uploads/signature'), $filePath);
                            //dd($filePath);
                            // Save $filePath to the database or associate it with the user record as needed
                            // For example: $user->signature_path = $filePath; $user->save();
        }
       
 
        $role=$request->usertypes;

        //sub-division id check user edited data
        if($role === 'PA' || $role === 'EE' ||  $role === 'AAO' || $role === 'audit' || $role === 'PO' || $role === 'Agency')
        {

           

          $subDivid=$divisionId ."0";
        }
        else
        {
            $subDivid=$request->Sub_Div_id;
        }

    //update user table user edit data
        $usercode = $DivisionID.$placezero.$PrimaryNumber;
        $user = User::where('id', $request->user_id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'mobileno'=>$request->mobileno,
            'Div_id'=>$request->Div_id,
            'Sub_Div_id'=>$subDivid,
            'Designation'=>$request->Designation,
            'usercode'=>$usercode,
            'usertypes'=>$request->usertypes,
            'Usernm'=>$request->Usernm,
            'period_from'=>$request->period_from,
        ]);

        
       
      // dd($lastInsertedUserId);
     //dd($role);

     //role if executive engineer 
     if($role === 'EE')
     {
       
        //sign update with condition
        $previousSignaturePath= DB::table('eemasters')->where('userid', $request->user_id)->value('sign');
        if ($filePath == null) {
            //dd($filePath);
            $filePath = $previousSignaturePath;
        }
        
              //update the executive engineer 
                DB::table('eemasters')->where('userid', $request->user_id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'divid'=>$request->Div_id,
                // 'subdiv_id'=>$request->Sub_Div_id,
                'Designation'=>$request->Designation,
                'user_name'=>$request->Usernm,
                'sign'=>$filePath,
            
                'period_from'=>$request->period_from,
            ]);
     }

    // If the role is 'DYE' or 'PA'
     if($role === 'DYE' || $role === 'PA')
     {
        // Get the latest record from the dyemasters table
        $lastid = DB::table('dyemasters')->orderBy('dye_id', 'desc')->first();


    // Get the current signature path for the user
        $previousSignaturePath= DB::table('dyemasters')->where('userid', $request->user_id)->value('sign');

          // If no new file is uploaded, retain the previous signature path
        if ($filePath == null) {
            //dd($filePath);
            $filePath = $previousSignaturePath;
        }
        
             
    // Update the dyemasters table with the new data
                DB::table('dyemasters')->where('userid', $request->user_id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                'subdiv_id'=>$subDivid,
                'designation'=>$request->Designation,
                'user_name'=>$request->Usernm,
                'sign'=>$filePath,
              
                'period_from'=>$request->period_from,
            ]);
     }


     // If the role is 'SO' or 'PO'
     if($role === 'SO' || $role === 'PO')
     {

        // Get the current signature path for the user
        $previousSignaturePath= DB::table('jemasters')->where('userid', $request->user_id)->value('sign');

         // If no new file is uploaded, retain the previous signature path
        if ($filePath == null) {
            //dd($filePath);
            $filePath = $previousSignaturePath;
        }
         // Update the jemasters table with the new data
                DB::table('jemasters')->where('userid', $request->user_id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                'subdiv_id'=>$subDivid,
                'designation'=>$request->Designation,
                'username'=>$request->Usernm,
                'sign'=>$filePath,
                'period_from'=>$request->period_from,
            ]);
     }

     // If the role is 'AAO'
     if($role === 'AAO')
     {
       
          // Get the current signature path for the user
        $previousSignaturePath= DB::table('daomasters')->where('userid', $request->user_id)->value('sign');

         // If no new file is uploaded, retain the previous signature path
        if ($filePath == null) {
            //dd($filePath);
            $filePath = $previousSignaturePath;
        }

         // Update the daomasters table with the new data
                DB::table('daomasters')->where('userid', $request->user_id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                // 'subdiv_id'=>$subDivid,
                'designation'=>$request->Designation,
                'username'=>$request->Usernm,
                'sign'=>$filePath,
                'period_from'=>$request->period_from,
            ]);
     }


      // If the role is 'audit'
     if($role === 'audit')
     {

         // Get the current signature path for the user
        $previousSignaturePath= DB::table('abmasters')->where('userid', $request->user_id)->value('sign');

         // If no new file is uploaded, retain the previous signature path
        if ($filePath == null) {
            //dd($filePath);
            $filePath = $previousSignaturePath;
        }
                // Update the abmasters table with the new data
                DB::table('abmasters')->where('userid', $request->user_id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                'subdiv_id'=>$subDivid,
                'designation'=>$request->Designation,
                'username'=>$request->Usernm,
                'sign'=>$filePath,
                'period_from'=>$request->period_from,
            ]);
     }

     // If the role is 'SDC'
     if($role === 'SDC')
     {
         // Get the current signature path for the user
        $previousSignaturePath= DB::table('sdcmasters')->where('userid', $request->user_id)->value('sign');
         // If no new file is uploaded, retain the previous signature path
        if ($filePath == null) {
            //dd($filePath);
            $filePath = $previousSignaturePath;
        }
                // Update the sdcmasters table with the new data
                DB::table('sdcmasters')->where('userid', $request->user_id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone_no'=>$request->mobileno,
                'div_id'=>$request->Div_id,
                'subdiv_id'=>$subDivid,
                'designation'=>$request->Designation,
                'username'=>$request->Usernm,
                'sign'=>$filePath,
                'period_from'=>$request->period_from,
            ]);
     }




     



        // Redirect to the users list with a success message
        return redirect('userslist')->with('success','Record save successfully.');
    }


    //delete user 
    public function FunDeleteUser(Request $request, $id)
    {      
        // dd($id);
        $selectdetailuser = DB::table('users')
        ->where('id', $id)
        ->first();

    // Check if the user is of type 'EE' or 'AAO'
    if ($selectdetailuser->usertypes === 'EE') {
        // Delete from 'eemasters'
        $del=DB::table('eemasters')
            ->where('userid', $id)
            ->delete();
// dd($del);
    }
    // Check if the user is of type 'PA'
    elseif ($selectdetailuser->usertypes === 'PA') {
        // Delete from 'daomasters'
        DB::table('dyemasters')
            ->where('userid', $id)
            ->delete();
    }
      // Check if the user is of type 'DYE'
 elseif ($selectdetailuser->usertypes === 'DYE') {
    // Delete from 'daomasters'
    DB::table('dyemasters')
        ->where('userid', $id)
        ->delete();
}
 // Check if the user is of type 'PO'
elseif ($selectdetailuser->usertypes === 'PO') {
    // Delete from 'daomasters'
    DB::table('jemasters')
        ->where('userid', $id)
        ->delete();
}
// Check if the user is of type 'SO'
elseif ($selectdetailuser->usertypes === 'SO') {
    // Delete from 'daomasters'
    DB::table('jemasters')
        ->where('userid', $id)
        ->delete();
}
// Check if the user is of type 'audit'
elseif ($selectdetailuser->usertypes === 'audit') {
    // Delete from 'daomasters'
    DB::table('abmasters')
        ->where('userid', $id)
        ->delete();
}
   // Check if the user is of type 'AAO'
     elseif ($selectdetailuser->usertypes === 'AAO') {
        // Delete from 'daomasters'
        DB::table('daomasters')
            ->where('userid', $id)
            ->delete();
    }

    // Check if the user is of type 'AGNECY'
    elseif ($selectdetailuser->usertypes === 'Agency') {
        // Delete from 'daomasters'
        DB::table('agencies')
            ->where('userid', $id)
            ->delete();
    }
    // Check if the user is of type 'SDC'
        elseif ($selectdetailuser->usertypes === 'SDC') {
        // Delete from 'daomasters'
        DB::table('sdcmasters')
            ->where('userid', $id)
            ->delete();
    }

    
    //DELETE USER
        $deleteUser=DB::table('users')
        ->where('id',$id)
        ->delete();
        // dd($deleteUser);

        //
        return redirect('userslist');


    }



}
