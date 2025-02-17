<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

use Illuminate\Support\Facades\Validator;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use PDF;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Session;

use Dompdf\Dompdf;

// All Bill related functionality included
class BillController extends Controller
{


    //bill data list to see in view page
  public function Billlist( Request $request)
  {
      
      try {
          
    $workid=$request->workid;
    
    
    // dd($request);
      // Fetch workmasters information based on work_id
      $embsection1 = DB::table('workmasters')
      //     ->leftjoin('workmasters', 'embs.Work_Id', '=', 'workmasters.workid')
         ->leftjoin('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
         ->leftjoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
          ->leftjoin('jemasters', 'jemasters.subdiv_id', '=', 'workmasters.Sub_Div_Id')
         ->where('workmasters.Work_Id', '=', $workid)
         ->first();
         
 //dd($embsection1);
     // Fetch embsection1a data
             $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
         $embsection1a = DB::table('fundhdms')->where('F_H_id' , $workdata->F_H_id)->first('Fund_Hd_M');
         


 //$embsection2=DB::table('bills')->where('t_bill_Id' , )
          // Fetch bill data related to the work_id and order by t_bill_id in descending order
         $billdata = DB::table('bills')
         ->where('work_id', $workid)
         ->orderBy('t_bill_id', 'desc') // 'desc' is the column, 'desc' is the sorting order (descending)
         ->get();
         //dd($billdata);

          // Get the maximum t_bill_id for the given work_id
         $tbillid = DB::table('bills')
         ->where('work_id', $workid)
         ->max('t_bill_id'); // 'desc' is the column, 'desc' is the sorting order (descending)
         
         // Get all bills related to the work_id
       $bills= DB::table('bills')->where('work_id', $workid)->get(); // 'desc' is the column, 'desc' is the sorting order (descending)
       
         // Get mb_status for the latest t_bill_id and work_id
       $mbstatus= DB::table('bills')
           ->where('t_bill_id', $tbillid)
           ->where('work_id', $workid)
           // ->select('mb_status')
           ->value('mb_status');
          //dd($mbstatus);
          
      // Fetch latest bill data with specific conditions      
    $latestbillid = DB::table('bills')
           ->where('work_id', $workid)
           ->where('is_current' , 1)
           ->where('mb_status' , 13)
           ->get();

 //dd($latestbillid);

 // Fetch all work bills related to the work_id
    $workbills=DB::table('bills')
    ->where('work_id', $workid)
    ->get();

          //dd($mbstatus);
           // Get mbstatus_so for the latest t_bill_id and work_id
            $mbstatusSo=DB::table('bills')
          ->where('t_bill_id', $tbillid)
          ->where('work_id', $workid)
          ->value('mbstatus_so');
        //   dd($mbstatusSo);
        $mbstatusSo = $mbstatusSo ?? 0;
        // DD($workid);

         // Check if there are bills with null mb_status
        $BillshaveNotExist=DB::table('bills')
        ->where('work_id',$workid)
        ->whereNull('mb_status')        
        ->max('t_bill_Id');
        // dd($BillshaveNotExist);

          // Check if there are bills with specific conditions
        $Billshaveexist = DB::table('bills')
        ->where('work_id', $workid)
        ->where('is_current', 1)
        ->where('mb_status',13)
        ->max('t_bill_Id');
        // dd($BillshaveNotExist,$Billshaveexist);
    
     // Get the final bill value for the latest t_bill_id and work_id
        $finalbill=DB::table('bills')
        ->where('t_bill_id', $tbillid)
        ->where('work_id', $workid)
        ->value('final_bill');
        //dd($finalbill);


  $lastbillid=DB::table('bills')
        ->where('work_id', $workid)
        ->max('t_bill_Id');
        //dd($lastbillid);

         // Return the view with all the fetched data
  return view('Listbills', compact('mbstatus','embsection1', 'embsection1a' , 'billdata' , 'latestbillid' ,
   'workbills','mbstatusSo','tbillid','BillshaveNotExist','Billshaveexist' , 'finalbill' , 'lastbillid'));
   
   
     } catch (\Exception $e) {
            Log::error('Error in Billlist: ' . $e->getMessage());

            // Redirect back with an error message
            return redirect()->back()->with('error', 'An error occurred while processing the request in Billlist.');
        }
  }
  //create new bill function
 
  //New bill create function
public function newbillfunction(Request $request)
{

    
    // Get the last bill for the specified work_id
    $workId = $request->workid;

  //dd($workId);

    // Get the last bill in the database
    $lastBill = DB::table('bills')
         ->where('bills.work_id', '=', $workId)
        ->orderBy('t_bill_id', 'desc')
         ->select('bills.*','bills.t_bill_id','bills.t_bill_no')
        ->first();
//dd($lastBill);

 // Format the work order date and stipulated completion date
        $formattedDate=DB::table('workmasters')->where('work_id' , $workId)->value('Wo_Dt');
        $workorderdt = $formattedDate;
        //$workorderdt = date('d-m-Y', strtotime($formattedDate));

        $stipulatedDate=DB::table('workmasters')->where('work_id' , $workId)->value('Stip_Comp_Dt');
// dd($stipulatedDate);
$stipulatedDate = date('d-m-Y', strtotime($stipulatedDate));
// dd($stipulatedDate);

    if ($lastBill) {
        // Generate new bill ID
        $lastFourDigits = substr($lastBill->t_bill_id, -4);
        $newLastFourDigits = str_pad((intval($lastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
        $newBillId = substr_replace($lastBill->t_bill_id, $newLastFourDigits, -4);

        // Increment bill number
        $newBillNo = $lastBill->t_bill_No + 1;

        //$firstbillgstrate=DB::table('')

        $lastBillDate = $lastBill->meas_dt_upto; // Assuming $lastBill->Bill_Dt is in a valid date format
         $nextDayDate = date('d-m-Y', strtotime($lastBillDate . ' +1 day'));
       
        // Get the bill amount from the previous bill record
        //$workid = $workId;
      
       $billAmt = $lastBill->bill_amt ;
       $recamt = $lastBill->rec_amt;
       $netamt = 0 ;
       $finalbill = $lastBill->final_bill ;
       $cvno = $lastBill->cv_no ;
       $cvdate = $lastBill->cv_dt ;
       $billdt = $nextDayDate ;
       $billtype = $lastBill->bill_type ;
       $measdtfr = date('Y-m-d', strtotime($nextDayDate));
       $measdtTo=$stipulatedDate;
       $part_a_amt = 0;		
       $part_b_amt = 0;
       $gst_base = 0;	
       $gst_amt = 0;	
       $tot_ded = 0;
       $gross_amt	= 0;	
       $a_b_effect = 0;
       $bill_amt_gt = 0;
       $bill_amt_ro = 0;
       $p_bill_amt=$lastBill->bill_amt;
       $gst_rt = $lastBill->gst_rt;
       $p_part_a_amt = $lastBill->part_a_amt;		
       $p_part_b_amt = $lastBill->part_b_amt;		
       $p_gross_amt = $lastBill->gross_amt;	 	
       $p_a_b_effect = $lastBill->a_b_effect;		
       $p_tot_ded	= $lastBill->tot_ded;
       $p_tot_recovery	= $lastBill->tot_recovery;
       $p_chq_amt	= $lastBill->chq_amt;				
       $p_gst_base = $lastBill->gst_base;		
       $p_net_amt = $lastBill->net_amt;
       $p_gst_rt = $lastBill->gst_rt;
       $p_gst_amt = $lastBill->gst_amt;
       $p_bill_amt_gt = $lastBill->bill_amt_gt;
       $p_bill_amt_ro = $lastBill->bill_amt_ro;
       $p_bill_dt = $lastBill->Bill_Dt;
       $pg_from	=$lastBill->pg_upto;	
       $pg_upto	=0;				
       //dd($p_net_bill_amt);
   } else {
       // If no previous bill exists, start from a default value
       $newBillId = $workId.'0001';
       $newBillNo = 1;

       $billAmt = 0;
       $recamt = 0;
       $netamt = 0;
       $finalbill = 0;
       $cvno = '';
       $cvdate = null;
       $billdt = null;
       $billtype='Normal';
       $measdtfr=$workorderdt;
              $measdtTo=$stipulatedDate;
       $part_a_amt = 0;		
       $part_b_amt = 0;
       $gst_base = 0;
       $gst_amt = 0;		
       $tot_ded = 0;
       $gross_amt	= 0;	
       $a_b_effect = 0;
       $gst_rt = 18;
       $bill_amt_gt = 0;
       $bill_amt_ro = 0;
       $p_part_a_amt = 0;		
       $p_part_b_amt = 0;		
       $p_gross_amt = 0;	 	
       $p_a_b_effect = 0;		
       $p_tot_ded	= 0;
       $p_tot_recovery	= 0;
       $p_chq_amt	= 0;							
       $p_gst_base = 0;		
       $p_net_amt = 0;
       $p_gst_rt = 0;
       $p_bill_amt_gt = 0;
       $p_bill_amt_ro = 0;	
       $p_bill_amt= 0;	
       $p_gst_amt= 0;
       $p_bill_dt = null;
       $pg_from	=0;	
       $pg_upto	=0;			
       //dd($billtype);$   
    }

   // Create a new bill entry
   $newBillData = [
       't_bill_id' => $newBillId,
       't_bill_No' => $newBillNo,
       // Set other bill properties as needed
       'work_id' =>  $workId,
       'bill_amt' => $billAmt,
       'rec_amt' =>  $recamt,
       'net_amt' =>  $netamt,
       'Bill_Dt' =>  $billdt,
       'final_bill' => $finalbill,
       'cv_no' =>  $cvno,
       'cv_dt' =>   $cvdate,
       'bill_type' =>   $billtype,
       'measdtfrom' => $measdtfr,
        'measdtToo'=>$measdtTo,
       'part_a_amt' => $part_a_amt,		
       'part_b_amt' => $part_b_amt,
       'gst_base' => $gst_base,	
       'gst_amt' => $gst_amt,	
       'tot_ded' => $tot_ded,
       'gross_amt'	=> $gross_amt,	
       'a_b_effect' => $a_b_effect,
       'gst_rt' => $gst_rt,
       'bill_amt_gt' =>$bill_amt_gt,
       'bill_amt_ro' =>$bill_amt_ro,
       'p_bill_amt' =>  $p_bill_amt,	
       'p_part_a_amt' => $p_part_a_amt,	
       'p_part_b_amt' => $p_part_b_amt,	
       'p_gross_amt' => $p_gross_amt,	 	
       'p_a_b_effect' => $p_a_b_effect,		
       'p_tot_ded'	=> $p_tot_ded,
       'p_tot_recovery' => $p_tot_recovery,
       'p_chq_amt' => $p_chq_amt,	
       'p_gst_base' => $p_gst_base,		
       'p_net_amt' => $p_net_amt,
       'p_gst_rt' => $p_gst_rt,
       'p_bill_amt_gt' => $p_bill_amt_gt,
       'p_bill_amt_ro'	=> $p_bill_amt_ro,
       'p_gst_amt'=> $p_gst_amt,
       'p_bill_dt' => $p_bill_dt,
       'pg_from'=> $pg_from,	
       'pg_upto'=> $pg_upto,	
   ];
    //dd($measdtfr);

//
$firstid=$workId.'0001';
//dd($newBillData);

// Get the last bill in the database
//last bill_id  of all bill_items records
$lasttbillid = DB::table('bills')
    ->orderBy('t_bill_id', 'desc')
    ->select('t_bill_id')
    ->where('work_id', '=', $workId)
    ->value('t_bill_id'); // Use the value() method to retrieve the t_bill_id directly
//dd($lasttbillid);
// Previous bill items
$previousbillitems = DB::table('bil_item')
    ->where('t_bill_id', '=', $lasttbillid)
    ->select('bil_item.*')
    ->get();
//dd($previousbillitems);


//previous b_item_ids
$previousbitemids = DB::table('bil_item')
    ->where('t_bill_id', '=', $lasttbillid)
    ->pluck('b_item_id');

    // Now, modify each b_item_id by replacing the first 16 characters with the new t_bill_id
$modifiedBItemIds = $previousbitemids->map(function ($bItemId) use ($newBillId) {
    return $newBillId . substr($bItemId, 16);
});


//dd($modifiedBItemIds);
// $modifiedBItemIds now contains the modified b_item_ids with the first 16 characters unchanged


//dd($previousbitemids);
// Get the last b_item_id
$lastbitemid = DB::table('bil_item')
        ->where('t_bill_id', '=', $lasttbillid) 
        ->orderBy('b_item_id', 'desc')
        ->select('b_item_id')
        ->first();
//dd($lastbitemid);
   
// Initialize an array to store all the new bill items
$newbilitems = [];

// if ($lastbitemid) {
//     // Calculate the initial b_item_id for the first row

//     $lastFourDigits = $newBillId + 4;
//     $newLastFourDigits = str_pad((intval($lastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
//     $newbitemid = substr_replace($newBillId, $newLastFourDigits, 4);
//     //dd($newbitemid);
// } else {
//     // If lastb_item_id is not available, generate a new bitem_id as $newbillid.0001
//     $newbitemid = $newBillId . '0001';
    
// }

//ALL previous bill items
foreach ($previousbillitems as $index =>$previousbillitem) {

    $previousbillqty=$previousbillitem->exec_qty;
    // Get the modified b_item_id for the current row
    $modifiedBItemId = $modifiedBItemIds[$index];
//dd($newBillId);
    if( $newBillId === $workId.'0001')
    {
        $bitemamt=0;
        $previousbillqty=0;
        $previousbitemamt=0;
        //dd($bitemamt);
    }
    else
    {

        $bitemamt= $previousbillitem->b_item_amt;
        
        //dd($previousbillqty);
        $previousbitemamt=$previousbillitem->b_item_amt;
       // dd($previousbitemamt);
    }
    //dd($bitemamt);
    // Create a new item with the updated b_item_id
    $newBillItem = [
        't_bill_id' => $newBillId,
        'b_item_id' => $modifiedBItemId,
        't_item_id' => $previousbillitem->t_item_id,
        't_item_no' => $previousbillitem->t_item_no,
        'sub_no' => $previousbillitem->sub_no,
        'item_id' => $previousbillitem->item_id,
        'sch_item' => $previousbillitem->sch_item,
        'item_desc' => $previousbillitem->item_desc,
        'exec_qty' => $previousbillitem->exec_qty,
        'item_unit' => $previousbillitem->item_unit,
        'tnd_rt' => $previousbillitem->tnd_rt,
        'b_item_amt' => $bitemamt,
        'tnd_qty' => $previousbillitem->tnd_qty,
        'je_check' => $previousbillitem->je_check,
        'dyE_check' => $previousbillitem->dyE_check,
        'ee_check' => $previousbillitem->ee_check,
        'je_chk_dt' => $previousbillitem->je_chk_dt,
        'dye_chk_dt' => $previousbillitem->dye_chk_dt,
        'ee_chk_dt' => $previousbillitem->ee_chk_dt,
        'passed_amt' => $previousbillitem->passed_amt,
        'passed_qty' => $previousbillitem->passed_qty,
        'withheld_amt' => $previousbillitem->withheld_amt,
        'part_rt_id' => $previousbillitem->part_rt_id,
        'agency_chk' => $previousbillitem->agency_chk,
        'drg' => $previousbillitem->drg,
        'photo1' => $previousbillitem->photo1,
        'photo2' => $previousbillitem->photo2,
        'photo3' => $previousbillitem->photo3,
        'document' => $previousbillitem->document,
        'is_previous' => $previousbillitem->is_previous,
        'prv_bill_qty' => $previousbillqty,
        'cur_qty' => 0,
        'exs_nm' => $previousbillitem->exs_nm,
        'previous_amt' => $previousbitemamt,
        'bill_rt' => $previousbillitem->bill_rt,
    ];

    // Add the new item to the array of new bill items
    $newbilitems[] = $newBillItem;

    // Increment the b_item_id for the next row
    // $newLastFourDigits = str_pad((intval($newLastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
    // $newbitemid = substr_replace($newbitemid, $newLastFourDigits, -4);
}
// Now $allPreviousBillItems contains all the previous bill items data.

//dd($newbilitems);



//dd($previousbillitem);
     // Store the other values in session variables
       // Store the workId in a session variable
     session()->put('workId', $workId);
     session()->put('lastBill', $lastBill);
     session()->put('newBillId', $newBillId);
     session()->put('newBillNo', $newBillNo);
     session()->put('newBillData', $newBillData);
     session()->put('newbilitems', $newbilitems);

    //  section 3 data add  means bill item adding new
//    get the last bill item

// Get the current server date using Carbon
           $serverDate = Carbon::now('Asia/Kolkata');


        // Calculate the date 180 days ago
        $date180DaysAgo = $serverDate->subDays(180)->format('d-m-Y');


            $convert=new CommonHelper();
                $bill_amt = $convert->formatIndianRupees($lastBill->bill_amt ?? 0);
                $rec_amt = $convert->formatIndianRupees($lastBill->rec_amt ?? 0);
               $net_amt = $convert->formatIndianRupees($lastBill->net_amt ?? 0);

    //return all data in bill view page
return response()->json([
        'newBillData' => $newBillData,
        'newBillId' => $newBillId,
        'newBillNo' => $newBillNo,
        'lastbill' => $lastBill, 
        'newbilitems'=>$newbilitems,
        'firstid' => $firstid,
        'date180DaysAgo' => $date180DaysAgo,
        'workorderdt' => $workorderdt,
        'bill_amt' => $bill_amt,
        'rec_amt' => $rec_amt,
        'net_amt' => $net_amt
    ]);
    
    
}











  //update final bill
  public function updateFinalBill(Request $request)
  {
    // $request->validate([
    //     'final_bill' => 'required|boolean', // Assuming final_bill is a boolean field
    //     'work_completed' => 'boolean',
    //     'work_completed_date' => 'date'
    // ]);

    $Workid=$request->workid;
   // dd($Workid);
  // dd($workid, $request->final_bill , $request->work_completed , $request->work_completed_date);

  //check work completed and has date is there update work data
    if ($request->has('work_completed') && $request->has('work_completed_date')) {
        // Update the database
         DB::table('workmasters')->where('Work_Id' , $Workid)->update([
            'work_comp' => $request->work_completed,
            'actual_complete_date' => $request->work_completed_date
        ]);
      
    }
    else{
          //if not  is there work_comp and date
        $workmasterdata = DB::table('workmasters')->where('Work_Id' , $Workid)->update([
            'work_comp' => 0,
            'actual_complete_date' => null,
        ]);

    }

      // Retrieve the final bill value from the AJAX request
      $finalBillValue = $request->input('final_bill');
  
      // Store the final bill value in the session
      session()->put('finalBillValue', $finalBillValue);
  
      // Return a success response
      return response()->json(['success' => true, 'message' => 'Final bill value stored successfully']);
  }
    


  

  //submit button function for new bill no
  public function submitForm(Request $request)
  {
      
      
      
        // Define validation rules
    $rules = [
        'Bill_Dt' => 'required',
        't_bill_No' => 'required',
        //'measdtfr' => 'required',
        'measdtupto' => 'required',
    ];


    // Validate the request data
    $validator = Validator::make($request->all(), $rules);

    try {
        // Check if validation fails
        if ($validator->fails()) {
            throw new \Exception('Validation failed: ' . $validator->errors()->first());
        }


    $formData = $request->input('formData');
    //dd($formData);
      // Retrieve the stored values from session
      $lastBill = session()->get('lastBill');
      $newBillId = session()->get('newBillId');
      $newBillNo = session()->get('newBillNo');
      $newBillData = session()->get('newBillData');
      $newbilitems =session()->get('newbilitems');
      //dd($newbilitems);
      // Retrieve the other form data
      $workId = $newBillData['work_id'];
      $tBillId = $newBillData['t_bill_id'];
    //   //$tBillNo = $newBillData['t_bill_No'];
    //   $billDt = $newBillData['Bill_Dt'];
    //   $billAmt = $newBillData['bill_amt'];
    //   $recAmt = $newBillData['bill_amt'];
    //   $netAmt = $newBillData['net_amt'];
      
      $tBillNo = $request->input('t_bill_No');
      //dd($tBillNo);
      $billDt = $request->input('Bill_Dt');
      $billAmt = $request->input('bill_amt');
      $recAmt = $request->input('rec_amt');
      $netAmt = $request->input('net_amt');
      
      // Remove commas from the inputs to ensure clean numeric values
      $billAmt = str_replace(',', '', $billAmt);
      $recAmt = str_replace(',', '', $recAmt);
      $netAmt = str_replace(',', '', $netAmt);
      
      $cvNo = $request->input('cv_no');

      $cvDt = $request->input('cv_dt');

//$formattedcvDate = date('d-m-Y', strtotime($formattedcvDate));
     


      $billType = $request->input('bill_type');

      $measdtfr = $request->input('measdtfr');
     // $formattedmeasDatefrom = date('d-m-Y', strtotime($formattedmeasDatefrom));
      
      $gstrate = $request->input('gstrate');
      //dd($gstrate);
      $measdtupto = $request->input('measdtupto');
     // $formattedmeasDateupto = date('d-m-Y', strtotime($formattedmeasDateupto));
//      dd($measdtupto);
$billDt = $request->input('Bill_Dt');

//$formattedbilDate = date('d-m-Y', strtotime($formattedbilDate));
  //dd($billDt);
$workorderdt=DB::table('workmasters')->where('work_id' , $workId)->value('Wo_Dt');

$stipulateddt=DB::table('workmasters')->where('work_id' , $workId)->value('Stip_Comp_Dt');

$previousBill = DB::table('bills')
    ->where('work_id', $workId)
    ->where('t_bill_id', '<', $tBillId) // Find bills with 't_bill_id' less than the current one
    ->orderByDesc('t_bill_id') // Order them in descending order of 't_bill_id'
    ->first(); // Retrieve the first (latest) previous bill


    $previousbilldt = $previousBill->Bill_Dt ?? null;
// Convert $previousbilldt to a timestamp, add one day (86400 seconds), and then format it as a date
$previousbillDate = date('Y-m-d', strtotime($previousbilldt . ' +1 day'));

$lasttbillid = DB::table('bills')
    ->where('work_id', '=', $workId)
    ->max('t_bill_id'); // Use the value() method to retrieve the t_bill_id directly

   // dd($lasttbillid);


$isFirstBill = $workId.'0001';// Determine if it's the first bill (you need to define this condition)
//dd($isFirstBill);
if ($isFirstBill === $tBillId) {
    // Check if $billDt is within the date range ($workorderdt to $stipulateddt) or ($workorderdt to $reviseddt)
    if (empty($reviseddt) && ($billDt >= $workorderdt && $billDt <= $stipulateddt) || (!empty($reviseddt) && $billDt >= $workorderdt && $billDt <= $reviseddt)) {
        // $billDt is within the date range for the first bill
        // Your code logic for the first bill goes here
        //dd($workorderdt , $stipulateddt);
    } else {
        // $billDt is not within the date range for the first bill
        // Handle the case where $billDt is outside the expected range
         // Set a flag to indicate that the date is invalid
         echo "<script>
         sweetAlertConfig = {
             icon: 'error',
             title: 'Error',
             text: 'Bill date is not within the expected range for the first bill.'
         };
     </script>";
     return; // Stop further processing and prevent insertion
    }
} else {
    // Handle the case where it's not the first bill
    //dd('ok');
    if (empty($reviseddt) && ($billDt >= $previousbillDate && $billDt <= $stipulateddt) || (!empty($reviseddt) && $billDt >= $previousbillDate && $billDt <= $reviseddt)) {
        // $billDt is within the date range for the first bill
        // Your code logic for the first bill goes here
    } else {
        // $billDt is not within the date range for the first bill
        // Handle the case where $billDt is outside the expected range
        echo "<script>
        sweetAlertConfig = {
            icon: 'error',
            title: 'Error',
            text: 'Bill date is not within the expected range for subsequent bills.'
            
        };
    </script>";
    return; // Stop further processing and prevent insertion
    }
}
  //dd($billType);
     // Retrieve the final bill value from the session
    $finalBillValue = $request->input('final_bill');
 //dd($finalBillValue);
    // Set the default value for 'final_bill' if it is null
    $finalBillValue = $finalBillValue ?? 0;


   
// dd($billAmt);
      //insert the bill data 
      $insertedId = DB::table('bills')->insertGetId([
        't_bill_Id' => $tBillId,
        't_bill_No' => $tBillNo,
        'Bill_Dt' => $billDt,
        'bill_amt' => $billAmt,
        'rec_amt' => $recAmt,
        'net_amt' => $netAmt,
        'cv_no' => $cvNo,
        'cv_dt' => $cvDt,
        'bill_type' => $billType,
        'final_bill' => $finalBillValue,
        'work_id' => $workId,
        'meas_dt_from' => $newBillData['measdtfrom'],
        'meas_dt_upto' => $measdtupto,
        'gst_rt' => $gstrate,
        'mb_status' => 1,

        'part_a_amt' => $newBillData['part_a_amt'],		
		'part_b_amt' =>$newBillData['part_b_amt'],	
		'gst_base' => $newBillData['gst_base'],	
		'gst_amt' => $newBillData['gst_amt'],		
		'tot_ded' => $newBillData['tot_ded'],	
		'gross_amt'	=> $newBillData['gross_amt'],	
	    'a_b_effect' => $newBillData['a_b_effect'],
        'bill_amt_gt' => $newBillData['bill_amt_gt'],
        'bill_amt_ro' => $newBillData['bill_amt_ro'], 
        'p_bill_amt' => $newBillData['p_bill_amt'],		
		'p_part_a_amt' => $newBillData['p_part_a_amt'],	
		'p_part_b_amt' => $newBillData['p_part_b_amt'],		
		'p_gross_amt' => $newBillData['p_gross_amt'],	
		'p_a_b_effect' => $newBillData['p_a_b_effect'],	
		'p_tot_ded'	=> $newBillData['p_tot_ded'],
        'p_tot_recovery' => $newBillData['p_tot_recovery'],
        'p_chq_amt' => $newBillData['p_chq_amt'],						
		'p_gst_base' => $newBillData['p_gst_base'],	
		'p_net_amt' => $newBillData['p_net_amt'],	
        'p_gst_rt' => $newBillData['p_gst_rt'],
        'p_bill_amt_gt' => $newBillData['p_bill_amt_gt'],	
        'p_bill_amt_ro' => $newBillData['p_bill_amt_ro'],
        'p_gst_amt' => $newBillData['p_gst_amt'],
        'previousbilldt' =>$newBillData['p_bill_dt'],
        'pg_from'	=> 	$newBillData['pg_from'],
        'pg_upto'	=> 	$newBillData['pg_upto'],		
    ]);
    // Retrieve the inserted row from the database
    $insertedRow = DB::table('bills')->where('t_bill_Id', $tBillId)->first();
   

     // Fetch bill data for view
     $embsection2 = DB::table('bills')
     ->leftjoin('embs', 'embs.t_bill_id', '=', 'bills.t_bill_id')
     ->leftjoin('workmasters', 'bills.work_id', '=', 'workmasters.Work_Id')
     ->where('bills.work_id', '=', $workId)
     ->select('bills.*')
     ->orderBy('bills.t_bill_No', 'desc')
     ->first();
 // dd($embsection2);
  $newbilldtformat=$embsection2->Bill_Dt;
  $newbilldt = date('d-m-Y', strtotime($newbilldtformat));
  $newmeasdtfrformat=$embsection2->meas_dt_from;
  $newmeasdtfr = date('d-m-Y', strtotime($newmeasdtfrformat));
  $newmessuptoformat=$embsection2->meas_dt_upto;
  $newmessupto = date('d-m-Y', strtotime($newmessuptoformat));
  //dd($newbilldt);

     // Fetch billNos based on work_id
     $billNos = DB::table('bills')
     ->where('work_id', $workId)
     ->orderBy('t_bill_No', 'desc')
     ->pluck('t_bill_No', 't_bill_id');

//dd($billNos);
 ////////////////////////////previous data bil items add new bill add bil items



// Retrieve the other form data
// $t_item_id = $previousbillitems['t_item_id'];
// dd($t_item_id);
// $tBillId = $newBillData['t_bill_id'];
// $tBillNo = $newBillData['t_bill_No'];
// $billDt = $newBillData['Bill_Dt'];
// $billAmt = $newBillData['bill_amt'];
// $recAmt = $newBillData['rec_amt'];
// $netAmt = $newBillData['net_amt'];


// Initialize newbitemid variable

// Loop through the previous bill items and insert rows into the bil_item table
$t_bill_id = isset($newbilitems[0]['t_bill_id']) ? $newbilitems[0]['t_bill_id'] : null;
//dd($t_bill_id);

// Assuming you have a table called 'previous_bills' with 't_bill_id' column
$previous_tBillIds = DB::table('bills')->where('work_id', $workId)->pluck('t_bill_id')->toArray();
//dd($previous_tBillIds);

// Set 'is_previous' to zero for all 'bil_item' rows related to the previous t_bill_ids
DB::table('bil_item')
    ->whereIn('t_bill_id', $previous_tBillIds) // Replace $previous_tBillIds with an array of the previous t_bill_ids
    ->update(['is_previous' => 0]);

//dd($newbilitems);
$previoustitemids = DB::table('bil_item')
->where('t_bill_id', '=', $lasttbillid)
->pluck('t_item_id');

//if first bill and is final bill is there

if ($embsection2->t_bill_No == '1' && $finalBillValue == 1)
{  //dd('ok');


$tenderitemdatas=DB::table('tnditems')->where('work_Id' , $workId)->get();


foreach ($tenderitemdatas as $tenderitemdata) {

    $lastBitemid = DB::table('bil_item')
    ->where('t_bill_id', '=', $tBillId) 
    ->orderByDesc('b_item_id')
    ->first('b_item_id');
    
    
    //dd($lastBitemid);

if ($lastBitemid) {
    // Calculate the initial b_item_id for the first row
    $lastFourDigits = substr($lastBitemid->b_item_id, -4);
    
    $newLastFourDigits = str_pad((intval($lastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
    //dd($lastFourDigits);
    $newbitemid = substr_replace($lastBitemid->b_item_id, $newLastFourDigits, -4);
   // dd($Newbitemid);
} else {
    // If lastb_item_id is not available, generate a new bitem_id as $newbillid.0001
    $newbitemid = $tBillId . '0001';
    //dd($newbitemid);
}
    // Insert the row into the bil_item table
    DB::table('bil_item')->insert([
        'b_item_id' => $newbitemid,
        't_bill_id' => $tBillId,
        't_item_id' => $tenderitemdata->t_item_id,
        't_item_no' => $tenderitemdata->t_item_no,
        'sub_no' => $tenderitemdata->sub_no,
        'item_id' => $tenderitemdata->item_id,
        'sch_item' => $tenderitemdata->sch_item,
        'item_desc' => $tenderitemdata->item_desc,
        'exec_qty' => 0.000,
        'item_unit' => $tenderitemdata->item_unit,
        'tnd_rt' => $tenderitemdata->tnd_rt,
        'b_item_amt' => 0.00,
        'tnd_qty' => $tenderitemdata->tnd_qty,
        'passed_qty' => 0.000,
        'passed_amt' => 0.000,
        'withheld_amt' => 0.000,
        'is_previous' => 1,
        'exs_nm' => $tenderitemdata->exs_nm,
        'bill_rt'=> $tenderitemdata->tnd_rt,       
        // Add other columns and their values from $newbilitem as needed
    ]);
}

//dd($tenderitemdata);

} 
elseif($embsection2->t_bill_No != '1' && $finalBillValue == 1) //if Any bill and is final bill is there
{  // dd('ok');


   //$tnditemsdatas=DB::table('tnditems')->where('work_Id' , $workId)->get();


$tenderitemdatas = DB::table('tnditems')
   ->where('work_Id', $workId)
   ->whereNotIn('t_item_id', $previoustitemids)
   ->get();
//dd($tnditemsdatas);

//dd($newbilitems);
foreach ($newbilitems as $newbilitem) {
    // Insert the row into the bil_item table
    DB::table('bil_item')->insert([
        'b_item_id' => $newbilitem['b_item_id'],
        't_bill_id' => $newbilitem['t_bill_id'],
        't_item_id' => $newbilitem['t_item_id'],
        't_item_no' => $newbilitem['t_item_no'],
        'sub_no' => $newbilitem['sub_no'],
        'item_id' => $newbilitem['item_id'],
        'sch_item' => $newbilitem['sch_item'],
        'item_desc' => $newbilitem['item_desc'],
        'exec_qty' => $newbilitem['exec_qty'],
        'item_unit' => $newbilitem['item_unit'],
        'tnd_rt' => $newbilitem['tnd_rt'],
        'b_item_amt' => $newbilitem['b_item_amt'],
        'tnd_qty' => $newbilitem['tnd_qty'],
        'je_check' => $newbilitem['je_check'],
        'dyE_check' => $newbilitem['dyE_check'],
        'ee_check' => $newbilitem['ee_check'],
        'je_chk_dt' => $newbilitem['je_chk_dt'],
        'dye_chk_dt' => $newbilitem['dye_chk_dt'],
        'ee_chk_dt' => $newbilitem['ee_chk_dt'],
        'passed_qty' => $newbilitem['passed_qty'],
        'passed_amt' => $newbilitem['passed_amt'],
        'withheld_amt' => $newbilitem['withheld_amt'],
        'part_rt_id' => $newbilitem['part_rt_id'],
        'agency_chk' => $newbilitem['agency_chk'],
        'drg' => $newbilitem['drg'],
        'photo1' => $newbilitem['photo1'],
        'photo2' => $newbilitem['photo2'],
        'photo3' => $newbilitem['photo3'],
        'document' => $newbilitem['document'],
        'is_previous' => 0,
        'prv_bill_qty' => $newbilitem['prv_bill_qty'],
        'cur_qty' => $newbilitem['cur_qty'],
        'exs_nm' => $newbilitem['exs_nm'],
        'previous_amt' => $newbilitem['previous_amt'],
        'bill_rt' => $newbilitem['bill_rt'],
        
        // Add other columns and their values from $newbilitem as needed
    ]);
}


foreach ($tenderitemdatas as $tenderitemdata) {


    $lastBitemid = DB::table('bil_item')
    ->where('t_bill_id', '=', $tBillId) 
    ->orderByDesc('b_item_id')
    ->first('b_item_id');
    
    
    //dd($lastBitemid);

if ($lastBitemid) {
    // Calculate the initial b_item_id for the first row
    $lastFourDigits = substr($lastBitemid->b_item_id, -4);
    
    $newLastFourDigits = str_pad((intval($lastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
    //dd($lastFourDigits);
    $Newbitemid = substr_replace($lastBitemid->b_item_id, $newLastFourDigits, -4);
   // dd($Newbitemid);
} else {
    // If lastb_item_id is not available, generate a new bitem_id as $newbillid.0001
    $Newbitemid = $tBillId . '0001';
    //dd($newbitemid);
}

//dd($newbitemid);
DB::table('bil_item')->insert([
    'b_item_id' => $Newbitemid,
    't_bill_id' => $tBillId,
    't_item_id' => $tenderitemdata->t_item_id,
    't_item_no' => $tenderitemdata->t_item_no,
    'sub_no' => $tenderitemdata->sub_no,
    'item_id' => $tenderitemdata->item_id,
    'sch_item' => $tenderitemdata->sch_item,
    'item_desc' => $tenderitemdata->item_desc,
    'exec_qty' => 0.000,
    'item_unit' => $tenderitemdata->item_unit,
    'tnd_rt' => $tenderitemdata->tnd_rt,
    'b_item_amt' => 0.00,
    'tnd_qty' => $tenderitemdata->tnd_qty,
    'passed_qty' => 0.000,
    'passed_amt' => 0.000,
    'withheld_amt' => 0.000,
    'is_previous' => 1,
    'exs_nm' => $tenderitemdata->exs_nm,
    'bill_rt'=> $tenderitemdata->tnd_rt,       
    // Add other columns and their values from $newbilitem as needed
]);


}



}
else  /// final bill is not there
{
   // dd('ok');


    foreach ($newbilitems as $newbilitem) {
        // Insert the row into the bil_item table
        DB::table('bil_item')->insert([
            'b_item_id' => $newbilitem['b_item_id'],
            't_bill_id' => $newbilitem['t_bill_id'],
            't_item_id' => $newbilitem['t_item_id'],
            't_item_no' => $newbilitem['t_item_no'],
            'sub_no' => $newbilitem['sub_no'],
            'item_id' => $newbilitem['item_id'],
            'sch_item' => $newbilitem['sch_item'],
            'item_desc' => $newbilitem['item_desc'],
            'exec_qty' => $newbilitem['exec_qty'],
            'item_unit' => $newbilitem['item_unit'],
            'tnd_rt' => $newbilitem['tnd_rt'],
            'b_item_amt' => $newbilitem['b_item_amt'],
            'tnd_qty' => $newbilitem['tnd_qty'],
            'je_check' => $newbilitem['je_check'],
            'dyE_check' => $newbilitem['dyE_check'],
            'ee_check' => $newbilitem['ee_check'],
            'je_chk_dt' => $newbilitem['je_chk_dt'],
            'dye_chk_dt' => $newbilitem['dye_chk_dt'],
            'ee_chk_dt' => $newbilitem['ee_chk_dt'],
            'passed_qty' => $newbilitem['passed_qty'],
            'passed_amt' => $newbilitem['passed_amt'],
            'withheld_amt' => $newbilitem['withheld_amt'],
            'part_rt_id' => $newbilitem['part_rt_id'],
            'agency_chk' => $newbilitem['agency_chk'],
            'drg' => $newbilitem['drg'],
            'photo1' => $newbilitem['photo1'],
            'photo2' => $newbilitem['photo2'],
            'photo3' => $newbilitem['photo3'],
            'document' => $newbilitem['document'],
            'is_previous' => 0,
            'prv_bill_qty' => $newbilitem['prv_bill_qty'],
            'cur_qty' => $newbilitem['cur_qty'],
            'exs_nm' => $newbilitem['exs_nm'],
            'previous_amt' => $newbilitem['previous_amt'],
            'bill_rt' => $newbilitem['bill_rt'],
            
            // Add other columns and their values from $newbilitem as needed
        ]);
    }
    

}

DB::table('bills')
    ->where('work_id', '=', $workId)
    ->where('is_current', 1)
    ->where('t_bill_id', '<>', $tBillId) // Exclude the current bill from the update
    ->update(['is_current' => 0]);

$iscurrentbill=DB::table('bills')
  ->where('work_id', '=', $workId)
  ->where('bills.t_bill_id', '=', $tBillId)
  ->select('t_bill_id')
  ->first();
  //dd($iscurrentbill);
// Set the is_current flag for the particular $tBillId
DB::table('bills')
     ->where('work_id', '=', $workId)
    ->where('t_bill_id', $tBillId)
    ->update(['is_current' => 1]);

  

$billItemsData = DB::table('bil_item')
->where('t_bill_id', $tBillId)
    ->select('t_bill_id')
    ->get();

//dd($billItemsData);
 // Retrieve the inserted data from the database
 $inserteddata = DB::table('bil_item')->where('t_bill_id', $t_bill_id)->get();
//dd($inserteddata);

// Fetch workmasters information based on work_id
$embsection1 = DB::table('workmasters')
//     ->leftjoin('workmasters', 'embs.Work_Id', '=', 'workmasters.workid')
   ->leftjoin('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
   ->leftjoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
    ->leftjoin('jemasters', 'jemasters.subdiv_id', '=', 'workmasters.Sub_Div_Id')
   ->where('workmasters.Work_Id', '=', $workId)
   ->first();
//dd($embsection1);
// Fetch embsection1a data
$embsection1a = DB::table('fundhdms')
   ->select('fundhdms.Fund_Hd_M')
   ->leftJoin('workmasters', function ($join) {
       $join->on(DB::raw('LEFT(workmasters.F_H_Code, 4)'), '=', DB::raw('LEFT(fundhdms.F_H_Code, 4)'));
   })
   ->where('workmasters.Work_Id', $workId)
   ->first();



$embsection3 = DB::table('bil_item')
->leftjoin('bills', 'bills.t_bill_Id', '=', 'bil_item.t_bill_Id')
->leftjoin('tnditems', 'tnditems.t_item_id', '=', 'bil_item.t_item_id')
->leftjoin('embs', 'embs.b_item_id', '=', 'bil_item.b_item_id')
->where('bil_item.t_bill_Id', '=', $t_bill_id) 
->where('bills.t_bill_Id', '=', $t_bill_id) 
->select('bil_item.*')
->get();
//dd($embsection3);

$billdata = DB::table('bills')
->where('work_id', $workId)
->orderBy('t_bill_id', 'desc') // 'desc' is the column, 'desc' is the sorting order (descending)
->get();
//dd($billdata);

$convertedbilldata=[];
//convert amount to indian rupees function


$convert=new CommonHelper();


foreach($billdata as $bill)
{
    //dd($bill);

 // Format the required columns
 $bill->bill_amt = $convert->formatIndianRupees($bill->bill_amt);
 $bill->c_billamt = $convert->formatIndianRupees($bill->c_billamt);
 $bill->p_bill_amt = $convert->formatIndianRupees($bill->p_bill_amt);
 $bill->rec_amt = $convert->formatIndianRupees($bill->rec_amt);
 $bill->net_amt = $convert->formatIndianRupees($bill->net_amt);
 $bill->c_netamt = $convert->formatIndianRupees($bill->c_netamt);
 $bill->p_net_amt = $convert->formatIndianRupees($bill->p_net_amt); 
 //dd($convertamount);
 $convertedbilldata[] = $bill;
}


  // Return a success response bill view page
  return response()->json(['newbill' => $insertedRow, 'billdata' => $billdata,
  'billNos' => $billNos,  'workId'=> $workId,  
  'embsection2' => $embsection2, 
  'embsection3' => $embsection3,
  'currentBillId' => $iscurrentbill ? $iscurrentbill->t_bill_id : null, // Pass the current bill ID
  'billItemsData' => $billItemsData, 'tBillId' => $tBillId , 'newbilldt' => $newbilldt , 'newmeasdtfr' => $newmeasdtfr , 'newmessupto' => $newmessupto]);
 
 
 
  } catch (\Exception $e) {
    Log::error('Error in newbillfunction: ' . $e->getMessage());

    return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
    ], 422); // 422 Unprocessable Entity
}


  }



//   public function deleteBillConfirmation(Request $request, $tbillid)
// {
//     $bill = DB::table('bills')->where('t_bill_id', $tbillid)->first();

//     if (!$bill) {
//         return response()->json(['message' => 'Bill not found'], 404);
//     }

//     return response()->json(['confirm' => true, 'work_id' => $bill->work_id]);
// }



//function for the delete bill
public function deletebill(Request $request , $tbillid)
    {
        
        
       // Retrieve token from request
    $token=$request->_token;
    // dd($token);
        // Find the bill by ID and delete it
        $bill = DB::table('bills')->where('t_bill_id' , $tbillid)->get();

        $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');
        // dd($request,$tbillid,$workid);

        // Get bill data with specific conditions
        $billgetdata=DB::table('bills')
        ->where('t_bill_Id' , $tbillid)
        ->where('is_current',1)
        ->get();

         // Count the number of bill items associated with the bill
        $billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->count();

          // Count the number of bills associated with the work ID
        $billcount=DB::table('bills')->where('work_id',$workid)->count();
        // dd($billitems,$billgetdata,$billcount);
//dd($workid);
 // If the bill is not found, redirect back with an error message
         if (!$bill) {
            return redirect()->back()->with('error', 'Bill not found');
        }


         //  // If there are bill items or the bill is current, prevent deletion and redirect back with an error message
        //     if ($billitems > 0 || $billgetdata === 0) {
        //         return redirect()->back()->with('error', 'Cannot delete the bill. Bill items exist or the bill is  current.');
            
        //     //return redirect()->back()
        // } 
        // else 
        // {
            
          if ($billgetdata === 0) {
                    return redirect()->back()->with('error', 'Cannot delete the bill. because is  current bill.');
                
                //return redirect()->back()
            } 
            else 
            {
            try {
            // Delete bill and associated data from various tables
            DB::table('bills')->where('t_bill_id', $tbillid)->delete();
            DB::table('bil_item')->where('t_bill_id', $tbillid)->delete();
            DB::table('mat_cons_d')->where('t_bill_id', $tbillid)->delete();
            DB::table('mat_cons_m')->where('t_bill_id', $tbillid)->delete();
            DB::table('recoveries')->where('t_bill_id', $tbillid)->delete();
            DB::table('royal_d')->where('t_bill_id', $tbillid)->delete();
            DB::table('royal_m')->where('t_bill_id', $tbillid)->delete();
            DB::table('embs')->where('t_bill_id', $tbillid)->delete();
            DB::table('billdeds')->where('T_Bill_Id',$tbillid)->delete();
            DB::table('excess')->where('t_bill_Id',$tbillid)->delete();
            
            DB::table('bill_rcc_mbr')->where('t_bill_id',$tbillid)->delete();
            DB::table('chcklst_aud')->where('t_bill_Id',$tbillid)->delete();
            DB::table('chklst_je')->where('t_bill_Id',$tbillid)->delete();
            DB::table('chklst_pb')->where('t_bill_Id',$tbillid)->delete();
            DB::table('chklst_sdc')->where('t_bill_Id',$tbillid)->delete();

            DB::table('part_rt_d')->where('t_bill_id',$tbillid)->delete();
            DB::table('part_rt_ms')->where('t_bill_id',$tbillid)->delete();
            DB::table('recordms')->where('t_bill_id',$tbillid)->delete();
            DB::table('stlmeas')->where('t_bill_id',$tbillid)->delete();


         // Get the maximum bill ID associated with the work ID
             $MaxTbillid=DB::table('bills')
            ->where('work_id',$workid)
            ->max('t_bill_id');
            // dd($MaxTbillid);

// dd($workid);
            // Update the 'is_current' field for the maximum bill ID
            $UpdateIsCurrent=DB::table('bills')
            ->where('t_bill_id',$MaxTbillid)
            ->update(['is_current' => 1]);

          // Update the 'is_previous' field for bill items associated with the maximum bill ID
            $UpdateIsprevious=DB::table('bil_item')
            ->where('t_bill_id',$MaxTbillid)
            ->update(['is_previous' => 1]);
            // dd( $UpdateIsCurrent);
            
             // Redirect back with a success message
               return redirect()->back()->with('DELETE', 'Bill deleted successfully');

        
            } catch (\Exception $e) {
                // Rollback transaction if any operation fails
                DB::rollback();
                Log::error('Error deleting bill: ' . $ex->getMessage());
                return redirect()->back()->with('error', 'An error occurred while deleting the bill. Please try again.');
            }
        }
    
    }


       //Edit bill data 
        public function editbilldata($id)
        {
            // Get the work ID associated with the bill
            $workid=DB::table('bills')->where('t_bill_id' , $id)->value('work_id');

           // dd($id);
           // Get the current server date using Carbon
           $serverDate = Carbon::now('Asia/Kolkata');
        // Calculate the date 180 days ago
          $date180DaysAgo = $serverDate->subDays(180)->format('d-m-Y');
         //dd($date180DaysAgo);


 // Get the formatted work order date from the database
    $formattedDate=DB::table('workmasters')->where('work_id' , $workid)->value('Wo_Dt');
   // dd($formattedDate);
    $workorderdt = date('d-m-Y', strtotime($formattedDate));
       $lastbilldt='';

        // Get the second-last bill data associated with the work ID
        $lastbilldata= DB::table('bills')
        ->where('work_id', '=', $workid)
        ->orderByDesc('t_bill_id') // Order by t_bill_id in descending order
        ->skip(1) // Skip the first result (last bill)
        ->take(1) // Take only one result (second-last bill)
        ->select('bills.*', 'bills.t_bill_id', 'bills.t_bill_no')
        ->first();

         // If the second-last bill data exists, calculate the date for the last bill
        if ($lastbilldata && $lastbilldata->t_bill_id) {
            $lastbilldt = date('d-m-Y', strtotime($lastbilldata->Bill_Dt . ' +1 day'));
            // Do something with $lastbilldt
        } else {
            // Handle the case where $lastbilldata is null or t_bill_id is not present
            // You can set a default value or perform error handling
        }
  //dd($lastbilldt);
      //  dd($lastbilldata->Bill_Dt , $lastbilldt , $workorderdt);

       // Generate the first bill ID
         $firstbill=$workid.'0001';
         //dd($firstbill);

         // If the current bill ID is the first bill
         if($id === $firstbill)
         {
             // Determine the minimum and maximum dates for the bill based on the work order date and 180 days ago
            if (strtotime($workorderdt) > strtotime($date180DaysAgo)) {
                $minimumdt = $workorderdt;
                $mindt=$workorderdt;
                $maximumdt=$workorderdt;
            } else {
                $minimumdt = $date180DaysAgo;
                $mindt = $date180DaysAgo;
                $maximumdt=$date180DaysAgo;
            }
           // dd($workorderdt  ,$date180DaysAgo,$minimumdt);

           // Get normal and steel measurements for the bill
            $normalmeas = DB::table('embs')->where('t_bill_id', $id)->pluck('measurment_dt');
            $steelmeas = DB::table('stlmeas')->where('t_bill_id', $id)->pluck('date_meas');
    
            // Combine the measurement dates
             $combinedDates = $normalmeas->merge($steelmeas);

             // If there are combined dates, determine the minimum and maximum dates
             if ($combinedDates->isNotEmpty()) {
                $maxDate = $combinedDates->max();
                $minDate = $combinedDates->min();
       
             if(strtotime($mindt) > strtotime($minDate))
              {

                $minimumdt = $mindt;

                }
                else{

                    $minimumdt = $minDate;
                }

                if(strtotime($mindt) > strtotime($maxDate))
                {
                    $maximumdt=$mindt;
                }
                else
                {
                    $maximumdt=$maxDate;
                }
                //dd($maximumdt , $minimumdt,  $workorderdt , $date180DaysAgo);
                // Use $maxDate and $minDate as needed
                }

         }
         // For non-first bills, determine the minimum and maximum dates based on the last bill date and 180 days ago
         else
         {

            //dd($lastbilldt);
            if (strtotime($lastbilldt) > strtotime($date180DaysAgo)) {
                $minimumdt = $lastbilldt;
                $mindt=$lastbilldt;
                $maximumdt=$lastbilldt;
            } else {
                $minimumdt = $date180DaysAgo;
                $mindt=$date180DaysAgo;
                $maximumdt=$date180DaysAgo;
            }
            $minimumdate =  $lastbilldt;
           // dd($lastbilldt  ,$date180DaysAgo,$minimumdt , $mindt);

           // Get normal and steel measurements for the bill
            $normalmeas = DB::table('embs')->where('t_bill_id', $id)->pluck('measurment_dt');
            $steelmeas = DB::table('stlmeas')->where('t_bill_id', $id)->pluck('date_meas');
    
             $combinedDates = $normalmeas->merge($steelmeas);

              // If there are combined dates, determine the minimum and maximum dates
             if ($combinedDates->isNotEmpty()) {
                $maxDate = $combinedDates->max();
                $minDate = $combinedDates->min();
       //dd($mindt);
             if(strtotime($mindt) > strtotime($minDate))
              {

                $minimumdt = $mindt;

                }
                else{

                    $minimumdt = $minDate;
                }

                if(strtotime($mindt) > strtotime($maxDate))
                {
                    $maximumdt=$mindt;
                }
                else
                {
                    $maximumdt=$maxDate;
                }
                //dd($maximumdt , $minimumdt,  $mindt , $maxDate , $workorderdt , $date180DaysAgo);
                // Use $maxDate and $minDate as needed
               
            }
           // dd($maximumdt , $minimumdt,  $lastbilldt , $date180DaysAgo);
         }


    

         // Format the minimum date for the view
         $minimumdate = date('Y-m-d', strtotime($minimumdt));

         // Get the last bill data
            $lastBill=DB::table('bills')->where('t_bill_Id' , $id)->first();

           // Return the view with necessary data
            return view('Editbill', ['lastBill' => $lastBill , 'date180DaysAgo' => $date180DaysAgo , 'minimumdt' => $minimumdate ,
              'maximumdt' => $maximumdt , 'lastbilldt' => $lastbilldt]);
        }

  
           //Update the bill data
        public function updatebilldata(Request $request , $tbillid)
        {
         

              // Get all form inputs
           
              $allInputs = $request->all();
              //dd($allInputs);
              $previousBillDate = $allInputs['previousbilldt'];
              $tBillId = $allInputs['t_bill_Id'];
              $tBillNo = $allInputs['t_bill_No'];
              $billAmt = $allInputs['bill_amt'];
              $recAmt = $allInputs['rec_amt'];
              $netAmt = $allInputs['net_amt'];

              $billdt = $allInputs['Bill_Dt'];
              $measdtfr = $allInputs['measdtfr'];
              $measdtupto = $allInputs['measdtupto'];
              $gstrate = $allInputs['gstrate'];
              $cv_no = $allInputs['cv_no'];
              $cv_dt = $allInputs['cv_dt'];
              $bill_type = $allInputs['bill_type'];

               // Get the work ID associated with the bill
              $workId=DB::table('bills')->where('t_bill_id' , $tBillId)->value('work_id');

               // Get the work order date and stipulated completion date
              $workorderdt=DB::table('workmasters')->where('work_id' , $workId)->value('Wo_Dt');

$stipulateddt=DB::table('workmasters')->where('work_id' , $workId)->value('Stip_Comp_Dt');

    // Get the previous bill data associated with the work ID
    $previousBill = DB::table('bills')
    ->where('work_id', $workId)
    ->where('t_bill_id', '<', $tBillId) // Find bills with 't_bill_id' less than the current one
    ->orderByDesc('t_bill_id') // Order them in descending order of 't_bill_id'
    ->first(); // Retrieve the first (latest) previous bill

     // Get the previous bill date or null if not available
$previousbilldt = $previousBill->Bill_Dt ?? null;
// Convert $previousbilldt to a timestamp, add one day (86400 seconds), and then format it as a date
$previousbillDate = date('Y-m-d', strtotime($previousbilldt . ' +1 day'));

//dd($previousbilldt);
//$reviseddt=DB::table('workmasters')->where('work_id' , $workId)->value('revised_Dt');


$isFirstBill = $workId.'0001';// Determine if it's the first bill (you need to define this condition)
//dd($isFirstBill);
if ($isFirstBill === $tBillId) {
    // Check if $billDt is within the date range ($workorderdt to $stipulateddt) or ($workorderdt to $reviseddt)
    if (empty($reviseddt) && ($billdt >= $workorderdt && $billdt <= $stipulateddt) || (!empty($reviseddt) && $billdt >= $workorderdt && $billdt <= $reviseddt)) {
        // $billDt is within the date range for the first bill
        // Your code logic for the first bill goes here
        //dd($workorderdt , $stipulateddt);
    } else {
        // $billDt is not within the date range for the first bill
        // Handle the case where $billDt is outside the expected range
         // Set a flag to indicate that the date is invalid
         echo "
         <script>
         sweetAlertConfig = {
             icon: 'error',
             title: 'Error',
             text: 'Bill date is not within the expected range for the first bill.'
         };
     </script>";
     Session::flash('error', 'Bill date is not within the expected range for the first bill.');
     return redirect()->back(); // Redirect back to the form view   
     }
} else {
    // Handle the case where it's not the first bill
    //dd('ok');
    if (empty($reviseddt) && ($billdt >= $previousbillDate && $billdt <= $stipulateddt) || (!empty($reviseddt) && $billdt >= $previousbillDate && $billdt <= $reviseddt)) {
        // $billDt is within the date range for the first bill
        // Your code logic for the first bill goes here
    } else {
        // $billDt is not within the date range for the first bill
        // Handle the case where $billDt is outside the expected range
        echo "
        sweetAlertConfig = {
            icon: 'error',
            title: 'Error',
            text: 'Bill date is not within the expected range for subsequent bills.'
            
        };
    </script>";
    Session::flash('error', 'Bill date is not within the expected range for subsequent bills.');
    return redirect()->back(); // Redirect back to the form view
    }
}


                // Retrieve the final bill value from the session
    $finalBillValue = session()->get('finalBillValue');

    // Set the default value for 'final_bill' if it is null
    $finalBillValue = $finalBillValue ?? 0;
           
           //dd($tbillid);

            // Update the bill data in the database
              DB::table('bills')->where('t_bill_Id' , $tbillid)->update([
               'Bill_Dt' => $billdt,
               'meas_dt_from'=> $measdtfr,
               'meas_dt_upto'=> $measdtupto,
               'gst_rt'=> $gstrate,
               'cv_no'=> $cv_no,
               'cv_dt'=> $cv_dt,
               'bill_type'=> $bill_type,
               'final_bill'=> $finalBillValue
              ]);
        //dd($bill_type);

        //$workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');
            //$billdt=$request->newrabillid;
             // Redirect to the bill list view with a success message
            $redirectUrl = route('billlist', ['workid' => $workId]);

            Alert::success('Congrats', 'You\'ve Succesfully Edit Bill  data');
            //dd($redirectUrl);
                   return redirect($redirectUrl);
        }


        //Bill view function
        public function workmasterdata(Request $request, $tbillid)
        {
     
        // Store the tbillid in a session variable
        //session(['tbillid' => $tbillid]);
         //dd($tbillid);
         //dd($Work_Id);
            $Work_Id = DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
     
             // Store the $workId in a session variable
         //session(['workId' => $workId]);
     
             // Fetch workmasters information based on work_id
             $embsection1 = DB::table('workmasters')
             //     ->leftjoin('workmasters', 'embs.Work_Id', '=', 'workmasters.workid')
                ->leftjoin('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                ->leftjoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
                 ->leftjoin('jemasters', 'jemasters.subdiv_id', '=', 'workmasters.Sub_Div_Id')
                ->where('workmasters.Work_Id', '=', $Work_Id)
                ->select('workmasters.Work_Id', 'workmasters.Sub_Div', 'workmasters.Agency_Nm', 'workmasters.Work_Nm', 'workmasters.F_H_Code', 'divisions.div', 'jemasters.name', 'workmasters.Tender_Id')
                ->first();
        //dd($embsection1);
            // Fetch embsection1a data
            $embsection1a = DB::table('fundhdms')
                ->select('fundhdms.Fund_Hd_M')
                ->leftJoin('workmasters', function ($join) {
                    $join->on(DB::raw('LEFT(workmasters.F_H_Code, 4)'), '=', DB::raw('LEFT(fundhdms.F_H_Code, 4)'));
                })
                ->where('workmasters.Work_Id', $Work_Id)
                ->first();
     
            // Fetch embsection2 data
            $embsection2 = DB::table('bills')
                ->where('t_bill_Id', '=' , $tbillid)
                ->first();
     
     
     
                $newmeasdtfrformat = $embsection2->meas_dt_from ?? null;
                $newmeasdtfr = date('d-m-Y', strtotime($newmeasdtfrformat));
             $newmessuptoformat=$embsection2->meas_dt_upto ?? null;
             $newmessupto = date('d-m-Y', strtotime($newmessuptoformat));
           $formatpreviousbilldt=$embsection2->previousbilldt ?? null;
           $previousbilldt = date('d-m-Y', strtotime($formatpreviousbilldt));
        //dd($embsection2);
            // Fetch billNos based on work_id
            $billNos = DB::table('bills')
                ->where('work_id', $Work_Id)
                ->orderBy('t_bill_No', 'desc')
                ->pluck('t_bill_No', 't_bill_id');
     
         //    // Fetch embsection3 data based on work_id
         //    $embsection3 = DB::table('bil_item')
         //        ->leftjoin('bills', 'bills.t_bill_Id', '=', 'bil_item.t_bill_Id')
         //        ->leftjoin('tnd_item', 'tnd_item.t_item_id', '=', 'bil_item.t_item_id')
         //        ->leftjoin('embs', 'embs.b_item_id', '=', 'bil_item.b_item_id')
         //        ->where('bills.work_id', '=', $workId)
         //        ->orderBy('b_item_id', 'desc')
         //        ->select('bil_item.*')
         //        ->get();
     
          // Get the last t_bill_Id
     $lastTBillId = DB::table('bills')
         ->where('work_id', '=', $Work_Id)
         ->orderBy('t_bill_Id', 'desc')
         ->value('t_bill_Id');
      //dd($lastTBillId);
     // Get all records related to the last t_bill_Id
     $embsection3 = DB::table('bil_item')
         ->where('t_bill_id', '=', $tbillid)
         ->paginate(5); // Paginate with 5 items per page
         
         //dd($embsection3);
         $paginationdata = DB::table('bil_item')
         ->where('t_bill_id', '=', $tbillid)
         ->paginate(5); // Display 10 items per page
     //dd
         // Get the current page from the query parameters
         $currentPage = request()->input('page', 1);
     //dd($currentPage);
         // Get the previous page from the session
         $previousPage = Session::get('previous_page', 1);
         // Store the current page as the previous page for the next request
         Session::put('previous_page', $currentPage);
         //dd($currentPage , $previousPage);
     
      //dd($currentPage , $previousPage);
         // Query embsection3 data with the previous page number
         // $embsection3 = DB::table('bil_item')
         //     ->where('t_bill_id', $tbillid)
         //     ->paginate(5 ,['*'], 'page', $currentPage);   
     
             // if($currentPage > 1)
             // {
                 $embsection3 = DB::table('bil_item')
                 ->where('t_bill_id', $tbillid)
                 ->paginate(5);
         
             // }
         // Perform your pagination query using Eloquent or Query Builder
         //$data = YourModel::paginate(10);
     
      //$links = $paginationdata->links('pagination::bootstrap-4')->toHtml(); // Use default pagination style
     
        $mbstatus= DB::table('bills')
         ->where('t_bill_id', $tbillid)
         ->value('mb_status'); 
     
         //dd($tbillid);
         $total = number_format((float)($embsection2->c_part_a_amt + $embsection2->c_part_b_amt), 2, '.', '');
     //dd($total);
     
              $convertamout = new CommonHelper();
        
              $total=$convertamout->formatIndianRupees($total);
      
     
              if ($embsection3->isEmpty()) 
         {
            // No data found, set a flash message
            Session::flash('error', 'No Measurement found for the given Tender Item!');
         }

     
     
         // Apply additional ordering by 't_item_no' in ascending order
            return view('Viewbill', compact('mbstatus','embsection1', 'embsection1a', 'embsection2', 'embsection3', 'billNos' , 'newmeasdtfr' , 'newmessupto' , 'previousbilldt' , 'previousPage' , 'currentPage' , 'total'));
        
            }    


         
          //Main Progress bar data fetch 
     public function billprogressbar(Request $request)
     {
        $workId = $request->input('workId'); // Retrieving the 'workId' from the request        
        //dd($workId);
        $mbstatus = DB::table('bills')->where('work_id', $workId)
        ->orderBy('t_bill_Id', 'desc')
        ->value('mb_status');

        //dd($mbstatus);
        return response()->json(['mbstatus' => $mbstatus]);
     }
     
     //Progress bar SO fetch 
          public function billprogressbarSO(Request $request)
            {
                $workId = $request->input('workId'); // Retrieving the 'workId' from the request        
                //dd($workId);
                $mbstatus = DB::table('bills')->where('work_id', $workId)
                ->orderBy('t_bill_Id', 'desc')
                ->value('mbstatus_so');

                //dd($mbstatus);
                return response()->json(['mbstatus' => $mbstatus]);
            }


     // Function to view documents associated with a bill
     public function viewdocument($tbillid)
     {
         // Your existing code to retrieve paths
         $Paths = DB::table('bills')->where('t_bill_id', $tbillid)->first();
     
         // Construct the full URLs using asset() helper function
         $assetBaseUrl = asset('storage/');
         $imagePath = public_path('images/sign.jpg');
         $imageData = base64_encode(file_get_contents($imagePath));
         $imageSrc = 'data:image/jpeg;base64,' . $imageData;
         
         $imagePath2 = public_path('images/sign2.jpg');
         $imageData2 = base64_encode(file_get_contents($imagePath2));
         $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;
         
         $paths = [
             'photo1' => $assetBaseUrl . '/' . $Paths->photo1,
             'photo2' => $assetBaseUrl . '/' . $Paths->photo2,
             'photo3' => $assetBaseUrl . '/' . $Paths->photo3,
             'photo4' => $assetBaseUrl . '/' . $Paths->photo4,
             'photo5' => $assetBaseUrl . '/' . $Paths->photo5,
             'doc1'   => $assetBaseUrl . '/' . $Paths->doc1,
             'doc2'   => $assetBaseUrl . '/' . $Paths->doc2,
             'vdo'    => $assetBaseUrl . '/' . $Paths->vdo,
         ];
     
         // Return the view with the paths
         return view('viewdocument', compact('paths'));
     }



     // Function to delete images, documents, or videos associated with a bill
     public function deleteimgdocvdo(Request $request)
     {

        $colname=$request->identifier;
        $tbillid=$request->billId;
      //dd($tbillid , $colname);

      // Update the bill record in the database, setting the specified column to null
      DB::table('bills')->where('t_bill_Id', $tbillid)->update([$colname => null]);


       // Return a JSON response indicating success
      return response()->json(['status' => 'success']);
    }
    
    
    // function for the work haNDOVER CERTIFICATE 
         public function uploadWHOC(Request $request)
            {
        
                try {
                    // Ensure the request has the necessary file and tbillid
                    if (!$request->hasFile('File')) {
                        throw new FileNotFoundException('File is missing.');
                    }
                    if (!$request->has('tbillid')) {
                        return response()->json(['error' => 'tbillid is missing.'], 400);
                    }
            
                    $file = $request->file('File');
                    $tbillid = $request->input('tbillid');
            
                    // Validate the file type and size if needed
                    $request->validate([
                        'File' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
                    ]);
            
                    // Generate a unique name for the file
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $uniqueFileName = time() . uniqid() . $originalName;
            
                    // Move the uploaded file to the "public/uploads/Workhandovercertificates" directory
                    $file->move(public_path('Uploads/Workhandovercertificates'), $uniqueFileName);
            
                    // Retrieve the previous paths from the database
                    $previousPaths = DB::table('bills')->where('t_bill_id', $tbillid)->first();
            
                    // Update the database with the new file name
                    DB::table('bills')->where('t_bill_id', $tbillid)->update([
                        'WHOCdocument' => $uniqueFileName,
                    ]);
            
                    return response()->json(['message' => 'File uploaded successfully.']);
                } catch (FileNotFoundException $e) {
                    // Log the exception for debugging purposes
                    Log::error('File upload error: ' . $e->getMessage());
            
                    // Return a JSON response with the error message
                    return response()->json(['error' => 'File is missing.'], 400);
                } catch (\Exception $e) {
                    // Log the exception for debugging purposes
                    Log::error('File upload error: ' . $e->getMessage());
            
                    // Return a JSON response with the error message
                    return response()->json(['error' => 'An error occurred while uploading the file.'], 500);
                }
                    }
}




