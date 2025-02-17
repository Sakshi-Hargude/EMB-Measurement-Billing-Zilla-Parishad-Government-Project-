<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\PublicDivisionId;
use Illuminate\Support\Facades\DB;
// use RealRashid\SweetAlert\Facades\Alert;
// use App\Helpers\PublicDivisionId;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Log;


//Sdc mastar data controller
class SdcController extends Controller
{
   // Function to display the list of SDC records along with their divisions and subdivisions
    public function FunindexSDC(Request $request)
    {
        try{
          // Retrieve all records from the 'sdcmasters' table
        $users=DB::table('sdcmasters')->get();
        // Arrays to hold division names and subdivision names
        $divNames = [];
        $SubdivNames = [];
           // Iterate through each user to fetch their division and subdivision names
        foreach ($users as $user) {
             // Get the division name based on div_id
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
        return view('listsdc', ['users' => $users, 'divNames' => $divNames, 'SubdivNames' => $SubdivNames]);
    
    }
    catch(\Exception $e)
        {  // Log the error and display a flash message if an exception occurs
          Log::error('An Error occurr during open list of SDC' . $e->getMessage());

           $request->session()->flash('error', 'An Error occurr during open list of SDC');

           return redirect('listworkmasters');
        }
    }

    // Function to populate dropdown lists for the SDC form
    public function FunDropdownselectInsertsdc(Request $request)
    {
        // Get the division ID from a predefined constant
       $divisionId = PublicDivisionId::DIVISION_ID;
        // Fetch the division name based on the division ID
       $DBDivlist=DB::table('divisions')
       ->where('div_id',$divisionId)
       ->value('div');
          // Fetch all subdivisions for the division
       $DBSubDivlist=DB::table('subdivms')
       ->select('Sub_Div')
       ->where('Div_Id',$divisionId)
       ->get();
       // Fetch all designations with code 2
       $DBDesignation = DB::table('designations')
       ->select('Designation')
       ->where('Designation_code', 2)
       ->get();
       // Pass the retrieved data to the view
       return view('sdc',compact('DBDivlist','DBSubDivlist','DBDesignation'));

    }

    // Function to insert a new SDC engineer record
    public function FuninsertSDCengineer(Request $request)
    {

              // Retrieve input values from the request
              $division=$request->divname;
            //  dd($division);
             $subdivision=$request->Subdivname;
            //  dd($subdivision);

               // Get division ID based on the division name
        $divid=DB::table('divisions')->where('div' ,  $division)->get()->value('div_id');

         // Get subdivision ID based on the subdivision name
        $subdivid=DB::table('subdivms')->where('Sub_Div' ,  $subdivision)->get()->value('Sub_Div_Id');
        // Generate a unique SDC ID
        $maxsdcid = DB::table('sdcmasters')
        ->where('subdiv_id',$subdivid)
        ->max('SDC_id');
        // dd($maxsdcid);

        if ($maxsdcid !== null && is_numeric($maxsdcid)) {
            // Extract the last three digits, increment, and pad with zeros
            $incrementedLastThreeDigits = str_pad((int)substr($maxsdcid, -2) + 1, 2, '0', STR_PAD_LEFT);
            // dd($incrementedLastThreeDigits);

            // Assuming $subdivid is defined somewhere in your code
            $FinalJe_id = $subdivid . $incrementedLastThreeDigits;
        } else {
            // If max value is not found or is not numeric, set a default value
            $FinalJe_id = $subdivid . '01';
        }
        // dd($FinalJe_id);


        $password=$request->password;
        // dd($password);

        $pfnumber=$request->pf_number;


        $ispfno=$request->pf_number_value;

        
           // Generate or use provided PF number
        if($ispfno == 0)
        {
          $previouspfnumber=DB::table('sdcmasters')
          ->where('div_id' , $divid)
          ->where('ispfno' , 0)
          ->orderBy('pf_no', 'desc')
          ->first('pf_no');
       //dd($previouspfnumber);

       if ($previouspfnumber) {
        // Generate new bill ID
        $lastFourDigits = substr($previouspfnumber->pf_no, -4);
        $newLastFourDigits = str_pad((intval($lastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
        $newpfnumber = substr_replace($previouspfnumber->pf_no, $newLastFourDigits, -4);
    
            }
        else
        {       // Set default PF number if none found
               $newpfnumber = $divid.'0001';
               //dd($newpfnumber);
         }


        }

        else
        {

                 // Use the provided PF number
            $newpfnumber= $pfnumber;
            //dd($newpfnumber);
        }
        //dd($newpfnumber);

         // Insert the new SDC engineer record into the database
        $Juniorengineer=DB::table('sdcmasters')->insert([
            'div_id'=>$divid,
            'subdiv_id'=>$subdivid,
            'SDC_id' => $FinalJe_id,
            'designation'=>$request->designation, 
            'period_from'=>$request->chargefrom,
            'period_upto'=>$request->chargeupto,
            'pf_no' => $newpfnumber,
            'phone_no'=>$request->mobileno,
            'email'=>$request->email,
            'username'=>$request->username,
            // 'password'=>$request->password,
            'name'=>$request->name,
            'ispfno' => $ispfno
        ]);
             // Display a success message and redirect
        Alert::success('Congrats', 'You\'ve Succesfully add the data');
        // return redirect('juniorengineer');
        // $users=DB::select('select * from jemasters');
        return redirect('listSDC');

        // return view('listjuniorengineer',['users'=>$users]);

    }




    
    // FuneditSDCengineer
    public function FuneditSDCengineer(Request $request,$SDC_id)
    {
        // dd("ok......");

    try{
         // Fetch the SDC engineer data based on SDC_id
        $user=DB::table('sdcmasters')->where('SDC_id' ,$SDC_id)->first();
       // Fetch the division name based on the division ID from the user data
        $Div=DB::table('divisions')
        ->select('div')
        ->where('div_id',$user->div_id)
        ->get();
    //   / dd($Div);

       // Fetch the subdivision name based on the subdivision ID from the user data
        $SubDiv=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('Sub_Div_Id',$user->subdiv_id)
        ->get();
       //dd($SubDiv);

        // Fetch the list of subdivisions for a specific division (hardcoded division_id=147)
        $SubDivList=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('div_id',147)
        ->get();
        //   /dd($SubDiv,$SubDivList);

           // Fetch designation list with code 6
        $designationList = DB::table('designations')->where('Designation_code', 6)->get();
        //dd($designationList);
        
         // Get the selected designation from user data
        $selectedDesignation = $user->designation ?? '';
       //dd($selectedDesignation);

        // Pass the retrieved data to the edit view
       return view('UpdateSdc',['user' => $user, 'Div' => $Div, 'SubDivList'=>$SubDivList,'SubDiv' => $SubDiv,'designationList'=>$designationList,'selectedDesignation'=>$selectedDesignation]);
    
    }catch(\Exception $e)
    {
          // Log the error and redirect back with an error message if an exception occurs
      Log::error('An Error occurr during open edit of SDC' . $e->getMessage());

       //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');

       return redirect()->back()->with('error' , 'An Error occurr during open edit of SDC data');
    }
    }

  // Function to update SDC engineer details
    public function FunSdcUpdate(Request $request,$SDC_id)
    {
        try{

         // Fetch the current SDC record based on SDC_id
        $user=DB::table('sdcmasters')->where('SDC_id' ,$SDC_id)->first();
        // Retrieve input values from the request
        $Divname=$request->input('division_name');
        // Fetch the division ID based on division name
        $divId=DB::table('divisions')
        ->select('div_id')
        ->where('div',$Divname)
        ->value('div_id');
        // dd($Divname,$divId);


        $subDivname=$request->input('subdivision_name');

     // Fetch the subdivision ID based on subdivision name
        $subdivId=DB::table('subdivms')
        ->select('Sub_Div_Id')
        ->where('Sub_Div',$subDivname)
        ->value('div_id');
       //dd($subDivname,$subdivId);

        $SDC_id=$user->SDC_id;

// sdc masters field  to be updated........
         // Update the SDC record with new data
        DB::table('sdcmasters')->where('SDC_id' ,$SDC_id)->update([
            'subdiv_id'=>$subdivId,
            'SDC_id' => $SDC_id,
            'subname' => $request->input('subname'),
            'name' => $request->input('ex_name'),
            'name_m' => $request->input('sdc_name_M'),
            'period_from' => $request->input('charge_from'),
            'period_upto' => $request->input('charge_upto'),
            'phone_no' => $request->input('phone_no'),
            'email' => $request->input('email'),
            'pf_no' => $request->input('PF_no'),
            'designation' => $request->input('designation'),
            'username' => $request->input('user_name'),
        ]);
                // $user->update();

               // Display success message and redirect
                Alert::success('Success', 'Successfully Updated....')->autoclose(30000);



        return redirect('listSDC');


        
     }
        catch(\Exception $e)
        {
               // Log the error and redirect with an error message if an exception occurs
            Log::error('An Error occurr during Update SDC data' . $e->getMessage());

            //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');

            return redirect()->back()->with('error' , 'An Error occurr during Update SDC data');
        }


    }


      // Function to delete an SDC engineer record
        public function FunSdcDelete(Request $request,$SDC_id)
        {   
            try{
               // Fetch the user ID associated with the SDC record
                $userid=DB::table('sdcmasters')->where('SDC_id', $id)->first('userid');

                // Delete the user from the 'users' table
               DB::table('users')->where('id', $userid->userid)->delete();
       
                      // Delete the SDC record from 'sdcmasters'
                    $query = DB::table('sdcmasters')
                    ->where('SDC_id', $SDC_id)
                    ->delete();

                   // return back as ir is page
                    return back();


                }catch(\Exception $e)
                {     // Log the error and redirect with an error message if an exception occurs
                    Log::error('An Error occurr during Delete SDC data' . $e->getMessage());

                    return redirect()->back()->with('error' , 'An Error occurr during Delete SDC data');
                }
        }


     // Function to view details of an SDC engineer
        public function FunViewSdc($SDC_id)
        {

            try{

             // Fetch the SDC record based on SDC_id
            $user=DB::table('sdcmasters')->where('SDC_id' ,$SDC_id)->first();
             // Fetch the division and subdivision names based on IDs from the SDC record
             $Div=DB::table('divisions')
             ->select('div')
             ->where('div_id',$user->div_id)
             ->first();
            //dd($Div->div);
     
     
             $SubDiv=DB::table('subdivms')
             ->select('Sub_Div')
             ->where('Sub_Div_Id',$user->subdiv_id)
             ->first();
            // /dd($SubDiv);
           // Pass the data to the view
           return view('viewSdc',['user'=>$user,'Div'=>$Div,'SubDiv'=>$SubDiv]);

        }
        catch(\Exception $e)
        {
             // Log the error and redirect with an error message if an exception occurs
            Log::error('An Error occurr during Show SDC data' . $e->getMessage());

            //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');
 
            return redirect()->back()->with('error' , 'An Error occurr during Show SDC data');
         }
        }

        
    }









