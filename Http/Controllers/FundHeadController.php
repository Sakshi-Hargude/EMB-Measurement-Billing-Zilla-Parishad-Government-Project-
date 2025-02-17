<?php

namespace App\Http\Controllers;

use App\Models\Fundhead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Log;

//fun head master data class
class FundHeadController extends Controller
{

    // insert fund head function
    
    public function insertfundhead(Request $request) 
    {

        try{
        // Insert new fund head record into the database
        $fundhead=Fundhead::insert([
       'F_H_CODE'=> $request->fhcode,
       'Fund_Hd'=> $request->fundhead,
       'Fund_Hd_M'=> $request->fundhead_m
        ]);

          // Display success message using SweetAlert and redirect to listfundhead route
        Alert::success('Congrats', 'You\'ve Succesfully add the data');
        return redirect('listfundhead');

    }catch(\Exception $e)
    {   // Log the error and redirect back with error message
        Log::error('An Error Occurr During for new fund head' . $e->getMessage());

        return redirect()->back()->with('error','An Error Occurr During  for new fund head');
    }

    }


    // list data of fund head function

    // public function listfundhead()
    // {
       
    //     $listfundhead=DB::select('select * from fundhdms');

    //     return view('listfundhead',compact('listfundhead'));
    // }


    //edit and update fund head functions

    // edit fund head page get the data
    public function editfundhead($id)
    {
          // Retrieve fund head record by id for editing
        $editfundhead=DB::table('fundhdms')->where('F_H_id', $id)->first();
    
         // Return editfundhead view with the retrieved fund head data
        return view('editfundhead', compact('editfundhead'));
    }

    // update fund head page function
    public function updatefundhead(Request $request, $id)
{
     //dd($id);
    try {

         // Retrieve the fund head record by id
        $updatefundhead = DB::table('fundhdms')->where('F_H_id', $id)->first();
        // Retrieve the fundhead record
        if ($updatefundhead) {
 
            // Update the fundhead record
            $affected = DB::table('fundhdms')
                ->where('F_H_id', $id)
                ->update([
                    'F_H_CODE' => $request->fhcode,
                    'Fund_Hd' => $request->fundhead,
                    'Fund_Hd_M' => $request->fundhead_m
                ]);

            if ($affected) {
                Alert::success('Congrats', 'You\'ve Successfully Edited the data');
            } else {
                Alert::info('No Changes', 'No data was changed.');
            }
        } else {
            Alert::error('Error', 'Fundhead record not found.');
        }

        return redirect('listfundhead');

    } catch (\Exception $e) {
        // Log the error and display error message using SweetAlert
        Log::error('An error occurred while updating the fundhead: ' . $e->getMessage());

        // Display error message using SweetAlert
        Alert::error('Error', 'An error occurred while updating the fundhead.');

        return redirect()->back();
    }
}




    // view fund head function

    public function viewfundhead($id)
    {
      try{
       // Retrieve fund head record by id for viewing
       $viewfundhead=DB::table('fundhdms')->where('F_H_id' , $id)->first();

       // Return view_fundhead view with the retrieved fund head data
       return view('view_fundhead', compact('viewfundhead'));

    } catch (\Exception $e) {
        // Log the error and display error message using SweetAlert
        Log::error('An error occurred while View the fundhead: ' . $e->getMessage());

        // Display error message using SweetAlert
        Alert::error('Error', 'An error occurred while View the fundhead.');

        return redirect()->back();
    }
    }




  // get all table rows for delete function
    public function getalltablerowsfundhead(Request $request)
    {
         // Retrieve all fund head records where is_delete is 1 (indicating not deleted)
        $listfundhead = DB::table('fundhdms')->select("*")->where('is_delete','=',1)->paginate(8);

           // Return listfundhead view with paginated fund head data
        return view('listfundhead', compact('listfundhead'));

    }
    

    // delete the data row function
    public function deletefundhead($id)
    {
        try{
        // Soft delete the fund head record by updating is_delete to 0
        if($id)
        {
            $query = DB::table('fundhdms')
            ->where('F_H_id', $id)
            ->update(['is_delete' => 0]);

            return back();
        }
    } catch (\Exception $e) {
          // Log the error and display error message using SweetAlert
        Log::error('An error occurred while Deleting the fundhead: ' . $e->getMessage());

        // Display error message using SweetAlert
        Alert::error('Error', 'An error occurred while Deleting the fundhead.');

        return redirect()->back();
    }

    } 
}
