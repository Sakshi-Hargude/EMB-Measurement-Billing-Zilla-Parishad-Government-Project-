<?php

namespace App\Http\Controllers;

use App\Models\ExecutiveEng;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use DB;
use RealRashid\SweetAlert\Facades\Alert;
use App\Helpers\PublicDivisionId;
use Illuminate\Support\Facades\Log;




//executive engineer master data
class ExecutiveEngController extends Controller
{
    
    // Method to prepare data before creating a new executive engineer
    public function funBeforCreateExecutiveEng(Request $data)
    {
        // dd('ok');
        $divisionId = PublicDivisionId::DIVISION_ID;
       
        // Fetch division name based on division ID
        $DBDivlist=DB::table('divisions')
        ->where('div_id',$divisionId)
        ->value('div');
        // dd($DBDivlist);
         // Fetch all subdivision names for the selected division
        $DBSubDivlist=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('Div_Id',$divisionId)
        ->get();
        // dd($DBSubDivlist);
         // Fetch designation based on designation code (e.g., 1 for Executive Engineer)
        $DBDesignation = DB::table('designations')
        ->select('Designation')
        ->where('Designation_code', 1)
        ->get();
        // dd( $DBDesignation);
        return view('executive',compact('DBDivlist','DBSubDivlist','DBDesignation'));

    }

     // Method to create a new executive engineer record
    public function funCreateExecutiveEng(Request $data)
    {
        // $divisionId = PublicDivisionId::DIVISION_ID;
        // dd($divisionId);
        $divname=$data->divname;

          // Fetch division ID based on division name
        $divid=DB::table('divisions')
        ->where('div',$divname)
        ->value('div_id');
        // dd($divid);
        $DBSubDivname=$data->Subdivname;
        
         // Fetch subdivision ID based on division ID and subdivision name
        $SubDivId=DB::table('subdivms')
        ->where('Div_Id',$divid)
        ->where('Sub_Div',$DBSubDivname)
        ->value('Sub_Div_Id');

            $eeid=$maxId = DB::table('eemasters')->max('eeid');

            // Generate a unique EEID (Executive Engineer ID)
            if ($eeid !== null && is_numeric($eeid)) {
                $incrementedLastTwoDigit = str_pad((int)substr($eeid, -2) + 1, 2, '0', STR_PAD_LEFT);
                // dd($incrementedLastTwoDigit);
                $FinalEEid = $divid . $incrementedLastTwoDigit;
            } else {
                // If max value is not found or is not numeric, set a default value
                $FinalEEid = $divid . '01';
            }        
            // dd($FinalEEid);

    // dd($DSdivision);
        // Insert the new executive engineer record into the database
        DB::table('eemasters')->insert([
            'divid'=>$divid,
            // 'subdiv_id'=>$SubDivId,
            'eeid'=>$FinalEEid,
            'subname' => $data->exname_categary,
            'name' => $data->ex_name,
            'name_m'=>$data->ex_name_M,
            'period_from' => $data->charge_from,
            'period_upto' => $data->charge_upto,
            'pf_no'=>$data->pf_no,
            'phone_no' => $data->phone_no,
            'email' => $data->email,
            'user_name' => $data->user_name,
            'Designation'=>$data->designation,
            'pwd' => $data->pwd,
        ]);    
        // Flash a success message and redirect to the view
        Alert::success('Success', 'You\'ve Successfully Registered');
        // return view('viewexecutive')->with(compact('DSdivision','DBDivisionslist','users'));
        return redirect('listexecutive');

    }
    
     // Method to display a list of executive engineers
    public function funindexexecutiveEng( Request $request) 
    {
        try{
 
        $divisionId = PublicDivisionId::DIVISION_ID;
        // dd($divisionId);
       

         // Fetch all executive engineers along with their associated division and subdivision names
        $users = DB::table('eemasters')->get()->toArray();
    //    dd($users);
    $DBDivisions=DB::table('divisions')
    ->where('div_id',$divisionId)
    ->value('div');
    // dd($DBDivisions);
        foreach ($users as $user) {
            $div_id = $user->divid; // Accessing div_id for the current user
        
        // For example, you might want to retrieve related division and subdivision names
            $division = DB::table('divisions')->where('div_id', $div_id)->value('div');
             // Fetch subdivision name based on subdivision ID (if applicable)
            $subdivision = DB::table('subdivms')->value('Sub_Div');
        // dd($division,$subdivision);
            $user->division_name = $division;
            $user->subdivision_name = $subdivision;
        }
        // dd($users);

         return view('viewexecutive', ['users' => $users,'DBDivisions'=>$DBDivisions]);


    }
    catch(\Exception $e)
        {
          Log::error('An Error occurr during open list of executive' . $e->getMessage());

           $request->session()->flash('error', 'An Error occurr during open list of executive');

           return redirect('listworkmasters');
        }

     }



