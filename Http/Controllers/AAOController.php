<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\PublicDivisionId;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Log;

// Class for the AAO master data
class AAOController extends Controller
{
    // * Display a listing of the AAOs with their divisions, subdivisions, and designations.
    public function FunindexAAO(Request $request)
    {
        try{
        // dd('ok');
         // Retrieve all DAO masters
        $users=DB::table('daomasters')->get(); 
        // dd($users);

         // Initialize arrays for division names, subdivision names, and designation names
        $divNames = [];
        $SubdivNames = [];
        $designationNames=[];
        
           // Iterate through each DAO master to fetch related division, subdivision, and designation names
        foreach ($users as $user) {
             // Fetch division name based on div_id
            $userDivName = DB::table('divisions')
                ->where('div_id', $user->div_id)
                ->value('div');
        
            // Add the division name to the array
            $divNames[] = $userDivName;
        
            $userSubDivName = DB::table('subdivms')
                ->where('Div_Id', $user->div_id)
                ->value('Sub_Div');
        
            // Add the subdivision name to the array
            $SubdivNames[] = $userSubDivName;

           // Fetch designation name based on designationid
            $userDEsignationName=DB::table('designations')
            ->where('designationid', $user->designation)
            ->value('Designation');
            $designationNames[]=$userDEsignationName;
     
        }
        
        // Pass both arrays to the view
        return view('listAAO', ['users' => $users, 'divNames' => $divNames,
         'SubdivNames' => $SubdivNames,
        'designationNames'=>$designationNames]);

      
    }catch(\Exception $e)
    {
      Log::error('An Error occurr during open list of deputy' . $e->getMessage());
    // Log error and redirect with flash message on exception
       $request->session()->flash('error', 'An Error occurr during open list of AAO');

       return redirect('listworkmasters');
    }
    }

    // public function FunDropdownselectInsertAAO(Request $data)
    // {
    //    $divisionId = PublicDivisionId::DIVISION_ID;
    //    // dd($divisionId);
    //    $DBDivlist=DB::table('divisions')
    //    ->where('div_id',$divisionId)
    //    ->value('div');
    //    // dd($DBDivlist);
    //    $DBSubDivlist=DB::table('subdivms')
    //    ->select('Sub_Div')
    //    ->where('Div_Id',$divisionId)
    //    ->get();
    //    // dd($DBSubDivlist);
    //    $DBDesignation = DB::table('designations')
    //    ->select('Designation')
    //    ->where('Designation_code', 2)
    //    ->get();
    //    // dd( $DBDesignation);
    //    return view('deputy',compact('DBDivlist','DBSubDivlist','DBDesignation'));

