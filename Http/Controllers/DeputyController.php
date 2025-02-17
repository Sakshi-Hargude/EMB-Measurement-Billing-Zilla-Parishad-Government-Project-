<?php

namespace App\Http\Controllers;

use App\Models\Deputy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller; 
use RealRashid\SweetAlert\Facades\Alert;
use App\Helpers\PublicDivisionId;
use Illuminate\Support\Facades\Log;


// Deputy engineer master data class
class DeputyController extends Controller
{
     // Method for displaying list of deputy engineers
    public function funindexdeputyEng(Request $request) 
    {

         // Fetch all deputy engineers
        $userdata = DB::table('dyemasters')->get()->toArray();
        try{
        // Iterate over each deputy engineer to add division and subdivision names
        $users = DB::table('dyemasters')->get()->toArray();
        //    dd($users);
            foreach ($users as $user) {
                $div_id = $user->div_id; // Accessing div_id for the current user
                $subdiv_id = $user->subdiv_id; // Accessing subdiv_id for the current user
            // For example, you might want to retrieve related division and subdivision names
                $division = DB::table('divisions')->where('div_id', $div_id)->value('div');
                $subdivision = DB::table('subdivms')->where('Sub_Div_Id', $subdiv_id)->value('Sub_Div');

            // Assign division and subdivision names to user object
                $user->division_name = $division;
                $user->subdivision_name = $subdivision;
            }
            // dd($users);
    
            // Return view with deputy engineers data
         return view('viewdeputy', ['users' => $users]);
         return $users;

        }
        catch(\Exception $e)
        {
            // Log error and redirect with error message on exception
          Log::error('An Error occurr during open list of deputy' . $e->getMessage());

           $request->session()->flash('error', 'An Error occurr during open list of deputy');

           return redirect('listworkmasters');
        }

     }


      // Method for displaying deputy creation form with dropdown data
     public function funDropdowndeputyEng(Request $data)
     {
         // Fetch division name from PublicDivisionId constant
        $divisionId = PublicDivisionId::DIVISION_ID;
      
         // Fetch subdivision names based on division ID
        $DBDivlist=DB::table('divisions')
        ->where('div_id',$divisionId)
        ->value('div');
        // dd($DBDivlist);
        $DBSubDivlist=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('Div_Id',$divisionId)
        ->get();

         // Fetch designations for dropdown
        $DBDesignation = DB::table('designations')
        ->select('Designation')
        ->where('Designation_code', 2)
        ->get();
       
        // Return view with dropdown data
        return view('deputy',compact('DBDivlist','DBSubDivlist','DBDesignation'));

        // dd('ok');
     }

     // Method for creating a new deputy engineer
     public function funCreateDeputyEng(Request $data)
     {
         // Validate and process form data to create a new deputy engineer

        // Retrieve division ID from division name
        $divname=$data->divname;
        // dd($divname);
        $divid=DB::table('divisions')
        ->where('div',$divname)
        ->value('div_id');
        // dd($divid);
        // Retrieve subdivision ID from subdivision name and division ID
        $DBSubDivname=$data->Subdivname;
        // dd($DBSubDivname);
        $SubDivId=DB::table('subdivms')
        ->where('Div_Id',$divid)
        ->where('Sub_Div',$DBSubDivname)
        ->value('Sub_Div_Id');
        // dd($SubDivId);

         // Generate a new deputy engineer ID based on subdivision ID and incrementing number
        $dye_id = DB::table('dyemasters')->max('dye_id');
        // dd($eeid);
        if ($dye_id !== null && is_numeric($dye_id)) {
            $incrementedLastTwoDigit = str_pad((int)substr($dye_id, -2) + 1, 2, '0', STR_PAD_LEFT);
            // dd($incrementedLastTwoDigit);
            $FinalDyeid = $SubDivId . $incrementedLastTwoDigit;
        } else {
            // If max value is not found or is not numeric, set a default value
            $FinalDyeid = $SubDivId . '01';
        }        
        // dd($FinalEEid);

// dd($DSdivision);
    // Create the ExecutiveEng record
    DB::table('dyemasters')->insert([


             'div_id' => $divid,
             'subdiv_id' => $SubDivId,
            'dye_id'=>$FinalDyeid,
             'Subname' => $data->dename_categary,
             'name'=>$data->dpt_name,
             'name_m'=>$data->dpt_name_marathi,
             'designation'=>$data->designation,
             'period_from' => $data->charge_from,
             'period_upto'=>$data->charge_upto,
             'phone_no'=>$data->phone_no,
             'email'=>$data->email,
             'user_name'=>$data->user_name,
             'pwd'=>$data->pwd,
         ]);
 
         // Show success message using SweetAlert and redirect to list view
         Alert::success('Success', 'You\'ve Successfully Registered');
        //  return view('viewdeputy');
        return redirect('listdeputy');
                //  return view('deputy');


 
 
 
     }
 
 

     
    // Method for opening edit page of a deputy engineer
     public function funEditDeputyEng($id)
     {


        try{
          // Fetch deputy engineer details by ID
        $user=DB::table('dyemasters')->where('dye_id' ,$id)->first();
        
          // Fetch division name of the deputy engineer
        $Div=DB::table('divisions')
        ->select('div')
        ->where('div_id',$user->div_id)
        ->get();
        // dd($Div);

       // Fetch subdivision name of the deputy engineer
        $SubDiv=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('Sub_Div_Id',$user->subdiv_id)
        ->get();
        // dd($SubDiv);

        // Fetch all subdivision names for dropdown
        $SubDivList=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('div_id',147)
        ->get();
        // dd($SubDiv,$SubDivList);

        // Fetch designations for dropdown
        $designationList = DB::table('designations')->where('Designation_code', 2)->get();
        $selectedDesignation = $user->designation ?? '';

          // Return view with deputy engineer data for editing
        return view('updatedeputy',['user'=>$user,
        'Div'=>$Div,
        'SubDiv'=>$SubDiv,
        'SubDivList'=>$SubDivList,
        'designationList'=>$designationList,
        'selectedDesignation'=>$selectedDesignation
      ]);

        }catch(\Exception $e)
        {
          Log::error('An Error occurr during open list of deputy' . $e->getMessage());

           //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');

           return redirect()->back()->with('error' , 'An Error occurr during open edit of deputy data');
        }

    }




