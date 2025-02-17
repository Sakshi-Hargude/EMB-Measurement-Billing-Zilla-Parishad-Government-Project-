<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\PublicDivisionId;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Log;


class ABController extends Controller
{

    // Class for the AB(auditor) master data
    public function FunindexAB(Request $request)
    {

        try{
        // dd('ok');
        // * Display a listing of the AAOs with their divisions, subdivisions, and designations.
        $users=DB::table('abmasters')->get();
        // dd($users);
        $divNames = [];
        $SubdivNames = [];
        
        foreach ($users as $user) {
            $userDivName = DB::table('divisions')
                ->where('div_id', $user->div_id)
                ->value('div');
        
            // Add the division name to the array
            $divNames[] = $userDivName;
        
            $userSubDivName = DB::table('subdivms')
                ->where('Div_Id', $user->div_id)
                ->where('Sub_Div_Id', $user->subdiv_id)
                ->value('Sub_Div');
        
            // Add the subdivision name to the array
            $SubdivNames[] = $userSubDivName;
        }
        
        // Pass both arrays to the view
        return view('listAB', ['users' => $users, 'divNames' => $divNames, 'SubdivNames' => $SubdivNames]);
    
        }catch(\Exception $e)
        {
          Log::error('An Error occurr during open list of Auditor' . $e->getMessage());
    
           $request->session()->flash('error', 'An Error occurr during open list of Auditor');
    
           return redirect('listworkmasters');
        }

        }


   //display the edit form for AB (Assistant Branch) based on AB_Id.
    public function FunEditAB(Request $request,$id)
    {


    try{

        // Fetch the division ID from a constant
        $divisionId = PublicDivisionId::DIVISION_ID;
        $DivisionOffer = $divisionId ."0";

        // dd($divisionId,$DivisionOffer);

        // Retrieve the AB master record based on AB_Id
        $user = DB::table('abmasters')->where('AB_Id', $id)->first();
        //  dd($user);

        // Retrieve the division name based on the division ID from the AB master record
        $Div=DB::table('divisions')
        ->select('div')
        ->where('div_id',$user->div_id)
        ->get();
        // dd($Div);

        // dd($user->Designation);
        // Retrieve the selected designation and its corresponding designation ID
        $SelectedDesignation=$user->designation;
        // dd($SelectedDesignation);
        $designatioid=DB::table('designations')
        ->where('designation',$user->designation)
        ->value('designationid');

        // Retrieve a list of designations with Designation_code = 5 (assuming this filters specific designations)
        $designationList=DB::table('designations')
        ->where('Designation_code',5)
        ->get();

        //dd($designatioid,$designationList,$SelectedDesignation);


        // Return the view with the user details, division name, designation list, and selected designation
        return view('UpdateAB',['user'=>$user,
        'Div'=>$Div,
        //  'SubDiv'=>$SubDiv,
        //  'SubDivList'=>$SubDivList,
        'designationList'=>$designationList,
        'SelectedDesignation'=>$SelectedDesignation

        ]);

        return view('UpdateAB');

    }
    catch(\Exception $e)
    {  // Log error and redirect back with error message on exception
        Log::error('An error Occurr During Open edit page' . $e->getMessage());

        return redirect()->back()->with('error' , 'An error Occurr During Open edit page');
    }
    
}

//Update the details of an AB (Assistant Branch) based on AB_Id.
public function FunUpdateauditor(Request $request, $abid)
{
    try{

     // Fetch the division ID from a constant
    $divisionId = PublicDivisionId::DIVISION_ID;
    // dd($divisionId);

     // Retrieve the AB master record based on AB_Id
     $user=DB::table('abmasters')->where('AB_Id' ,$abid)->first();


     // Retrieve the division ID based on the division name input from the form
     $Divname=$request->input('division_name');
     // dd($Divname);
                $divId=DB::table('divisions')
                ->select('div_id')
                ->where('div',$Divname)
                ->value('div_id');
                // dd($Divname,$divId);



                $abid=$user->AB_Id;
                // dd($ee_id);
                // Update the AB master record with the new data
                DB::table('abmasters')->where('AB_Id', $abid)->update([
                'div_id'=>$divId,
                'subname'=>$request->input('subname'),
                'name' => $request->input('ex_name'),
                'name_m' => $request->input('ee_name_M'),
                'designation' => $request->input('designation'),
                'period_from' => $request->input('charge_from'),
                'period_upto' => $request->input('charge_upto'),
                'pf_no' => $request->input('PF_no'),
                'phone_no' => $request->input('phone_no'),
                'email' => $request->input('email'),
                'username' => $request->input('user_name'),
                ]);

                // $user->update();
                // dd($ee_id);
                // Display a success message using Alert and redirect to the listAB route
                Alert::success('Success', 'Record updated successfully');

                return redirect('listAB');

        }
        catch(\Exception $e)
        {  // Log error and redirect back with error message on exception
            Log::error('An error Occurr During Update edit data' . $e->getMessage());

            return redirect()->back()->with('error' , 'An error Occurr During Update edit data');
        }
}


//view auditor data
public function FunViewAB(Request $request,$id)
{
// dd($id);

try{

    // Fetch the division ID from a constant (assuming PublicDivisionId::DIVISION_ID returns the division ID)
    $divisionId = PublicDivisionId::DIVISION_ID;
    $DivisionOffer = $divisionId ."0";

// dd($divisionId,$DivisionOffer);

    // Retrieve the AB master record based on AB_Id
    $user = DB::table('abmasters')->where('AB_Id', $id)->first();
    //  dd($user);

    // Retrieve the division name based on the division ID from the AB record
    $Div=DB::table('divisions')
    ->select('div')
    ->where('div_id',$user->div_id)
    ->get();
    // dd($Div);

    // dd($user->Designation);
    // Retrieve the selected designation and its corresponding designation ID
    $SelectedDesignation=$user->designation;
    // dd($SelectedDesignation);

    $designatioid=DB::table('designations')
    ->where('designation',$user->designation)
    ->value('designationid');

    // Retrieve the list of designations filtered by Designation_code (assuming Designation_code 5 is relevant)
    $designationList=DB::table('designations')
    ->where('Designation_code',5)
    ->get();

    //dd($designatioid,$designationList,$SelectedDesignation);

    // Return the view with the retrieved data
    return view('ViewAb',['user'=>$user,
    'Div'=>$Div,
    //  'SubDiv'=>$SubDiv,
    //  'SubDivList'=>$SubDivList,
    'designationList'=>$designationList,
    'SelectedDesignation'=>$SelectedDesignation

]);


}
catch(\Exception $e)
{// Log error and redirect back with error message on exception
   Log::error('An Error Occurr During open view page' . $e->getMessage());
   return redirect()->back()->with('error','An error occurr during open view page');
}

}


}