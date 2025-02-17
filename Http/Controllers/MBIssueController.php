<?php

namespace App\Http\Controllers;

use Log;
use Exception;
use App\Models\MBIssueSO;
use App\Models\MBIssueDiv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MBIssueController extends Controller
{
    //
    public function Measurementbookslist(Request $request)
    {
        $user=Auth::user();

        $subdiv=null;

        if($user->usertypes == "EE")
        {
            $EEid = DB::table('eemasters')->where('userid' , $user->id)->first('eeid');

            $MBnoLIst = MBIssueDiv::
            where('EE_Id', $EEid->eeid)
            ->get();
        
        }
        elseif($user->usertypes == "AAO")
        {
            $AAOid = DB::table('daomasters')->where('userid' , $user->id)->first('DAO_id');

            $MBnoLIst = MBIssueDiv::
            where('AAO_Id', $AAOid->DAO_id)
            ->get();
        
        }
        elseif($user->usertypes == "DYE")
        {
            $DYEdata = DB::table('dyemasters')->where('userid' , $user->id)->first();
            
            $subdiv=DB::table('subdivms')->where('Sub_Div_Id' , $DYEdata->subdiv_id)->first();

            $MBnoLIst = MBIssueSO::
            where('Dye_Id' , $DYEdata->dye_id)
            ->get();
        }

        //dd($MBnoLIst , $subdiv);

        return view('MBissuedlist' , compact('MBnoLIst' , 'subdiv'));
    }


    ///Update measurement book

    // Method to fetch MB data
    public function getMBData($id , Request $request)
    {
        
        if($request->subdiv == 0)
        {
            $mbData = MBIssueDiv::findOrFail($id);

           // Fetch related namelist with sorting
                $namelist = DB::table('dyemasters')
                ->join('workmasters', 'dyemasters.dye_id', '=', 'workmasters.DYE_id') // Adjust the join condition as needed
                ->where('workmasters.EE_id', $mbData->EE_Id) // Adjust the condition based on your requirements
                ->where('dyemasters.div_id', $mbData->Div_Id)
                ->distinct() // Ensure distinct records
                ->orderBy('dyemasters.name') // Adjust the sorting column as needed
                ->get(['dyemasters.dye_id as id', 'dyemasters.name']); // Fetch specific columns

        }
        else
        {
            $mbData = MBIssueSO::findOrFail($id);
            $namelist = DB::table('jemasters')->where('subdiv_id' , $mbData->Sub_Div_Id)->get();
             
             // Fetch related namelist with sorting
             $namelist = DB::table('jemasters')
             ->join('workmasters', 'jemasters.jeid', '=', 'workmasters.jeid') // Adjust the join condition as needed
             ->where('workmasters.DYE_id', $mbData->Dye_Id) // Adjust the condition based on your requirements
             ->where('jemasters.subdiv_id', $mbData->Sub_Div_Id)
             ->distinct() // Ensure distinct records
             ->orderBy('jemasters.name') // Adjust the sorting column as needed
             ->get(['jemasters.jeid as id', 'jemasters.name']); // Fetch specific columns

             
        }

        $t_bill_Id = DB::table('bills')->where('work_id' , $mbData->MB_No)->max('t_bill_Id');

             $pgupto= DB::table('bills')->where('t_bill_Id' , $t_bill_Id)->value('pg_upto');

             //dd($pgupto); 
        
        //dd($id , $mbData , $namelist);
        
        // Return data as JSON
        return response()->json(['mbdata' => $mbData , 'namelist' => $namelist , 'pgupto' => $pgupto]);
    }

    // Method to update MB data
    public function updateMBData(Request $request)
    {
        try {
        // Validate request
        $validatedData = $request->validate([
            'id' => 'required|integer', // Update validation as per your model
            'Name' => 'required|string',
            'Issue_Dt' => 'nullable|date',
            'DateOfReturn' => 'nullable|date',
            'Pg_from' => 'required|string',
            'Pg_Upto' => 'required|string',
            'Remark' => 'nullable|string',
            'subdiv' => 'required|integer',
        ]);

        //dd($validatedData);

        if($validatedData['subdiv'] == 1)
        {
            //$validatedData['Name']
            $jedata=DB::table('jemasters')->where('jeid' , $validatedData['Name'])->first();
            
            MBIssueSO::where('id' , $validatedData['id'])->update([
                'JE_Id'=> $jedata->jeid,
                'JE_Nm'=>$jedata->name,
                'Pg_Upto'=>$validatedData['Pg_Upto'],
                'Issue_Dt'=>$validatedData['Issue_Dt'],
                'Return_Dt'=>$validatedData['DateOfReturn'],
                'Preserve_Yr'=>"Permanent",
                'Remark'=>$validatedData['Remark'],

            ]);

            $mbdata=MBIssueSO::where('id' , $validatedData['id'])->first();
        }
        else
        {
             //$validatedData['Name']
             $dyedata=DB::table('dyemasters')->where('dye_id' , $validatedData['Name'])->first();
            
             MBIssueDiv::where('id' , $validatedData['id'])->update([
                 'Dye_Id'=> $dyedata->dye_id,
                 'Dye_Nm'=>$dyedata->name,
                 'Pg_Upto'=>$validatedData['Pg_Upto'],
                 'Issue_Dt'=>$validatedData['Issue_Dt'],
                 'Return_Dt'=>$validatedData['DateOfReturn'],
                 'Preserve_Yr'=>"Permanent",
                 'Remark'=>$validatedData['Remark'],
 
             ]);

             $mbdata=MBIssueDiv::where('id' , $validatedData['id'])->first();
        }

        
         // Return a success response
         return response()->json(['success' => 'Record updated successfully!', 'mbdata' => $mbdata , 'subdiv' => $validatedData['subdiv']]);

        } catch (Exception $e) {
            // Log the exception
            Log::error('Update failed: ' . $e->getMessage());
    
            // Return an error response
            return response()->json(['error' => 'Failed to update record. Please try again.'. $e->getMessage()], 500);
        }
    }



    public function checkBillStatus($workid)
{
    // Get the latest bill ID for the given work ID
    $billid = DB::table('bills')->where('work_id', $workid)->max('t_bill_Id');

    // Fetch the bill details using the latest bill ID
    $bill = DB::table('bills')->where('t_bill_Id', $billid)->first();

    // Check if the bill exists and meets the conditions
    if ($bill && $bill->mb_status == 13 && $bill->final_bill) {
        // Bill is complete
        //dd($bill);
        return response()->json(['billComplete' => true]);
    } else {
        // Bill is not complete or doesn't exist
        return response()->json(['billComplete' => false]);
    }
}

}
