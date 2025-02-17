<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JuniorEngineer;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use App\Helpers\PublicDivisionId;
use Illuminate\Support\Facades\Log;

//Junior  engineer controller  master data functions
class JuniorEngineerController extends Controller
{
//list divisions
    public function getDivisions()
    {
          // Fetch division name where 'div_id' is 147 from the 'divisions' table
        $divisions = DB::table('divisions')->where('div_id', '=', 147)->select('div')->get();
        //dd($divisions);
         // Return divisions as JSON response
        return response()->json($divisions);
    }


    //list subdivisions
    public function getSubdivisions(Request $request)
    {
        // Retrieve the 'division' input from the request
        $division = $request->input('division');
        //dd($division);
        // Fetch the division ID where 'div' matches the input
        $divid=DB::table('divisions')->where('div' , $division)->get()->value('div_id');

         // Fetch subdivisions where 'Div_Id' is 147 from 'subdivms' table
        $subdivisions = DB::table('subdivms')
            ->where('Div_Id', 147)
            ->select('Sub_Div')
            ->get();
            //dd($subdivisions);

            // Return subdivisions as JSON response
        return response()->json($subdivisions);
    }
    // insert data in junior engineer
    public function FunDropdownselectInsert(Request $request)
    {
        // Assuming a constant or configuration value for division ID
        $divisionId = PublicDivisionId::DIVISION_ID;
        // dd($divisionId);
        // Fetch the division name for the given division ID
        $DBDivlist=DB::table('divisions')
        ->where('div_id',$divisionId)
        ->value('div');
        // dd($DBDivlist);
          // Fetch subdivisions for the given division ID
        $DBSubDivlist=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('Div_Id',$divisionId)
        ->get();

          // Fetch designation where 'Designation_code' is 3 from 'designations' table
        $DBDesignation = DB::table('designations')
        ->select('Designation')
        ->where('Designation_code', 3)
        ->get();
        // dd( $DBDesignation);

        // Return view with fetched data
       return view('juniorengineer',compact('DBDivlist','DBSubDivlist','DBDesignation'));
    }