 // Method to display the edit form for an executive engineer
     public function FunEditExecutiveEng($id)
     {
        try{

        $divisionId = PublicDivisionId::DIVISION_ID;
       // dd($divisionId);

        // Fetch executive engineer details based on EEID
        $user = DB::table('eemasters')->where('eeid', $id)->first();
        // return $user;
        // dd($user);

         // Fetch division name based on division ID of the executive engineer
        $Div=DB::table('divisions')
        ->select('div')
        ->where('div_id',$user->divid)
        ->get();
        // dd($Div);

       // Fetch all subdivision names for the selected division
        $SubDiv=DB::table('subdivms')
        ->select('Sub_Div')
        // ->where('Sub_Div_Id',$user->subdiv_id)
        ->get();
        // dd($SubDiv);

         // Fetch subdivision list for the selected division
        $SubDivList=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('div_id',$divisionId)
        ->get();
        // dd($SubDiv,$SubDivList);
      // Fetch selected designation and designation list based on designation code
        $SelectedDesignation=$user->Designation;
        // dd($SelectedDesignation);
        $designatioid=DB::table('designations')
        ->where('designation',$user->Designation)
        ->value('designationid');

        $designationList=DB::table('designations')
        ->where('Designation_code',1)
        ->get();

        // dd($user->Designation,$designatioid,$designationList);


        return view('updateexecutive',['user'=>$user,
        'Div'=>$Div,
        'SubDiv'=>$SubDiv,
        'SubDivList'=>$SubDivList,
        'designationList'=>$designationList,
        'SelectedDesignation'=>$SelectedDesignation
    
    ]);

        }catch(\Exception $e)
        {
        Log::error('An Error occurr during open list of executive' . $e->getMessage());

        //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');

        return redirect()->back()->with('error' , 'An Error occurr during open edit of executive data');
        }
     }


    // Method to update an executive engineer record
     public function FunUpdateExecutiveEng(Request $request, $ee_id)
    {

        try{

        $divisionId = PublicDivisionId::DIVISION_ID;
       // dd($divisionId);

       // Fetch executive engineer details based on EEID
        $user=DB::table('eemasters')->where('eeid' ,$ee_id)->first();

         //dd($request);
                // Fetch division ID based on division name
                $Divname=$request->input('division_name');
                // dd($Divname);
        $divId=DB::table('divisions')
        ->select('div_id')
        ->where('div',$Divname)
        ->value('div_id');
        // dd($Divname,$divId);
        
        
        
        $ee_id=$user->eeid;

        // Update executive engineer record
        DB::table('eemasters')->where('eeid', $ee_id)->update([
            'divid'=>$divId,
            'subname'=>$request->input('subname'),
            'name' => $request->input('ex_name'),
            'name_m' => $request->input('ee_name_M'),
            'Designation' => $request->input('designation'),
            'period_from' => $request->input('charge_from'),
            'period_upto' => $request->input('charge_upto'),
            'pf_no' => $request->input('PF_no'),
            'phone_no' => $request->input('phone_no'),
            'email' => $request->input('email'),
            'user_name' => $request->input('user_name'),
            'pwd' => $request->input('pwd'),
        ]);
        
        // $user->update();
        // Flash success message and redirect to the executive list
        Alert::success('Success', 'Record updated successfully');

        return redirect('listexecutive');


       }catch(\Exception $e)
        {
           
            Log::error('An Error occurr during Update Executive data' . $e->getMessage());

            //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');
 
            return redirect()->back()->with('error' , 'An Error occurr during Update Executive data');
         }
    }

    // Method to view an executive engineer record
    public function FunshowExecutiveEng($id)
    {
        $divisionId = PublicDivisionId::DIVISION_ID;
        // dd($divisionId);
        // Fetch executive engineer details based on EEID
        $user = DB::table('eemasters')->where('eeid', $id)->first();

       // Fetch division name based on division ID of the executive engineer
        $Div=DB::table('divisions')
        ->where('div_id',$user->divid)
        ->value('div');
        // dd($user,$user->divid,$Div);

        // dd($Div);


       return view('showexecutive',['user'=>$user,'Div'=>$Div]);
    }


 // Method to Delete an executive engineer record
     public function funDeleteExecutiveEng($id)
     {
        //dd($id);
        try{
             // Fetch the user ID associated with the executive engineer record
        $userid=DB::table('eemasters')->where('eeid', $id)->first('userid');

          // Delete user record (assuming 'users' table) based on the fetched user ID
        DB::table('users')->where('id', $userid->userid)->delete();
         
        // Delete executive engineer record based on EEID
         DB::table('eemasters')->where('eeid', $id)->delete();



        return redirect('listexecutive');

        }catch(\Exception $e)
        {
            Log::error('An Error occurr during Delete executive data' . $e->getMessage());

            return redirect()->back()->with('error' , 'An Error occurr during Delete executive data');
        }
   
     }
 
     

}
