<?php

namespace App\Http\Controllers;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert; 
use App\Helpers\PublicDivisionId;
use Illuminate\Support\Facades\Log;


class DivisionController extends Controller
{

    // Method to fetch regions from the database and return as JSON
    public function getRegions() {
        $regions = DB::table('regions')->select('Region')->get();
       // dd($regions);
        return response()->json($regions); // Return the regions as JSON
    }
    
    // Method to fetch circles based on region and return as JSON
    public function getCircles(Request $request)
    {
        $region = $request->input('region');
       // dd($region);
       $regionid=DB::table('regions')->where('Region', '=', $region)->get()->value('Reg_Id');
        $circles = DB::table('circles')
            ->where('Reg_Id', $regionid)
            ->get();
           // dd($circles);
        return response()->json($circles);
    }

    // Method to fetch divisions based on circle and return as JSON
    public function getDivisions(Request $request)
    {
        $circle = $request->input('circle');
        
        $circleid=DB::table('circles')->where('Circle', '=', $circle)->get()->value('Cir_Id');
        $divisions = DB::table('divisions')
            ->where('cir_id', $circleid)
            ->get();
            
        return response()->json($divisions);
    }

    
     // Method to create a new division
    public function funCreate(Request $request)
    {
        // dd($request);
    // ['users'=>$users]
   $region=$request->input('region');

   $circle=$request->input('circle');
   //dd($region);
   
   //dd($request->division_name);
   $regid=DB::table('regions')->where('Region', '=', $region)->get()->value('Reg_Id');

   $cirid=DB::table('circles')->where('Circle', '=', $circle)->get()->value('Cir_Id');
   //dd($cirid);
    $sub_division = DB::table('divisions')->insert([
        'reg_id' => $regid,
        'cir_id' => $cirid,
        'div' => $request->division_name,
        'address1' => $request->address1,
        'address2' => $request->address2,
        'place' => $request->place,
        'email' => $request->email,
        'phoneno' => $request->phoneno,
        'designation' => $request->designation
    ]);

        Alert::success('Congrats', 'You\'ve Succesfully add the data');
        return redirect('division');
    }


   // Method to display a list of divisions
    public function index1() {

        try{

        $divregid=DB::table('divisions')->get()->value('reg_id');
        //dd($divregid); 

        $regid=DB::table('regions')->where('Reg_Id', '=', $divregid)->value('Region');
        //dd($regid);

        $divcircleid=DB::table('divisions')->get()->value('cir_id');
        //dd($divregid); 

        $cirid=DB::table('circles')->where('Cir_Id', '=', $divcircleid)->value('Circle');

         // Fetching division details with associated region and circle names
        $users = DB::table('divisions')
        ->join('regions', 'divisions.reg_id', '=', 'regions.Reg_Id')
        ->join('circles', 'divisions.cir_id', '=', 'circles.Cir_Id')
        ->where('div_id' ,'=', '147')
        ->select('divisions.*', 'regions.Region', 'circles.Circle')
        ->get();
          //dd($users);


        return view('listdivision',['users'=>$users]);


        }catch(\Exception $e)
        {
          Log::error('An error Occurr during open a list of division' .$e->getMessage());

           // Display error message using SweetAlert
         
          return redirect('listworkmasters')->with('error', 'An error occurred while open a list of division.');
        }
     }



      // Method to display the edit form for a division
     public function edit($id)
    {
        try{
        // dd($id);
                $divisionId = PublicDivisionId::DIVISION_ID;

                  // Fetch division details by ID for editing
                $users = DB::table('divisions')->where('div_id', $id)->first();

                    // dd($users);
                    $Div=DB::table('divisions')
                    ->select('div')
                    ->where('div_id',$divisionId)
                    ->get();
                    // dd($Div);


            return view('editdivision',['users'=>$users,'Div'=>$Div]);  

        }catch(\Exception $e)
        {
        Log::error('An error Occurr during open a list of division' .$e->getMessage());

        // Display error message using SweetAlert
        
        return redirect('listdivision')->with('error', 'An error occurred while open a edit  division.');
        }
       
        // ['users'=>$users]
    }

    // Method to update a division
    public function update(Request $request, $id)
    {

        try{

     // Updating division details based on ID
        DB::table('divisions')->where('div_id', $id)->update([
        'div' => $request->input('division_name'),
        'address1'=> $request->input('address1'),
        'address2'=>$request->input('address2'),
        'place'=>$request->input('place'),
       'email'=> $request->input('email'),
        'phoneno'=> $request->input('phoneno'),
       'designation'=> $request->input('designation')
    ]);

        // $users->update();
       
    return redirect('listdivision');

     }catch(\Exception $e)
        {
        Log::error('An error Occurr during open a list of division' .$e->getMessage());

        // Display error message using SweetAlert
        
        return redirect()->back()->with('error', 'An error occurred while Update  division.');
        }
    
    }

    // Method to view details of a division
    public function viewdivisiondata($div_id)
    {
         // Fetch division details by ID for viewing
        $users = DB::table('divisions')
        ->join('regions', 'divisions.reg_id', '=', 'regions.Reg_Id')
        ->join('circles', 'divisions.cir_id', '=', 'circles.Cir_Id')
        ->where('div_id', '=', $div_id)
        ->get();
        //dd($users);
        // return $users;
    return view('view_division',['users'=>$users]);  
        // ['users'=>$users]
}



public function deletedivision($id)
{
    //dd($id);
    try {
        // Attempt to delete the division record
        DB::table('divisions')->where('div_id', $Id)->delete();

        // If you have a Division model, you can use the following instead:
        // Division::where('div_id', $id)->delete();

        // Return back with success message
        return back()->with('success', 'Division deleted successfully.');
    } catch (\Exception $e) {
        // Log the exception message for debugging
        \Log::error('Error deleting division: ' . $e->getMessage());

        // Return back with error message
        return back()->with('error', 'An error occurred while deleting the division.');
    }
}
   
}