    //    // dd('ok');
    // }

//* Display the form for editing AAO details.    
    public function FunEditAAO(Request $data,$DAO_id)
    {

        try{
                // dd($DAO_id);

                 // Fetch the division ID from a constant
                $divisionId = PublicDivisionId::DIVISION_ID;
                // dd($divisionId);

                 // Retrieve the DAO master record based on DAO_id
                $user = DB::table('daomasters')->where('DAO_id', $DAO_id)->first();
                // dd($user);

                // Retrieve the division name based on division ID
                $Div=DB::table('divisions')
                ->select('div')
                ->where('div_id',$divisionId)
                ->get();
                // dd($Div);

                // dd($SubDiv,$SubDivList);
                // dd($user->Designation);
                $SelectedDesignation=$user->designation;
                // $SelectedDesignation=DB::table('designations')
                // ->where('designationid',$designationid)
                // ->value('designation');


                // dd($SelectedDesignation);
               // Retrieve the list of designations where Designation_code is 4
                $designationList=DB::table('designations')
                ->where('Designation_code',4)
                ->select('Designation')
                ->get();
                // dd($SelectedDesignation,$designationList);


                 // Pass data to the view for editing AAO details
                return view('UpdateAAO',['user'=>$user,
                'Div'=>$Div,
                'designationList'=>$designationList,
                'SelectedDesignation'=>$SelectedDesignation ]);

        }catch(\Exception $e)
        {  // Log error and redirect back with flash message on exception
            Log::error('An error occurr during edit  box open' . $e->getMessage());

            return redirect()->back()->with('error' , 'An error occurr during edit  box open');
        }
    }

    
    //* Update AAO details based on DAO_id.
    public function FunUpdateAAO(Request $request,$DAO_id)
    {
        try {
        // dd($DAO_id);
        // Fetch the division ID from a constant
        $divisionId = PublicDivisionId::DIVISION_ID;
        // dd($divisionId);
 
      // Retrieve the DAO master record based on DAO_id
        $user = DB::table('daomasters')->where('DAO_id', $DAO_id)->first();
 
        //  dd($user);
         // Retrieve the division ID based on the division name from the request
         $Divname=$request->input('division_name');
                 // dd($Divname);
         $divId=DB::table('divisions')
         ->select('div_id')
         ->where('div',$Divname)
         ->value('div_id');
         // dd($Divname,$divId);
         
         
        //  dd($request->input('name'));
         $DAO_id=$user->DAO_id;
         // dd($DAO_id);
        //  dd($request->input('designation'));
$Designationname=$request->input('designation');

// dd($Designationname);


         // Update the DAO master record with the updated details from the request
         DB::table('daomasters')->where('DAO_id', $DAO_id)->update([
             'div_id'=>$divId,
             'subname'=>$request->input('subname'),
             'name' => $request->input('name'),
             'name_m' => $request->input('AAO_M'),
            //  'designation' => $request->input('designation'),
            'designation' => $Designationname,
             'period_from' => $request->input('charge_from'),
             'period_upto' => $request->input('charge_upto'),
             'pf_no' => $request->input('PF_no'),
             'phone_no' => $request->input('phone_no'),
             'email' => $request->input('email'),
             'username' => $request->input('user_name'),
         ]);
         
         // $user->update();
         // dd($ee_id);
          // Flash success message and redirect to the listAAO route on success
         Alert::success('Success', 'Record updated successfully');
 
         return redirect('listAAO');
 
 
        } catch (\Exception $e) {
              // Log error, flash error message, and redirect back on exception
            Log::error('Error updating AAO record: ' . $e->getMessage());

            Alert::error('Error', 'Failed to update record');
            return redirect()->back();
        }
    }

    // Display details of AAO (Deputy Accounts Officer) based on DAO_id.
    public function FunshowAAO(Request $request ,$DAO_id)
    {
        try {
        // dd($DAO_id);

         // Fetch the division ID from a constant
        $divisionId = PublicDivisionId::DIVISION_ID;
        // dd($divisionId);

         // Retrieve the DAO master record based on DAO_id
        $user = DB::table('daomasters')->where('DAO_id', $DAO_id)->first();
        // dd($user);
        // dd($user,$user->divid);

         // Retrieve the division name based on the division ID from the DAO master record
        $Div=DB::table('divisions')
        ->where('div_id',$user->div_id)
        ->value('div');
        // dd($user,$user->div_id,$Div);

        // Return the view with the user details and division name
        return view('showAAO',['user'=>$user,'Div'=>$Div]);
       
    } catch (\Exception $e) {
         // Log error and redirect back with error message on exception
        Log::error('Error fetching AAO record: ' . $e->getMessage());
       
        return redirect()->back()->with('error' , 'Failed to fetch record');
    }

    }

    
    // Delete AAO (Deputy Accounts Officer) based on DAO_id.
    public function  FunDeleteAAO(Request $request, $DAO_id)
    {
        try {
             // Retrieve the user ID associated with the DAO_id
            $userid=DB::table('daomasters')->where('DAO_id', $DAO_id)->first('userid');

            //dd($userid);
             // Delete the user record from the 'users' table based on user ID
           DB::table('users')->where('id', $userid->userid)->delete();

             // Delete the DAO master record from 'daomasters' table based on DAO_id
            DB::table('daomasters')->where('DAO_id', $DAO_id)->delete();

            // Redirect to the listAAO route after successful deletion
            return redirect('listAAO');

        } catch (\Exception $e) {
             // Log error, flash error message, and redirect back on exception
            Log::error('Error deleting AAO record: ' . $e->getMessage());
            Alert::error('Error', 'Failed to delete record');
            return redirect()->back();
        }

    }
}
