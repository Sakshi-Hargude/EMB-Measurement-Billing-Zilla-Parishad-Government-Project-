<?php

namespace App\Http\Controllers;
use App\Models\Agency;

use Illuminate\Http\Request;
use App\Helpers\PublicDivisionId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\User;
use Illuminate\Support\Facades\Log;

 //class used for Agency master data
class AgencyController extends Controller
{
    // Function to create a new agency
    public function funcreateagency(Request $request)
    {

        try{
        //dd($request);
  // Get the division ID from the PublicDivisionId class or constant
        $divisionId = PublicDivisionId::DIVISION_ID;
        // dd($divisionId);

        // Retrieve the maximum ID from the 'agencies' table to generate a new agency ID
        $Agencyid=$maxId = DB::table('agencies')->max('id');
        // dd($Agencyid);
        if ($Agencyid !== null && is_numeric($Agencyid)) {
            // Increment the last two digits of the max ID for the new agency ID
            $incrementedLastTwoDigit = str_pad((int)substr($Agencyid, -4) + 1, 4, '0', STR_PAD_LEFT);
            // dd($incrementedLastTwoDigit);
            $FinalAgencyid = $divisionId . $incrementedLastTwoDigit;
        } else {
            // If max value is not found or is not numeric, set a default value
            $FinalAgencyid = $divisionId . '0001';
        }        

       
        // dd($FinalAgencyid);

//dd($request->agency_sign);
      // Handle file upload for agency signature
        if ($request->hasFile('agency_sign')) {
            
            $file = $request->file('agency_sign');
            //dd($file);
            // File name to store
            $fileNameToStore = time() . '_' . $file->getClientOriginalName();
            
            // Upload Image
            $file->move(public_path('Uploads/signature/'), $fileNameToStore);// Adjust storage path as needed
            //$path = $request->file('agency_sign')->store('public/agency_signs', $fileNameToStore);
        }

       
        //dd($fileNameToStore);
        // Encrypt the password for the agency user
        $encryptedPassword = Hash::make($request->Password);


 // Create a new user with agency details
        $user = User::create([
            'name' => $request->agency_nm,
            'email' => $request->Agency_Mail,
            'password' => $encryptedPassword,
            'mobileno' => $request->Agency_Phone,	
            'usertypes' => 'Agency',
            'Div_id' => $divisionId,
            'Usernm' => $request->User_Name,
            'Designation' => $request->Designation,
        ]);

        $lastInsertedUserId = $user->id; 



// Insert agency details into the 'agencies' table
         $agency=
            DB::table('agencies')->insert([

            'id'=>$FinalAgencyid,
            'agency_nm' => $request-> agency_nm,
            'Agency_Ad1'=> $request-> Agency_Ad1,
            'Agency_Ad2' => $request-> Agency_Ad2,
            'Agency_Pl' => $request-> Agency_Pl,
            'Agency_Mail' => $request-> Agency_Mail,
            'Agency_Phone' => $request-> Agency_Phone,	
            'User_Name' => $request-> User_Name,
            'Regi_No_Local' => $request-> Regi_No_Local,
            'Gst_no' => $request-> Gst_no,
            'Regi_Class' => $request-> Regi_Class,
            'Pan_no' => $request-> Pan_no,
            'Regi_Dt_Local' => $request-> Regi_Dt_Local,
            'Bank_nm' => $request-> Bank_nm,
            'Ifsc_no' => $request-> Ifsc_no,
            'Bank_br' => $request-> Bank_br,
            'Micr_no' => $request-> Micr_no,
            'Bank_acc_no' => $request-> Bank_acc_no,
            'Contact_Person1' => $request-> Contact_Person1,
            'C_P1_Phone' => $request-> C_P1_Phone,
            'C_P1_Mail' => $request-> C_P1_Mail,
            'userid' => $lastInsertedUserId,
            'agencysign' => $fileNameToStore

         ]);

          // Success alert for adding agency data
         Alert::success('Congrats', 'You\'ve Succesfully add the Agency data');
         return redirect('agency');

        }catch(\Exception $e)
        {
           Log::error('An error occurr During Create new agency data' . $e->getMessage());

            // Flash error message to session
        $request->session()->flash('error', 'An error occurr During Create new agency data');

        return redirect()->back(); // Redirect back to the previous page (agency form)
        }
    }


    //
    // public function index(Request $request) {
    //      $users = DB::table('agencies')->get();