 // Method for updating deputy engineer details
public function FunDeputyUpdate(Request $request, $dye_id)
    {

        try{

           // Fetch the deputy engineer record to update   
        $user=DB::table('dyemasters')->where('dye_id' , $dye_id)->first();

// dd($user);
             // Retrieve division ID based on division name from request
            $Divname=$request->input('division_name');
            // dd($Divname);
            $divId=DB::table('divisions')
            ->select('div_id')
            ->where('div',$Divname)
            ->value('div_id');
            // dd($Divname,$divId);

            // Retrieve subdivision ID based on subdivision name from request
            $subDivname=$request->input('subdivision_name');
            // dd($subDivname);
            $subdivId=DB::table('subdivms')
            ->select('Sub_Div_Id')
            ->where('Sub_Div',$subDivname)
            ->value('div_id');
            // dd($subDivname,$subdivId);

            $dye_id=$user->dye_id;
            // dd($dye_id);

            // Update deputy engineer record in the database
            DB::table('dyemasters')->where('dye_id', $dye_id)->update([
                'div_id'=>$divId,
                'subdiv_id'=>$subdivId,
                'name' => $request->input('dpt_name'),
                'name_m' => $request->input('dpt_name_M'),
                'designation' => $request->input('designation'),
                'period_from' => $request->input('charge_from'),
                'period_upto' => $request->input('charge_upto'),
                'pf_no' => $request->input('PF_no'),
                'phone_no' => $request->input('phone_no'),
                'email' => $request->input('email'),
                'user_name' => $request->input('user_name'),
            ]);
        // $user->update();
          // Show success message using SweetAlert and redirect to list view
        Alert::success('Success', 'You\'ve Successfully Registered');

        return redirect('listdeputy');

        }
        catch(\Exception $e)
        {
              // Log error and redirect with error message on exception
            Log::error('An Error occurr during Update Deputy data' . $e->getMessage());

            //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');
 
            return redirect()->back()->with('error' , 'An Error occurr during Update Deputy data');
         }

    }



      // Method for displaying details of a deputy engineer
    public function FunShowDeputyEng($id)
    {

        try{
         // Fetch deputy engineer details by ID
       $user=DB::table('dyemasters')->where('dye_id' ,$id)->first();

        // Fetch division name of the deputy engineer
       $Div=DB::table('divisions')
       ->where('div_id',$user->div_id)
       ->value('div');
    //    dd($Div);

         // Fetch subdivision name of the deputy engineer
       $SubDiv=DB::table('subdivms')
       ->where('Sub_Div_Id',$user->subdiv_id)
       ->value('Sub_Div');
    //    dd($SubDiv);


       // Return view with deputy engineer data for display
       return view('showdeputy',['user'=>$user,'Div'=>$Div,'SubDiv'=>$SubDiv]);


   }
        catch(\Exception $e)
        {
             // Log error and redirect with error message on exception
            Log::error('An Error occurr during Show Deputy data' . $e->getMessage());

            //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');
 
            return redirect()->back()->with('error' , 'An Error occurr during Show Deputy data');
         }

    }


    
      // Method for deleting a deputy engineer
     public function funDeleteDeputyEng($id)
     {
        try{
        // dd($id);
        // Fetch user ID associated with the deputy engineer
         $userid=DB::table('dyemasters')->where('dye_id', $id)->first('userid');

        // Delete associated user record
        DB::table('users')->where('id', $userid->userid)->delete();

         // Delete deputy engineer record
        $query = DB::table('dyemasters')
        ->where('dye_id', $id)
        ->delete();

        // Redirect back after deletion
        return back();

    
        }catch(\Exception $e)
        {
            Log::error('An Error occurr during Delete Deputy data' . $e->getMessage());
 
            return redirect()->back()->with('error' , 'An Error occurr during Delete Deputy data');
        }
       
     }



}
