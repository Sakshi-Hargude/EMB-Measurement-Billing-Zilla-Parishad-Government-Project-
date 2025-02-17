<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\Subdivms;
use RealRashid\SweetAlert\Facades\Alert;
use DB;
use Illuminate\Support\Facades\Log;

//subdivision master controller
class SubdivisionController extends Controller
{

    // division list
public function getDivisions(Request $request)
{
     // Query the 'divisions' table to get the 'div_m' column where 'div_id' is 147
    $DSdivision = DB::table('divisions')
        ->where('div_id', 147)
        ->select('div_m')
        ->get();
// Add debugging code
    // Return the result as a JSON response
    return response()->json($DSdivision);
}
     // get subdivision list
    public function getSubDivision()
    {
        // Query the 'subdivms' table to get the 'Sub_Div_M' column where 'Div_Id' is 147
        $DSdivision = DB::table('subdivms')
        ->where('Div_Id',147)
         ->select('Sub_Div_M')
         ->get();
         // dd($circle,$DSdivision);
     return response()->json($DSdivision);

    }


    public function funCreate(Request $data)
    {
         // Retrieve the 'Reg_Id' from the 'regions' table where 'Reg_Id' is 1
        $RegionId = DB::table('regions')
            ->where('Reg_Id', 1)
            ->value('Reg_Id');
    
             // Retrieve the 'Cir_Id' from the 'circles' table where 'Cir_Id' is 14
        $circleId = DB::table('circles')
            ->where('Cir_Id', 14)
            ->value('Cir_Id');
    
             // Retrieve the 'div_id' from the 'divisions' table where 'div_id' is 147
        $divisionId = DB::table('divisions')
            ->where('div_id', 147)
            ->value('div_id');
    
             // Get the selected subdivision ID from the request data
        $selectedsubdiv = $data->input('Sub_Div_Id');

        // Retrieve the 'Sub_Div_Id' from the 'subdivms' table where 'Sub_Div_M' matches the selected subdivision ID
        $subdivisionId = DB::table('subdivms')
            ->where('Sub_Div_M', $selectedsubdiv)
            ->value('Sub_Div_Id');
    
        // Create the Subdivms record
        $subdivision = DB::table('subdivms')->insert([
            'Reg_Id' => $RegionId,
            'Cir_Id' => $circleId,
            'Div_Id' => $divisionId,
            'Sub_Div_Id' => $subdivisionId,
            'Sub_Div' => $data->Sub_Div_Id,
            'address1' => $data->address1,
            'address2' => $data->address2,
            'place' => $data->place,
            'email' => $data->email,
            'phone_no' => $data->phone_no,
            'designation' => $data->designation
        ]);
    
        // Debugging output
        // dd($subdivision);
    
        // Display success message and return view
        Alert::success('Success', 'You\'ve Successfully Registered');
        //return view page
        return view('subdivision', compact('RegionId', 'circleId', 'divisionId', 'subdivisionId'));
    }
    

    // list dub divisions
    public function index(Request $request) 
    {
        try{

              // Retrieve divisions where 'div_id' is 147
        $div=DB::table('divisions')
        ->select('div')
        ->where('div_id',147)
        ->get();
         // Retrieve subdivisions where 'Div_Id' is 147
        $subdiv=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('Div_Id',147)
        ->get();


 // Retrieve paginated list of subdivisions
        $users = Subdivms::paginate(10);

        $users = DB::table('subdivms')
        ->where('Div_Id',147)
        ->paginate(10);
        // Iterate through each paginated user and retrieve related division and subdivision names
            foreach ($users as $user) {
                $div_id = $user->Div_Id; // Accessing div_id for the current user
                $subdiv_id = $user->Sub_Div_Id; // Accessing subdiv_id for the current user
            // For example, you might want to retrieve related division and subdivision names
                $division = DB::table('divisions')->where('div_id', $div_id)->value('div');
                $subdivision = DB::table('subdivms')->where('Sub_Div_Id', $subdiv_id)->value('Sub_Div');
            // dd($division,$subdivision);
                $user->division_name = $division;
                $user->subdivision_name = $subdivision;
            }
    
//    // Return the 'viewsubdivision' view with paginated users and related data
         return view('viewsubdivision', compact('users','div','subdiv'));
        //  return $users;
    }
    catch(\Exception $e)
    {
      Log::error('An Error occurr during open list of deputy' . $e->getMessage());

         // Flash error message and redirect to 'listworkmasters'
       $request->session()->flash('error', 'An Error occurr during open list of Subdivision');

       return redirect('listworkmasters');
    }

     }