    //     // dd($users);
    //     return view('listagencies',['users'=>$users]);

    // }



// Edit agency

// Function to edit agency details
    public function edit($id)
    {
        // Find the agency and associated user data by ID
        $users = Agency::find($id);

        $userdata = User::find($users->userid);

      // Return the edit agency view with agency and user data
     return view('editagency',['users'=>$users , 'userdata'=>$userdata]);  
    }


    // Update agency data based on the given ID
    public function update(Request $request, $id)
    {
        

        try {
            $users = Agency::find($id);

            // Retrieve the agency again (redundant) to handle file upload and update
            $agency = Agency::find($id);
            
             
              // Handle file upload for agency signature
            if ($request->hasFile('agency_sign')) {
                $file = $request->file('agency_sign');
                $fileNameToStore = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('Uploads/signature/'), $fileNameToStore);
                
                // If there's an old sign, delete it
                if ($agency->agencysign && file_exists(public_path('Uploads/signature/' . $agency->agencysign))) {
                    unlink(public_path('Uploads/signature/' . $agency->agencysign));
                }
                
                // Save the new sign
                $agency->agencysign = $fileNameToStore;
            }

             // Update agency fields with input values from the form
        $users->agency_nm = $request->input('agency_nm');
        $users->Agency_Ad1 = $request->input('Agency_Ad1');
        $users->Agency_Ad2 = $request->input('Agency_Ad2');
        $users->Agency_Pl = $request->input('Agency_Pl');
        $users->Agency_Mail = $request->input('Agency_Mail');
        $users->Agency_Phone = $request->input('Agency_Phone');
        $users->Regi_No_Local = $request->input('Regi_No_Local'); 
        $users->Gst_no = $request->input('Gst_no');
        $users->Regi_Class = $request->input('Regi_Class');
        $users->Pan_no = $request->input('Pan_no');
        $users->Regi_Dt_Local = $request->input('Regi_Dt_Local');
        $users->Bank_nm = $request->input('Bank_nm');
        $users->Ifsc_no = $request->input('Ifsc_no');
        $users->Bank_br = $request->input('Bank_br');
        $users->Micr_no = $request->input('Micr_no');
        $users->Bank_acc_no = $request->input('Bank_acc_no');
        $users->Contact_Person1 = $request->input('Contact_Person1');
        $users->C_P1_Phone = $request->input('C_P1_Phone');
        $users->C_P1_Mail = $request->input('C_P1_Mail');


         // Update associated user details
             User::where('id' , $users->userid)->update([
            'name' => $request->input('agency_nm'),
            'email' => $request->input('Agency_Mail'),
            'mobileno' => $request->input('Agency_Phone'),	
            'Designation' => $request->input('Designation'),
        ]);
        // $users->update();
         if($users->update())
        {
            Alert::success('Congrats', 'You\'ve Successfully Edit the data');
        }
       
          // Redirect to the list of agencies
        return redirect('listagencies');

    } catch (\Exception $e) {
        Log::error('Error updating agency: ' . $e->getMessage());
        return redirect()->back()->with('error', 'An error occurred while updating the agency.');
    }


}




// View agency details
public function viewagencydata($id)
{
    // Find the agency and associated user data by ID for viewing
    $users = Agency::find($id);

    $userdata = User::find($users->userid);
   
    // Return the view with agency and user data
 return view('view_agency',['users'=>$users , 'userdata'=>$userdata]);  
   
}


// Delete function to list agencies with 'isdelete' flag

public function del(Request $request)
    {
         // Paginate and retrieve agencies where 'isdelete' is set to 1 (deleted)
        $users = Agency::select("*")->where('isdelete','=',1)->paginate(10);
        return view('listagencies', compact('users'))->with('no', 1);
    }


// Delete agency function by setting 'isdelete' flag to 0
    public function deleteagency($id)
    { 
        
        
        // Find the user ID associated with the agency
        if($id)
        {
             // Delete the user associated with the agency
              $userid=DB::table('agencies')->where('id', $id)->first('userid');

            //dd($userid);
           DB::table('users')->where('id', $userid->userid)->delete();
           
            // Update 'isdelete' flag to 0 in the 'agencies' table to mark as deleted
        $query = DB::table('agencies')
              ->where('id', $id)
              ->update(['isdelete' => 0]);

        // Redirect back to the previous page
        return back();
        }
       
    }




}