    public function insertjuniorengineer(Request $request)
    {
        try{

             // Retrieve inputs from the request
              $division=$request->divname;
            //  dd($division);
             $subdivision=$request->Subdivname;
            //  dd($subdivision);

              // Fetch division ID for the given division name
        $divid=DB::table('divisions')->where('div' ,  $division)->get()->value('div_id');

        // Fetch subdivision ID for the given subdivision name
        $subdivid=DB::table('subdivms')->where('Sub_Div' ,  $subdivision)->get()->value('Sub_Div_Id');

       // Fetch the maximum 'jeid' from 'jemasters' table
        $maxjeid = DB::table('jemasters')->max('jeid');
        // dd($maxjeid);

        if ($maxjeid !== null && is_numeric($maxjeid)) {
            // Extract the last three digits, increment, and pad with zeros
            $incrementedLastThreeDigits = str_pad((int)substr($maxjeid, -3) + 1, 3, '0', STR_PAD_LEFT);
            // dd($incrementedLastThreeDigits);

            // Assuming $subdivid is defined somewhere in your code
            $FinalJe_id = $subdivid . $incrementedLastThreeDigits;
        } else {
            // If max value is not found or is not numeric, set a default value
            $FinalJe_id = $subdivid . '001';
        }
        // dd($FinalJe_id);



    // Retrieve PF number input from the request
    $pfnumber=$request->pf_number;


    $ispfno=$request->pf_number_value;

    
   // dd($has_pf_number);
    if($ispfno == 0)
    {
         // Fetch the previous PF number where 'div_id' matches and 'ispfno' is 0, ordered by 'pf_no' descending
          $previouspfnumber=DB::table('jemasters')
          ->where('div_id' , $divid)
          ->where('ispfno' , 0)
          ->orderBy('pf_no', 'desc')
          ->first('pf_no');
       //dd($previouspfnumber);

       if ($previouspfnumber) {
      // Generate new PF number by incrementing the last four digits
        $lastFourDigits = substr($previouspfnumber->pf_no, -4);
        $newLastFourDigits = str_pad((intval($lastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
        $newpfnumber = substr_replace($previouspfnumber->pf_no, $newLastFourDigits, -4);
    
            }
        else
        {          // If no previous PF number, set a default value
               $newpfnumber = $divid.'0001';
               //dd($newpfnumber);
         }


    }

    else
    {

          // Use the provided PF number if 'ispfno' is not 0
        $newpfnumber= $pfnumber;
        //dd($newpfnumber);
    }
    //dd($newpfnumber);

       // Insert the new junior engineer data into 'jemasters' table
        $Juniorengineer=DB::table('jemasters')->insert([
           
            
            'div_id'=>$divid,
            'subdiv_id'=>$subdivid,
            'jeid' => $FinalJe_id,
            'designation'=>$request->designation, 
            'period_from'=>$request->chargefrom,
            'period_upto'=>$request->chargeupto,
            'pf_no' => $newpfnumber,
            'phone_no'=>$request->mobileno,
            'email'=>$request->email,
            'username'=>$request->username,
            'password'=>$request->password,
            'name'=>$request->name,
            'ispfno' => $ispfno
        ]);

        // swweet alert to successfully insert data
        Alert::success('Congrats', 'You\'ve Succesfully add the data');
        // return redirect('juniorengineer');
        // $users=DB::select('select * from jemasters');
        return redirect('listjuniorengineer');

        // return view('listjuniorengineer',['users'=>$users]);


    }catch(\Exception $e)
    {
       Log::error('An error occurr During Create new agency data' . $e->getMessage());

        // Flash error message to session
    $request->session()->flash('error', 'An error occurr During Create new junior data');

    return redirect()->back(); // Redirect back to the previous page (agency form)
    }

    }


    //view data in list format 
    
    public function listjunioreengineer(Request $request)
    {
         // Fetch all records from 'jemasters' table
        // $users=DB::select('select * from junior_engineers');
                $users=DB::table('jemasters')->get();
                dd($users);

        return view('listjuniorengineer',['users'=>$users]);

    }


// edit and update the data inside the junior engineer

// edit view page
public function editjuniorengineer($id)
{
    
    try{

          // Retrieve junior engineer details by ID
  $users=DB::table('jemasters')->where('jeid' , $id)->first();

  // Get the division name based on the retrieved division ID
  $div=DB::table('divisions')->where('div_id' , $users->div_id)->get()->value('div');
  //dd( $div);
        $selecteddesignation =$users->designation;

          // Get the list of designations with the designation code 3
        $designationlist=DB::table('designations')
        ->where('Designation_code',3)
        ->select('Designation')->get();

   // Get the subdivision name based on the retrieved subdivision ID
   $subdiv=DB::table('subdivms')->where('Sub_Div_Id' , $users->subdiv_id)->get()->value('Sub_Div');


      // Return the view with the retrieved data
    return view('editjuniorengineer',['users'=>$users , 'div'=>$div , 'subdiv'=>$subdiv,
                 'designationlist'=>$designationlist,'selecteddesignation'=>$selecteddesignation]);


    }catch(\Exception $e)
    {
          // Log the error and redirect back with an error message
    Log::error('An Error occurr during open list of Junior engineer' . $e->getMessage());

    //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');

    return redirect()->back()->with('error' , 'An Error occurr during open edit of Junior engineer data');
    }

}



// update fuction of  junior engineer edit page

public function updatejuniorengineer(Request $request , $id)
{ 
    //dd($request);

    try{

         // Retrieve division and subdivision IDs based on names from the request
            $division=$request->division_name;
            // dd($division);
            $subdivision=$request->subdivision_name;
        $divid=DB::table('divisions')->where('div' ,  $division)->get()->value('div_id');
        //dd($divid);
        $subdivid=DB::table('subdivms')->where('Sub_Div' ,  $subdivision)->get()->value('Sub_Div_Id');


    // Update junior engineer details
   $users=DB::table('jemasters')->where('jeid' , $id)->update([
    'div_id'=> $divid,
   'subdiv_id' => $subdivid,
   'jeid' => $id,
   'designation'=>$request->designation, 
   'period_from'=>$request->chargefrom,
   'period_upto'=>$request->chargeupto,
   'phone_no'=>$request->mobileno,
   'email'=>$request->email,
   'username'=>$request->username,
   'pf_no'=>$request->pf_number,
   'name'=>$request->name,
   ]);


    Alert::success('Congrats', 'You\'ve Successfully Edit the data');


return redirect('listjuniorengineer');

}
catch(\Exception $e)
{
     // Log the error and redirect back with an error message
    Log::error('An Error occurr during Update Junior engineer data' . $e->getMessage());

    //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');

    return redirect()->back()->with('error' , 'An Error occurr during Update Junior engineer data');
 }
}



// view data of junior engineer
public function viewjuniorengineer($id)
{
    try{
       // Retrieve junior engineer details by ID
    $users=DB::table('jemasters')->where('jeid' , $id)->first();

    // Retrieve division and subdivision names based on IDs
    $div_id = $users->div_id; // Accessing div_id for the current user
    $subdiv_id = $users->subdiv_id; // Accessing subdiv_id for the current user

    $division = DB::table('divisions')->where('div_id', $div_id)->value('div');
    $subdivision = DB::table('subdivms')->where('Sub_Div_Id', $subdiv_id)->value('Sub_Div');

     // Add division and subdivision names to the user object
    $users->divisionname= $division;
    $users->subdivisionname= $subdivision;

    return view('view_juniorengineer',compact('users'));

    }catch(\Exception $e)
        {
             // Log the error and redirect back with an error message
            Log::error('An Error occurr during Show Junior Engineer data' . $e->getMessage());

            //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');
 
            return redirect()->back()->with('error' , 'An Error occurr during Show Junior Engineer data');
         }
}

// get and delete data function of junior engineer

// get function
public function getalltablerows(Request $request)
    {
        
        try{

      // Use paginate method for pagination with 10 items per page
    $users = DB::table('jemasters')->paginate(9);

    // Iterate over the paginated items
    foreach ($users as $user) {
        $div_id = $user->div_id; // Accessing div_id for the current user
        $subdiv_id = $user->subdiv_id; // Accessing subdiv_id for the current user

        // Retrieve related division and subdivision names
        $division = DB::table('divisions')->where('div_id', $div_id)->value('div');
        $subdivision = DB::table('subdivms')->where('Sub_Div_Id', $subdiv_id)->value('Sub_Div');

        // Assign the names to the user object
        $user->division_name = $division;
        $user->subdivision_name = $subdivision;
    }


        //dd($users->div_id);
        return view('listjuniorengineer', ['users' => $users]);

    }
    catch(\Exception $e)
    {
         // Log the error and redirect to a specific route with an error message
      Log::error('An Error occurr during open list of Junior Engineer' . $e->getMessage());

       $request->session()->flash('error', 'An Error occurr during open list of Junior Engineer');

       return redirect('listworkmasters');
    }

    }


// delete function
public function deletejuniorengineer($id)
{
    try{

    // dd($id);
        if($id)
        {
             // Retrieve user ID associated with the junior engineer
            $userid=DB::table('jemasters')->where('jeid', $id)->first('userid');

          // Delete the user record
        DB::table('users')->where('id', $userid->userid)->delete();

          // Delete the junior engineer record
            $query = DB::table('jemasters')
            ->where('jeid', $id)
            ->delete();
            // ->update(['isdelete' => 0]);
            return back();
        }

        }catch(\Exception $e)
        {
             // Log the error and redirect back with an error message
            Log::error('An Error occurr during Delete junior engineer data' . $e->getMessage());

            return redirect()->back()->with('error' , 'An Error occurr during Delete Junior engineer data');
        }

}

}