     //Edit subdivision list
     public function FunEditSubdivision($Sub_Div_Id)
     {
        try{

          // Retrieve the specific subdivision details using 'Sub_Div_Id'
        $subdiv=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('Sub_Div_Id' ,$Sub_Div_Id)
        ->first();

         // Retrieve a list of all subdivisions under 'div_id' 147
        $subdivlist=DB::table('subdivms')
        ->select('Sub_Div')
        ->where('div_id', 147)
        ->get();

          // Retrieve the division name where 'div_id' is 147
        // dd($subdiv,$subdivlist);
        $div = DB::table('divisions')
        ->select('div')
        ->where('div_id', 147)
        ->first();

     // Fetch the full user details based on 'Sub_Div_Id'
    $user = Subdivms::where('Sub_Div_Id', $Sub_Div_Id)->first();
    
     // Return the view for updating a subdivision with necessary data
    return view('updatesubdivision', [
        'user' => $user,
        'div' => $div, 
        'subdiv'=>$subdiv,
        'subdivlist'=>$subdivlist,
        
        ]);

            }catch(\Exception $e)
            {
                 // Log the exception message
            Log::error('An Error occurr during open list of Subdivision' . $e->getMessage());

            //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');
            // Redirect back with an error message if an exception occurs
            return redirect()->back()->with('error' , 'An Error occurr during open edit of Subdivision data');
            }
     }




// update the sub division
public function update(Request $request, $Sub_Div_Id)
    {
        try{

        // Extract data from the request
        $regionId=1;
        $CircleId=14;
        $divisionname = $request->input('division_name');
        // dd($divisionname);
        $DivId=DB::table('divisions')
        ->select('div_id')
        ->where('div',$divisionname)
        ->value('div_id');
        // dd($DivId);
        $subdiv=$request->input('subdivision_name');
        // dd($subdiv);
        $subdivid=DB::table('subdivms')
        ->select('Sub_Div_Id')
        ->where('Sub_Div',$subdiv)
        ->value('Sub_Div_Id');
        // dd($subdivid);
        $subdivname = $request->input('subdivision_name');
        $subdivnameM = $request->input('subdivision_nameM');

        // Additional inputs
        $address1 = $request->input('address1');
        $address2 = $request->input('address2');
        $place = $request->input('place');
        $email = $request->input('email');
        $phone_no = $request->input('phone_no');
        $designation = $request->input('designation');


       
        // Update the subdivision record in the database
        $user = DB::table('subdivms')
        ->where('Sub_Div_Id', $Sub_Div_Id)
        ->update([
            // 'Reg_Id'=>$regionId,
            // 'Cir_Id'=>$CircleId,
            // 'Div_Id'=>$DivId,
            // 'Sub_Div_Id'=>$subdivid,
            // 'Sub_Div' => $subdivname,
            // 'Sub_Div_M'=>$subdivnameM,
            'address1' => $address1,
            'address2' => $address2,
            'place' => $place,
            'email' => $email,
            'phone_no' => $phone_no,
        ]);
        // $user->update(); 
        //dd($user);

       // Display success message and redirect to the list of subdivisions
        Alert::success('Success', 'You\'ve Successfully Registered');

        return redirect('listsubdivisions');


            }
            catch(\Exception $e)
            {
                     // Log the exception message
                Log::error('An Error occurr during Update Subdivision data' . $e->getMessage());

                //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');

                return redirect()->back()->with('error' , 'An Error occurr during Subdivision Deputy data' . $e->getMessage());
            }
    }

    //show the sub division
    public function show($Sub_Div_Id)
    {
        try{

      // Retrieve the specific user details using 'Sub_Div_Id'
       $user=Subdivms::where('Sub_Div_Id' ,$Sub_Div_Id)->first();

       // Retrieve the division name where 'div_id' is 147
       $div = DB::table('divisions')
       ->select('div')
       ->where('div_id', 147)
       ->first();

       // Return the view to show subdivision details
       return view('showsubdivision',['user'=>$user , 'div' => $div]);

    }
    catch(\Exception $e)
    {
        // Log the exception message
        Log::error('An Error occurr during Show Subdivison data' . $e->getMessage());

        //$request->session()->flash('error', 'An Error occurr during open edit of deputy data');

        return redirect()->back()->with('error' , 'An Error occurr during Show Subdivison data');
     }
    }



  //delete sub-division
    public function funDeleteSubdivision($Sub_Div_Id)
    {
          // Delete the subdivision record identified by 'Sub_Div_Id'
        Subdivms::where('Sub_Div_Id', $Sub_Div_Id)->delete();

           // Redirect back to the previous page
        return back();
    }






// DB::delete('delete from subdivisions where id=?' ,[$id]);
// return redirect('listsubdivisions')->with('success','Record Deleted');



   }





