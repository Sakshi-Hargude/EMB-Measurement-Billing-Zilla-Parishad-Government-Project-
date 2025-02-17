<?php

namespace App\Http\Controllers;

use App\Models\Emb;
use Carbon\Carbon; 
use App\Models\Workmaster;
use App\Imports\ExcelImport;
use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Log;


// Your code that uses LocalFilesystemAdapter

// ... your code


// ... your code


class EmbController extends Controller
{

  //section

   //emblist in that section1 for workmasterdata,div data,fund head data table fetching function
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
           ->select('workmasters.Work_Id', 'workmasters.Sub_Div', 'workmasters.Agency_Nm', 'workmasters.Work_Nm', 'workmasters.F_H_Code', 'divisions.div', 'jemasters.name')
           ->first();
   //dd($embsection1);
       // Fetch embsection1a data
              $workdata=DB::table('workmasters')->where('Work_Id' , $Work_Id)->first();
       $embsection1a = DB::table('fundhdms')->where('F_H_id' , $workdata->F_H_id)->first('Fund_Hd_M');


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

        //dd($tbillid);
    $total = number_format((float)($embsection2->c_part_a_amt + $embsection2->c_part_b_amt), 2, '.', '');
     //dd($total);
      $convertamout = new CommonHelper();

      $total=$convertamout->formatIndianRupees($total);

//dd($total);
    // Apply additional ordering by 't_item_no' in ascending order
              $mbstatusSo=DB::table('bills')
          ->where('t_bill_id', $tbillid)
          ->where('work_id', $Work_Id)
          ->value('mbstatus_so');
        //   dd($mbstatusSo);

       return view('listemb', compact('mbstatus','embsection1', 'embsection1a', 'embsection2', 'embsection3', 'billNos' , 'newmeasdtfr' , 'newmessupto' , 'previousbilldt' , 'previousPage' , 'currentPage' , 'total','mbstatusSo'));
   }


 //section 2 data retrive if present or not
 public function checkDataAvailability(Request $request)
    {
        $workId = $request->input('work_id');
    //dd($workId);
    $embsection2 = DB::table('bills')
           //->leftjoin('embs', 'embs.t_bill_id', '=', 'bills.t_bill_id')
           ->join('workmasters', 'bills.work_id', '=', 'workmasters.Work_Id')
           ->where('workmasters.Work_Id', '=', $workId)
           ->select('bills.*')
           ->orderBy('bills.t_bill_No', 'desc')
           ->first();



    // Fetch billNos based on work_id
       $billNos = DB::table('bills')
       ->where('work_id', $workId)
       ->orderBy('t_bill_No', 'desc')
       ->pluck('t_bill_No', 't_bill_id');

 //dd($embsection2);
    return response()->json(['embsection2' => $embsection2 , 'billNos' => $billNos]);
}

// ajax dropdown for bill nos

  public function ajaxbilldropdown(Request $request)
  {
    $workId = $request->workid;

    //dd($workId);
       $billNos = DB::table('bills')->pluck('t_bill_id');
       $selectedBillId = $request->selectrabill;

      $embsection2 = DB::table('bills')
          ->leftjoin('embs', 'embs.t_bill_id', '=', 'bills.t_bill_id')
          ->join('workmasters', 'workmasters.Work_Id', '=', 'bills.work_id')
          ->where('bills.t_bill_id', '=', $request->selectrabill)
          ->select('bills.*')
          ->first();


          $newmeasdtfrformat=$embsection2->meas_dt_from ?? null;
          $newmeasdtfr = date('d-m-Y', strtotime($newmeasdtfrformat));
          $newmessuptoformat=$embsection2->meas_dt_upto ?? null;
          $newmessupto = date('d-m-Y', strtotime($newmessuptoformat));

//dd($embsection2);
   $tbillid=$embsection2->t_bill_Id;
   //dd($tbillid);

          $embsection3 = DB::table('bil_item')
      ->leftjoin('bills', 'bills.t_bill_Id', '=', 'bil_item.t_bill_Id')
       ->leftjoin('tnditems', 'tnditems.t_item_id', '=', 'bil_item.t_item_id')
    //->leftjoin('embs', 'embs.b_item_id', '=', 'bil_item.b_item_id')
    ->where('bil_item.t_bill_Id', '=', $request->selectrabill)
    ->where('bills.t_bill_Id', '=', $request->selectrabill)
    ->orderBy('bil_item.t_item_no', 'asc') // Adding the additional orderBy clause for t_item_no ascending
    ->select('bil_item.*')
    ->get();
//dd($embsection3);



          return response()->json([
            'billNos' => $billNos,
            'embsection2' => $embsection2,
            'embsection3' => $embsection3,
            't_bill_id' => $embsection2->t_bill_Id ?? '',
            'newmeasdtfr' => $newmeasdtfr , 'newmessupto' => $newmessupto,

        ]);

  }




//New bill create function
public function newbillfunction(Request $request)
{


    $workId = $request->workid;

  //dd($workId);

    // Get the last bill in the database
    $lastBill = DB::table('bills')
         ->where('bills.work_id', '=', $workId)
        ->orderBy('t_bill_id', 'desc')
         ->select('bills.*','bills.t_bill_id','bills.t_bill_no')
        ->first();
//dd($lastBill);

        $formattedDate=DB::table('workmasters')->where('work_id' , $workId)->value('Wo_Dt');
        $workorderdt = date('d-m-Y', strtotime($formattedDate));
//dd($workorderdt);

    if ($lastBill) {
        // Generate new bill ID
        $lastFourDigits = substr($lastBill->t_bill_id, -4);
        $newLastFourDigits = str_pad((intval($lastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
        $newBillId = substr_replace($lastBill->t_bill_id, $newLastFourDigits, -4);

        // Increment bill number
        $newBillNo = $lastBill->t_bill_No + 1;

        //$firstbillgstrate=DB::table('')

        $lastBillDate = $lastBill->Bill_Dt; // Assuming $lastBill->Bill_Dt is in a valid date format
         $nextDayDate = date('d-m-Y', strtotime($lastBillDate . ' +1 day'));
        // dd($nextDayDate);
        // Get the bill amount from the previous bill record
        //$workid = $workId;
       // dd($workid);
        $billAmt = $lastBill->bill_amt ;
        $recamt = $lastBill->rec_amt;
        $netamt = 0 ;
        $finalbill = $lastBill->final_bill ;
        $cvno = $lastBill->cv_no ;
        $cvdate = $lastBill->cv_dt ;
        $billdt = $nextDayDate ;
        $billtype = $lastBill->bill_type ;
        $measdtfr = $nextDayDate;
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
		$p_gst_base = $lastBill->gst_base;
		$p_net_amt = $lastBill->net_amt;
        $p_gst_rt = $lastBill->gst_rt;
        $p_gst_amt = $lastBill->gst_amt;
        $p_bill_amt_gt = $lastBill->bill_amt_gt;
        $p_bill_amt_ro = $lastBill->bill_amt_ro;
        $p_bill_dt = $lastBill->Bill_Dt;
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
		$p_gst_base = 0;
		$p_net_amt = 0;
        $p_gst_rt = 0;
        $p_bill_amt_gt = 0;
        $p_bill_amt_ro = 0;
        $p_bill_amt= 0;
        $p_gst_amt= 0;
        $p_bill_dt = null;
        //dd($billtype);$
     }
// dd($newBillId);
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
		'p_gst_base' => $p_gst_base,
		'p_net_amt' => $p_net_amt,
        'p_gst_rt' => $p_gst_rt,
        'p_bill_amt_gt' => $p_bill_amt_gt,
        'p_bill_amt_ro'	=> $p_bill_amt_ro,
        'p_gst_amt'=> $p_gst_amt,
        'p_bill_dt' => $p_bill_dt
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

           //dd($serverDate);

        // Calculate the date 180 days ago
        $date180DaysAgo = $serverDate->subDays(180)->format('d-m-Y');
//dd($date180DaysAgo);


return response()->json([
        'newBillData' => $newBillData,
        'newBillId' => $newBillId,
        'newBillNo' => $newBillNo,
        'lastbill' => $lastBill,
        'newbilitems'=>$newbilitems,
        'firstid' => $firstid,
        'date180DaysAgo' => $date180DaysAgo,

    ]);
}

  //update final bill
  public function updateFinalBill(Request $request)
  {
    $Workid=$request->workid;
   // dd($Workid);
  // dd($workid, $request->final_bill , $request->work_completed , $request->work_completed_date);

    if ($request->has('work_completed') && $request->has('work_completed_date')) {
        // Update the database
         DB::table('workmasters')->where('Work_Id' , $Workid)->update([
            'work_comp' => $request->work_completed,
            'actual_complete_date' => $request->work_completed_date
        ]);
      
    }
    else{

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
    $formData = $request->input('formData');
   // dd($formData);
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
      $recAmt = $request->input('bill_amt');
      $netAmt = $request->input('net_amt');
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
//dd($previousBill);
$previousbilldt = $previousBill->Bill_Dt ?? null;
// Convert $previousbilldt to a timestamp, add one day (86400 seconds), and then format it as a date
$previousbillDate = date('Y-m-d', strtotime($previousbilldt . ' +1 day'));

//dd($previousbilldt);
//$reviseddt=DB::table('workmasters')->where('work_id' , $workId)->value('revised_Dt');


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
    $finalBillValue = session()->get('finalBillValue');

    // Set the default value for 'final_bill' if it is null
    $finalBillValue = $finalBillValue ?? 0;

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
        'meas_dt_from' => $measdtfr,
        'meas_dt_upto' => $measdtupto,
        'gst_rt' => $gstrate,

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
		'p_gst_base' => $newBillData['p_gst_base'],
		'p_net_amt' => $newBillData['p_net_amt'],
        'p_gst_rt' => $newBillData['p_gst_rt'],
        'p_bill_amt_gt' => $newBillData['p_bill_amt_gt'],
        'p_bill_amt_ro' => $newBillData['p_bill_amt_ro'],
        'p_gst_amt' => $newBillData['p_gst_amt'],
        'previousbilldt' =>$newBillData['p_bill_dt'],
    ]);
    // Retrieve the inserted row from the database
    $insertedRow = DB::table('bills')->where('t_bill_Id', $tBillId)->first();


     // Fetch embsection2 data
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


$embsection3 = DB::table('bil_item')
->leftjoin('bills', 'bills.t_bill_Id', '=', 'bil_item.t_bill_Id')
->leftjoin('tnditems', 'tnditems.t_item_id', '=', 'bil_item.t_item_id')
->leftjoin('embs', 'embs.b_item_id', '=', 'bil_item.b_item_id')
->where('bil_item.t_bill_Id', '=', $t_bill_id)
->where('bills.t_bill_Id', '=', $t_bill_id)
->orderBy('bil_item.t_item_no', 'asc') // Adding the additional orderBy clause for t_item_no ascending
->select('bil_item.*')
->get();
//dd($embsection3);


      // Return a success response
      return response()->json(['newbill' => $insertedRow,
      'billNos' => $billNos,
      'embsection2' => $embsection2,
      'embsection3' => $embsection3,
      'currentBillId' => $iscurrentbill ? $iscurrentbill->t_bill_id : null, // Pass the current bill ID
      'billItemsData' => $billItemsData, 'tBillId' => $tBillId , 'newbilldt' => $newbilldt , 'newmeasdtfr' => $newmeasdtfr , 'newmessupto' => $newmessupto]);
  }



//edit tenser item function

public function tenderitemedit(Request $request)
{
      try{
    $bitemid = $request->input('bitemId');
 //dd($bitemid);

 $bill_item=DB::table('bil_item')->where('b_item_id' , $bitemid)->first();
 //dd($bill_item);
 
     if (!$bill_item) {
        return response()->json(['error' => 'Bill item not found'], 404);
    }


$convertamout = new CommonHelper();

 $bill_item->b_item_amt = $convertamout->formatIndianRupees($bill_item->b_item_amt);



 $billtemdata=DB::table('bil_item')->where('b_item_id', $bitemid)->first();

 $tbillid=DB::table('bil_item')->where('b_item_id', $bitemid)->value('t_bill_id');

 $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

 $work_id=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

 $convert=new CommonHelper();

 $workmasterdetail=DB::table('workmasters')->where('work_id' , $work_id)->first();
 $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
 '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
 '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
'</div></div>';
  //dd($workdetail);


  return response()->json(['bill_item' => $bill_item, 'workdetail' => $workdetail]);

} catch (\Exception $e) {
    Log::error('Error in tenderitemedit: ' . $e->getMessage());

    return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
}
}

//update the tender item from edited
public function Updatedtenderitem(Request $request)
{
     try{
         
      // Access the data sent from the client-side
        $bitemId = $request->input('bitemId');
       // dd($bitemId);
        $item = $request->input('item');
        $tenderItemNo = $request->input('tenderItemNo');
        $tenderQuantity = $request->input('tenderQuantity');
        $uptoDateQty = $request->input('uptoDateQty');
        $tenderRate = $request->input('tenderRate');
        $billRate = $request->input('billRate');
        $rateCode = $request->input('rateCode');
        $unit = $request->input('unit');
        $amount = $request->input('amount');

        if ($billRate > $tenderRate) {
            // If billRate is greater, do not insert values into the table
            // You can return a response indicating the error, or take other actions as needed.
            return response()->json(['message' => 'Bill Rate cannot be greater than Tender Rate'], 400);
        }


        $titemid= DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

        DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

            'item_desc' => $item,
            'tnd_qty' => $tenderQuantity,
            'tnd_rt' => $tenderRate,
            'bill_rt' => $billRate,
            'ratecode' => $rateCode,
            'b_item_amt' => $amount,
        ]);




        $tbillid= DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_bill_id');


        $work_id=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');


        $lasttbillid=DB::table('bills')->where('work_id', $work_id)->orderby('t_bill_id', 'desc')->first();


        $billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->get();



        return response()->json(['billitems' => $billitems , 'lasttbillid' => $lasttbillid ,]);
        
     } catch (\Exception $e) {
        Log::error('Error in Updatetenderitem: ' . $e->getMessage());
    
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}



// rate analysis data for edit
public function rateanalysis(Request $request)
{
    
    try{
        
    
     $bitemId = $request->input('bitemId');
     // dd($bitemId);
     $item = $request->input('item');
     $tenderItemNo = $request->input('tenderItemNo');
     $tenderQuantity = $request->input('tenderQuantity');
     $uptoDateQty = $request->input('uptoDateQty');
     $tenderRate = $request->input('tenderRate');
     $billRate = $request->input('billRate');
     $rateCode = $request->input('rateCode');
     $unit = $request->input('unit');
     $amount = $request->input('amount');

     $titemid= DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
  $tenderdata=DB::table('tnditems')->where('t_item_id' , $titemid)->get();

  $parrtid=DB::table('part_rt_ms')->where('b_item_id' , $bitemId)->value('part_rt_id');
  $reduceddata=DB::table('part_rt_d')->where('b_item_id' , $bitemId)->where('part_rt_id' , $parrtid)->get();
// dd($reduceddata);
  $lastsrno=DB::table('part_rt_d')->where('b_item_id' , $bitemId)->where('part_rt_id' , $parrtid)->orderBy('pra_d_id', 'desc')->first('sr_no');

 // dd($lastsrno);
  $bitemdata = DB::table('bil_item')->where('b_item_id' , $bitemId)->get();
  //dd($bitemdata);


 //workdetails
 $billtemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

 $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

 $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

 $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

$convert=new CommonHelper();

 $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
 $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
 '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees(
    $workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
 '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees(
    $billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
 '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees(
    $tbilldata->curr_grossamt) . '</span>&nbsp;&nbsp;&nbsp;' .
'</div></div>';


  return response()->json(['tenderdata' => $tenderdata , 'tenderItemNo' => $tenderItemNo , 'tenderRate' => $tenderRate ,
  'reduceddata' => $reduceddata , 'bitemdata' => $bitemdata , 'workdetail' => $workdetail , 'lastsrno' => $lastsrno]);
  
    } catch (\Exception $e) {
    Log::error('Error in Rate analysis: ' . $e->getMessage());

    return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
}

}



//submit reduced rate data function
public function reducedratedata(Request $request)
{
    try{

    $tenderItemNo = $request->input('tenderItemNo');
    $tenderRate = $request->input('tenderRate');
    $itemDescription = $request->input('itemDescription');
    $rateCode = $request->input('rateCode');
    $dynamicRowData = $request->input('dynamicRowData');
    //dd($dynamicRowData);
    $ratereducedby = $request->input('ratereducedby');
    $partReducedRate = $request->input('partReducedRate');
    $workid = $request->input('workid');
    $tbillid = $request->input('tbillid');
    $bitemid = $request->input('bitemid');
    //dd($bitemid);

     DB::table('part_rt_d')->where('b_item_id' , $bitemid)->delete();
     DB::table('part_rt_ms')->where('b_item_id' , $bitemid)->delete();

    $previouspartrateid = DB::table('part_rt_ms')->where('b_item_id', '=', $bitemid)->orderBy('part_rt_id', 'desc')->select('part_rt_id')->first();

    if ($previouspartrateid) {
        $previouspartrtid = $previouspartrateid->part_rt_id; // Convert object to string
        // Increment the last four digits of the previous meas_id
         $lastthreeDigits = intval(substr($previouspartrtid, -3));
         $newLastthreeDigits = str_pad(($lastthreeDigits + 1), 3, '0', STR_PAD_LEFT);
         $newpartrtid = $tbillid.$newLastthreeDigits;
         //dd($newprdid);
   } else {
       // If no previous meas_id, start with bitemid.0001
       $newpartrtid = $tbillid.'001';
   }


  $partrateid=DB::table('part_rt_ms')->where('b_item_id', '=', $bitemid)->value('part_rt_id');

        if(!empty($dynamicRowData))
        {


    foreach ($dynamicRowData as $row) {

        $previouspartrtdid = DB::table('part_rt_d')->where('b_item_id', '=', $bitemid)->orderBy('pra_d_id', 'desc')->select('pra_d_id')->first();

     if ($previouspartrtdid) {
         $previousrtid = $previouspartrtdid->pra_d_id; // Convert object to string
         // Increment the last four digits of the previous meas_id
          $lasttwoDigits = intval(substr($previousrtid, -2));
          $newLasttwoDigits = str_pad(($lasttwoDigits + 1), 2, '0', STR_PAD_LEFT);
          $newprdid = $newpartrtid.$newLasttwoDigits;
          //dd($newprdid);
    } else {
        // If no previous meas_id, start with bitemid.0001
        $newprdid = $newpartrtid.'01';
    }
    //dd($newprdid);




    DB::table('part_rt_d')->insert([
        'work_id' => $workid,
        't_bill_id' => $tbillid,
        'b_item_id' => $bitemid,
        'pra_d_id' => $newprdid,
        'part_rt_id' => $newpartrtid,
        'sr_no' =>  $row['srNo'],
        'red_for' => $row['particulars'],
        'formula' => $row['formula'],
        'amt_red' => $row['amountReduced'],
        'red_by' => $ratereducedby,


    ]);


    }

    }

  $titemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('t_item_id');
  $subno=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('sub_no');

    DB::table('part_rt_ms')->insert([
        'work_id' => $workid,
        't_bill_id' => $tbillid,
        'b_item_id' => $bitemid,
        'part_rt_id' => $newpartrtid,
        't_item_id' => $titemid,
        'exs_nm' => $itemDescription,
        'red_by' => $ratereducedby,
        'tnd_rt' => $tenderRate,
        'bill_rt' => $partReducedRate,
        't_item_no' => $tenderItemNo,
        'ratecode' => $rateCode,
        'sub_no' => $subno,
    ]);



    $curqty=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('cur_qty');
    $curamt=$curqty*$partReducedRate;
    $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('previous_amt');
    $bitemamt=$curamt+$previousamt;

    DB::table('bil_item')->where('b_item_id' , $bitemid)->update([
        'ratecode' => $rateCode,
        'cur_amt' => $curamt,
        'b_item_amt' => $bitemamt,
        'bill_rt' => $partReducedRate,
        'part_rt_id' => $newpartrtid,

    ]);

    $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();

$bitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->paginate(5);


        $previousPage = session()->get('previous_page');
        // Append the route to the current URL
        $redirectUrl = redirect()->route('emb.workmasterdata', ['id' => $lasttbillid->t_bill_Id, 'page' => $previousPage])->getTargetUrl();
        //dd($redirectUrl);
        return response()->json(['bitemdata' => $bitemdata , 'lasttbillid' => $lasttbillid , 'redirect_url' => $redirectUrl]);
        
        } catch (\Exception $e) {
            Log::error('Error in tenderitemedit: ' . $e->getMessage());
        
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
}

//ADD TND ITEM function data fetch in modal
  public function Addtenditem(Request $request)
  {
      try
      {
      // Retrieve the 'workid' from the request
      $workId = $request->input('workid');

      // Assuming $workId contains the correct 'workid' value, fetch the tnd_item data related to it
      $items = DB::table('tnditems')
               ->where('work_id', $workId) // Filter the data based on the 'workid'
               ->select('tnditems.*')
                ->get();
          //dd($items);
          
            // Create an instance of the CommonHelper class
            $convertamout = new CommonHelper();

            // Iterate over each item and format the t_item_amt
            foreach($items as $item){
                // Format the t_item_amt and update the item
                $item->t_item_amt = $convertamout->formatIndianRupees($item->t_item_amt);
            }
      // Return the fetched data as a JSON response
      return response()->json($items);
      
          
      }catch(\Exception $e)
      {
          Log::error('An error occur during Add tender' . $e->getMessage());
          
          return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
      }
  }



//check repeateb data function to checkbox for tender items
public function Checkrepeatedata(Request $request)
{
    try{

    $checkdata = $request->input('selectedRowData');
 //dd($checkdata);
 $tbillid = $request->input('tbillid');
      $rowdata[]=$checkdata;
    // Extract the t_item_id values from selectedRowsData
    $selectedTItemIds=array_column($rowdata, 't_item_id');
    //$selectedTItemIds=array_map(create_function('$arr', 'return $arr["t_item_id"];'), $checkdata);
   //dd($selectedTItemIds);

    // Fetch the t_item_id values from the bil_item table
    $tabledata = DB::table('bil_item')->where('t_bill_id', $tbillid)->pluck('t_item_id')->toArray();
    //dd($tabledata);
    //

    //dd($tabledata);





    // Find the common t_item_id values between $selectedTItemIds and $tabledata
    $repeatedTItemIds = array_intersect($selectedTItemIds, $tabledata);
    
//dd($repeatedTItemIds);
// Store the repeated tndids in a session variable
session(['repeatedTndIds' => $repeatedTItemIds]);
    // Get the indices (IDs) of the common t_item_id values in the $selectedTItemIds array
    $repeatedIds = array_intersect($selectedTItemIds, $repeatedTItemIds);
   // dd($repeatedIds);
    if (!empty($repeatedTItemIds)) {
        // Some t_item_id values are repeated, show the SweetAlert popup
        $message = 'These data is  repeated Are you sure you want these data ';
        return response()->json(['message' => $message, 'repeatedIds' => $repeatedIds], 200); // HTTP status code 200 (OK)
    } else {
        // No t_item_id values are repeated, proceed with your logic here
        return response()->json(); // HTTP status code 200 (OK)
    }
}catch(\Exception $e)
 {
    return response()->json(['error' => 'Error occur during the' . $e->getMessage()], 500);
 }
}


//store the selected tnd items in bill items
public function Seleteditems(Request $request)
{
    
     try{


    // Retrieve the data from ajax row data
    $Rowsdata= $request->input('selectedRowsData');
    //dd($Rowsdata);
    $workid = $request->input('work_id');
 // dd($workid);

//  foreach ($Rowsdata as $row) {
//     dd($row);
//     foreach ($row as $key) {
//         // Access $key and $value here, where $key is the column name and $value is the column value
//         dd($key);
//         echo $key . ': ' . $value . '<br>';
//     }
//     echo '<br>';
// }
    // Last bill no id
    $lasttbillid = DB::table('bills')
    ->where('bills.work_id', '=', $workid)
    ->orderBy('t_bill_Id', 'desc')
    ->pluck('t_bill_Id')
    ->first();
     //dd($lasttbillid);
    $lastbitemid = DB::table('bil_item')
        ->where('bil_item.t_bill_id', '=', $lasttbillid)
        ->orderBy('b_item_id', 'desc')
        ->select('b_item_id')
        ->first();
 //dd($lastbitemid);
    // Initialize newbitemid variable
    if ($lastbitemid) {
        // Generate new bill ID
        $lastFourDigits = substr($lastbitemid->b_item_id, -4);
        $newLastFourDigits = str_pad((intval($lastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
        $newbitemid = substr_replace($lastbitemid->b_item_id, $newLastFourDigits, -4);
    } else {
        $newbitemid = $lasttbillid . '0001';
    }
 //dd($newbitemid);

 $repeatedTndIds = session('repeatedTndIds', []);
    // Retrieve the repeated tndids from the session variable

    // Loop through the Rowsdata and insert rows into the bil_item table
    foreach ($Rowsdata as $row) {

        if (in_array($row['t_item_id'], $repeatedTndIds)) {
            // If it's repeated, use the bill_rt column value as partrate
            $ratecode = "Part Rate";
        } else {
            // If it's not repeated, set partrate to a default value or as needed
            $ratecode = "Full Rate"; // Change this to your default value or logic
        }

        //dd($row['t_item_id']);
        $titemdata=DB::table('tnditems')->where('t_item_id' , $row['t_item_id'])->first();
        //dd($titemdata);
        // Insert the row into the bil_item table
        DB::table('bil_item')->insert([
            'b_item_id' => $newbitemid,
            't_bill_id' => $lasttbillid,
            't_item_id' => $titemdata->t_item_id,
            't_item_no' => $titemdata->t_item_no,
            'sub_no' => $titemdata->sub_no,
            'item_desc' => $titemdata->item_desc,
            'tnd_qty' => $titemdata->tnd_qty,
            'item_unit' => $titemdata->item_unit,
            'tnd_rt' => $titemdata->tnd_rt,
            'bill_rt' => $titemdata->tnd_rt,
            'ratecode' => $ratecode,
            'item_id' => $titemdata->item_id,
            'sch_item' => $titemdata->sch_item,
            'exs_nm' => $titemdata->exs_nm,
            'is_previous' => 1, // Add the 'is_previous' column with the value 1
            'prv_bill_qty' => 0,
            'cur_qty' => 0,
            'exec_qty'=>0

                          // Add 't_item_id' value from $row
            // Add other columns and their values from $row as needed
            // Example: 'column_name' => $row['value'],
        ]);

        // Increment newbitemid for the next iteration
        $lastFourDigits = substr($newbitemid, -4);
        $newLastFourDigits = str_pad((intval($lastFourDigits) + 1), 4, '0', STR_PAD_LEFT);
        $newbitemid = substr_replace($newbitemid, $newLastFourDigits, -4);
        //dd($newbitemid);
    }



  // Get all records related to the last t_bill_Id
$allbillitems = DB::table('bil_item')
//

->where('bil_item.t_bill_Id', '=', $lasttbillid)
->orderBy('bil_item.t_bill_Id', 'desc')
->select('bil_item.*')
->paginate(5);
//dd($allbillitems);


$iscurrentvalue = DB::table('bills')
    ->where('bills.work_id', '=', $workid)
    ->where('bills.t_bill_Id', '=', $lasttbillid)
    ->value('is_current');


    $previousPage = session()->get('previous_page');
// Append the route to the current URL
$redirectUrl = redirect()->route('emb.workmasterdata', ['id' => $lasttbillid, 'page' => $previousPage])->getTargetUrl();


    //dd($iscurrentvalue);
// Return the response with the data
return response()->json(['allbillitems' => $allbillitems, 'is_current' => $iscurrentvalue , 'lasttbillid' => $lasttbillid , 'redirect_url' => $redirectUrl]);

  }catch (\Exception $e)
  {
     Log::error('Error in Seleteditems: ' . $e->getMessage());
      
      return response()->json(['error' => 'An error occurred' . $e->getMessage()], 500);
  }
}







//all measurements in single button
public function Allmeasurement(Request $request)
{
    try{

    $request->validate([
        'excelFileInputallmeas' => 'required|mimes:xls,xlsx|max:2048', // Add any validation rules you need
    ]);

    // Get the uploaded file
    $uploadeallexceldFile = $request->file('excelFileInputallmeas');
   $tbillid=$request->input('tbillid');
   //dd($uploadedFile);
//dd($tbillid);

   $previousPage = session()->get('previous_page');
// Append the route to the current URL
$redirectUrl = redirect()->route('emb.workmasterdata', ['id' => $tbillid, 'page' => $previousPage])->getTargetUrl();
//dd($redirectUrl);
    // Use the ExcelImport class to process the Excel file
    $excelImport = new ExcelImport();
$Alldata = $excelImport->Allmeasexcel($uploadeallexceldFile, $tbillid);

return (object)[
    'Alldata' => $Alldata,
    'redirectUrl' => $redirectUrl
];

 } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle model not found errors
            return response()->json(['error' => 'Model not found: ' . $e->getMessage()], 404);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
            // Handle file not found errors
            return response()->json(['error' => 'File not found: ' . $e->getMessage()], 404);
        } catch (\Exception $e) {
            // Handle all other exceptions
            Log::error('Error in Allmeasurement: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }


        }
// fetch modal data from emb table
// fetch modal data from emb table
public function fetchModalTableData(Request $request)
{
    $bitemId = $request->input('b_item_id');
    //dd( $bitemId);

    $tbillid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('t_bill_id');
    $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');


    $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
    //dd($itemid);
         if (
        in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])
            //in_array(substr($itemid, -6), ["001295", "001298", "002115", "003960", "003963", "004351", "003550", "003551", "002064", "002065", "002066", "002067", "002068", "002069", "003399", "003558", "004566", "004567"])
        ) {
            
            
            
            try{
                
            
        $stldata = DB::table('stlmeas')
     ->where('b_item_id', $bitemId)
     ->get();
  //dd($stldata);
    $bill_rc_data = DB::table('bill_rcc_mbr')->get();

   // dd($stldata , $bill_rc_data);

    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
      'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

      foreach ($stldata as &$data) {
        if (is_object($data)) {
            foreach ($ldiamColumns as $ldiamColumn) {
                if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                    $temp = $data->$ldiamColumn;
                    $data->$ldiamColumn = $data->bar_length;
                    $data->bar_length = $temp;
                   // dd($data->bar_length , $data->$ldiamColumn);
                    break; // Stop checking other ldiam columns if we found a match
                }
            }
        }
    }


    $sums = array_fill_keys($ldiamColumns, 0);

    foreach ($stldata as $row) {
        foreach ($ldiamColumns as $ldiamColumn) {
            $sums[$ldiamColumn] += $row->$ldiamColumn;
        }
    }//dd($stldata);
//dd($sums);

$bill_member = DB::table('bill_rcc_mbr')
   ->whereExists(function ($query) use ($bitemId) {
       $query->select(DB::raw(1))
             ->from('stlmeas')
             ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
             ->where('bill_rcc_mbr.b_item_id', $bitemId);
   })
   ->get();
//$bill_memberdata=DB::table('rcc_mbr')->get();
//dd($bill_member);
// Generate the HTML content

$rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();
//d($rc_mbr_ids);



$html = '';


//dd($stldata);
    // Check if there is data for this rc_mbr_id
    // if ($stldata->isEmpty()) {
    //     continue; // Skip if there's no data
    // }


    foreach ($bill_member as $index => $member) {
        $html .= '<div class="container-fluid" >';
        $html .= '
        <div class="container-fluid" >
    <div class="row">
        <div class="col-md-1">
            <div class="form-group">
                <label for="sr_no">Sr No</label>
                <input type="text" class="form-control" id="sr_no" name="sr_no" value="' . $member->member_sr_no . '" disabled>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="rcc_member">RCC Member</label>
                <select class="form-control" id="rcc_member" name="rcc_member" disabled>
                    <option value="' . $member->rc_mbr_id . '">' . $member->rcc_member . '</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="member_particular">Member Particular</label>
                <input type="text" class="form-control" id="member_particular" name="member_particular" value="' . $member->member_particulars . '" disabled>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                 <label for="no_of_members">No Of Members</label>
                 <input type="text" class="form-control" id="no_of_members" name="no_of_members" value="' . $member->no_of_members . '" disabled>
            </div>
       </div>
    </div>


    <div class="container-fluid">
      <div class="col-md-12">
            <table class="table table-striped">

                <thead>
                    <tr>
                    <th>Sr No</th>
                    <th>Bar Particulars</th>
                    <th>No of Bars</th>
                    <th>Length of Bars</th>
                    <th>6mm</th>
                    <th>8mm</th>
                    <th>10mm</th>
                    <th>12mm</th>
                    <th>16mm</th>
                    <th>20mm</th>
                    <th>25mm</th>
                    <th>28mm</th>
                    <th>32mm</th>
                    <th>36mm</th>
                    <th>40mm</th>
                    <th>Date</th>

                    </tr>
                </thead>
                <tbody>';

                foreach ($stldata as $bar) {
                    if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                    //dd($bar);// Assuming the bar data is within a property like "bar_data"
                    $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                    $html .= '<tr>
                                <td>'. $bar->bar_sr_no .'</td>
                                <td>'. $bar->bar_particulars .'</td>
                                <td>'. $bar->no_of_bars .'</td>
                                <td>'. $bar->bar_length .'</td>
                                <td>'. $bar->ldiam6 .'</td>
                                <td>'. $bar->ldiam8 .'</td>
                                <td>'. $bar->ldiam10 .'</td>
                                <td>'. $bar->ldiam12 .'</td>
                                <td>'. $bar->ldiam16 .'</td>
                                <td>'. $bar->ldiam20 .'</td>
                                <td>'. $bar->ldiam25 .'</td>
                                <td>'. $bar->ldiam28 .'</td>
                                <td>'. $bar->ldiam32 .'</td>
                                <td>'. $bar->ldiam36 .'</td>
                                <td>'. $bar->ldiam40 .'</td>
                                <td>'. $formattedDateMeas .'</td>
                                <td>
                            </td>
                                </tr>';
                }
            }

            $html .= '
                </tbody>
            </table>
        </div>
    </div>
    </div>';

    // Add a row for the totals for the last member
    if ($index === count($bill_member) - 1) {
        $html .= '
        <div><h4>TOTAL LENGTH</h4></div>
       <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                <thead>
                    <tr>
                    <th></th>
                    <th colspan="13"></th>
                    <th>Length</th>
                    <th>6mm</th>
                    <th>8mm</th>
                    <th>10mm</th>
                    <th>12mm</th>
                    <th>16mm</th>
                    <th>20mm</th>
                    <th>25mm</th>
                    <th>28mm</th>
                    <th>32mm</th>
                    <th>36mm</th>
                    <th>40mm</th>
                    <th colspan="8"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                        <th>Total</th>
                        <td>' . $sums['ldiam6'] . '</td>
                        <td>' . $sums['ldiam8'] . '</td>
                        <td>' . $sums['ldiam10'] . '</td>
                        <td>' . $sums['ldiam12'] . '</td>
                        <td>' . $sums['ldiam16'] . '</td>
                        <td>' . $sums['ldiam20'] . '</td>
                        <td>' . $sums['ldiam25'] . '</td>
                        <td>' . $sums['ldiam28'] . '</td>
                        <td>' . $sums['ldiam32'] . '</td>
                        <td>' . $sums['ldiam36'] . '</td>
                        <td>' . $sums['ldiam40'] . '</td>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </div>';
    }

    $html .= '</div>'; // Close the container
}
            // Check if this is the last member in the list

            if (in_array(substr($itemid, -6), ["003351", "003878"]))
            {
                 $sec_type="HCRM/CRS Bar";
            }
         else{
                 $sec_type="TMT Bar";
             }

             $selectedlength = [];
             $size=null;
             $sr_no = 0; // Initialize the serial number
             $totalweight = 0; // Initialize the total weight

             $html .= '<div><h4>TOTAL WEIGHT</h4></div> <div class="container-fluid">
      <div class="row">
          <div class="col-md-12">
                <table class="table table-striped" style="width: 100%;">
                  <thead>
                      <tr>
                          <th>Sr No</th>
                          <th>Particulars</th>
                          <th>Formula</th>
                          <th>Weight</th>
                      </tr>
                  </thead>
                  <tbody>';

                  $distinctStlDate = DB::table('stlmeas')
                  ->select('date_meas') // Add other columns as needed
                  ->where('b_item_id', $bitemId)
                  ->groupBy('date_meas')
                  ->orderBy('date_meas', 'asc') // Optional: Order by date in descending order
                  ->get();


                  DB::table('embs')->where('b_item_id', $bitemId)->delete();


                  $Size=null;
                 //dd($sums);
                  foreach($distinctStlDate as $date)
                 {
      // //dd($date);
      $barlenghtl6=0;
                  $barlenghtl8=0;
                  $barlenghtl10=0;
                  $barlenghtl12=0;
                  $barlenghtl16=0;
                  $barlenghtl20=0;
                  $barlenghtl25=0;
                  $barlenghtl28=0;
                  $barlenghtl32=0;
                  $barlenghtl36=0;
                  $barlenghtl40=0;
                  $barlenghtl45=0;
                                      $steelmeasdata=DB::table('stlmeas')->where('b_item_id', $bitemId)->where('date_meas', $date->date_meas)->get();

                                    //dd($steelmeasdata);

                                      foreach ($steelmeasdata as $row) {
      //dd($row);
                                        $measurement=DB::table('stlmeas')->select('ldiam6','ldiam8' , 'ldiam10' , 'ldiam12' , 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40', 'ldiam45')->where('steelid' , $row->steelid)->first();

                                        $keyValuePairs = (object)[];

                                        foreach ($measurement as $column => $value) {
                                            if (!is_null($value)) {
                                                $keyValuePairs->$column = $value;
                                            }
                                        }
                                        //dd(key($keyValuePairs));
                                      //   foreach ($row as $key => $value) {
                                      //     }
                                          //dd($row);
                                          switch (key($keyValuePairs)) {
                                              case 'ldiam6':
                                                  $Size = "6 mm dia";
                                                  $barlenghtl6 += $row->bar_length;
                                                  break;
                                              case 'ldiam8':
                                                  $Size = "8 mm dia";
                                                  $barlenghtl8 += $row->bar_length;
                                                  break;
                                              case 'ldiam10':
                                                  $Size = "10 mm dia";
                                                  $barlenghtl10 += $row->bar_length;
                                                  break;
                                              case 'ldiam12':
                                                  $Size = "12 mm dia";
                                                  $barlenghtl12 += $row->bar_length;
                                                  break;
                                              case 'ldiam16':
                                                  $Size = "16 mm dia";
                                                  $barlenghtl16 += $row->bar_length;
                                                  break;
                                              case 'ldiam20':
                                                  $Size = "20 mm dia";
                                                  $barlenghtl20 += $row->bar_length;
                                                  break;
                                              case 'ldiam25':
                                                  $Size = "25 mm dia";
                                                  $barlenghtl25 += $row->bar_length;
                                                  break;
                                              case 'ldiam28':
                                                  $Size = "28 mm dia";
                                                  $barlenghtl28 += $row->bar_length;
                                                  break;
                                              case 'ldiam32':
                                                  $Size = "32 mm dia";
                                                  $barlenghtl32 += $row->bar_length;
                                                  break;
                                              case 'ldiam36':
                                                  $Size = "36 mm dia";
                                                  $barlenghtl36 += $row->bar_length;
                                                  break;
                                              case 'ldiam40':
                                                  $Size = "40 mm dia";
                                                  $barlenghtl40 += $row->bar_length;
                                                  break;
                                              case 'ldiam45':
                                                  $Size = "45 mm dia";
                                                  $barlenghtl45 += $row->bar_length;
                                                  break;
                                          }
                                      }//dd($stldata);



                                     $excelimportclass = new ExcelImport();


                                      if($barlenghtl6 > 0)
                                      {
      
                                         $size="6 mm dia";
                                          
                                         $sr_no++;
                                         //function is created 
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl6 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
          //dd($tmtdata);           
                                                   
                                      }
      
      
      
      
      
                                  
                                 
                                      if($barlenghtl8 > 0)
                                      {
                                              $size="8 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl8 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
          $html .= $tmtdata['html']; // Accessing html
                             
                                                   
      
                                      }
                                   
                                      if($barlenghtl10 > 0)
                                      {
                                              $size="10 mm dia";
                                             
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl10 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                   
      
                                      }
                                      if($barlenghtl12 > 0)
                                      {
                                              $size="12 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl12 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
      
                                      }
                                      if($barlenghtl16 > 0)
                                      {
                                              $size="16 mm dia";
      
                                              $sr_no++;
                                               //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl16 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html                                                                          
      
                                      }
      
                                     
                                    
                                      if($barlenghtl20 > 0)
                                      {
                                              $size="20 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl20 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                      
                                      }
      
                                      if($barlenghtl25 > 0)
                                      {
                                              $size="25 mm dia";
      
                                              $sr_no++;
                                                //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl25 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                                        
                                      }
                                     
                                    
                                      if($barlenghtl28 > 0)
                                      {
                                              $size="28 mm dia";
      
                                              $sr_no++;
      
      
      
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl28 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                       
                      
                                      }
                                    
                                     
                                      if($barlenghtl32 > 0)
                                      {
                                              $size="32 mm dia";
      
                                              $sr_no++;
                                                  //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl32 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                       
                      
                                      }
                                    
                                     
                                     
                                      if($barlenghtl36 > 0)
                                      {
                                              $size="36 mm dia";
      
                                              $sr_no++;
                                                 //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl36 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                      
                                      }
      
      
                                      if($barlenghtl40 > 0)
                                      {
                                              $size="40 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl40 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                                       
                                      }
                                     // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];





                                  }

                $html .= '<tr style="background-color: #333; color: #fff;">
                      <td></td>
                      <td><strong>Total Weight:</strong></td>
                      <td></td>
                      <td><strong>' . $totalweight . ' M.T</strong></td>
                    </tr>';

                    $html .= '</tbody>
                       </table>
                   </div>
               </div>
           </div>';




            // $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');



       $previousTBillId = DB::table('bills')
       ->where('work_id' , $workid)
       ->where('t_bill_id', '<', $tbillid) // Add your condition here
       ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
       ->limit(1) // Limit the result to 1 row
       ->value('t_bill_id');
           //dd($previousTBillId);
           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

           $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


           $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
           //dd($previousexecqty);

           if (is_null($previousexecqty)) {
               $previousexecqty = 0;
           }

          $curqty = number_format(round($totalweight, $Qtydec), 3, '.', '');



$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
           //dd($execqty);
           //dd($execqty);




           //dd($execqty);


           //dd($execqty);

           $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                $curamt=$curqty*$billrt;

           $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

               $bitemamt=$curamt+$previousamt;

           DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

               'exec_qty' => $execqty,
               'cur_qty' => $curqty,
               'prv_bill_qty' => $previousexecqty,
               'cur_amt' => $curamt,
               'b_item_amt' => $bitemamt,
           ]);


           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
           $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
        //   $tndqty=round($tnditem->tnd_qty , 2);
        $tndqty=$tnditem->tnd_qty;
        
         $amountconvert=new CommonHelper();
                

           $tndcostitem=$tnditem->t_item_amt;
           //dd($tndqty);
           $percentage=round(($execqty / $tndqty)*100 , 2);
           //dd($percentage);
           $totlcostitem=round($billrt*$execqty , 2);

           $costdifference= round($tndcostitem-$totlcostitem , 2);

                 $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);

        // dd($$html);
                 // Check if this is the last member in the list
                 $parta = 0; // Initialize the sum for matched conditions
                 $partb = 0; // Initialize the sum for unmatched conditions

                 $cparta = 0; // Initialize the sum for matched conditions
                 $cpartb = 0; // Initialize the sum for unmatched conditions

                $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")
                        )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }


                 $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

                 $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
                 //dd($billgrossamt);
                 $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
                 //dd($beloaboperc);
                 $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

                 $bill_amt=0;
                $abeffect = $parta * ($beloaboperc / 100);
                $cabeffect = $cparta * ($beloaboperc / 100);

                               if ($beloAbo === 'Above') {


                                  $bill_amt = round(($parta + $abeffect), 2);
                                  $cbill_amt = round(($cparta + $cabeffect), 2);

                              } elseif ($beloAbo === 'Below') {

                                  $bill_amt = round(($parta - $abeffect), 2);
                                  $cbill_amt = round(($cparta - $cabeffect), 2);

                              }

                               // Determine whether to add a minus sign
                               if ($beloAbo === 'Below') {
                                   $abeffect = -$abeffect;
                                   $cabeffect = -$cabeffect;
                                   $beloaboperc = -$beloaboperc;
                                  }
                                  //dd($abeffect);
                                 //$part_a_ab=($parta * $beloaboperc / 100);
                                 //dd($partb);





                                 $Gstbase = round($bill_amt, 2);
                                 $cGstbase = round($cbill_amt, 2);
                                        //dd($Gstbase);

                                        $Gstamt= round($Gstbase*(18 / 100), 2);
                                        $cGstamt= round($cGstbase*(18 / 100), 2);
                                         //dd($Gstamt);

                                         $part_A_gstamt=$Gstbase + $Gstamt;
                                         $cpart_A_gstamt=$cGstbase + $cGstamt;


                                         $billamtgt = $partb + $part_A_gstamt;
                                         $cbillamtgt = $cpartb + $cpart_A_gstamt;

                           $integer_part = floor($billamtgt);  // Extract the integer part
                           $cinteger_part = floor($cbillamtgt);


                           $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                           $cdecimal_part = $cbillamtgt - $cinteger_part;
                           //dd($decimal_part);

                           $billamtro = round($decimal_part, 2);
                           $cbillamtro = round($cdecimal_part, 2);
                           //dd($rounded_decimal_part);

                      //     // Round the total bill amount
                      //     $billamtro = round($billamtgt);
                      //     //dd($rounded_billamtgt);

                      //    // Calculate the difference
                      //     //$billamtro = $rounded_billamtgt - $billamtgt;
                      //     dd($billamtro);
                          //$billamtro=0.37;
                          if ($billamtro > 0.50) {
                              // Calculate the absolute difference
                              $abs_diff = abs($billamtro);
                              $billamtro = 1 - $abs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $billamtro = -$billamtro;
                              //dd($billamtro);
                          }

                          if ($cbillamtro > 0.50) {
                              // Calculate the absolute difference
                              $cabs_diff = abs($cbillamtro);
                              $cbillamtro = 1 - $cabs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $cbillamtro = -$cbillamtro;
                              //dd($billamtro);
                          }
                           //dd($billamtro);

                           $net_amt= $billamtgt + $billamtro;
                           $cnet_amt= $cbillamtgt + $cbillamtro;
                           //dd($net_amt);

                            // Determine whether to add a minus sign


                           DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                              'part_a_amt' => $parta,
                              'part_a_gstamt' => $part_A_gstamt,
                              'part_b_amt' => $partb,
                              'gst_amt' => $Gstamt,
                              'gst_base' => $Gstbase,
                              'gross_amt' => $billgrossamt,
                              'a_b_effect' => $abeffect,
                              'bill_amt' => $bill_amt,
                              'bill_amt_gt' => $billamtgt,
                              'bill_amt_ro' => $billamtro,
                              'net_amt' => $net_amt,

                              'c_part_a_amt' => $cparta,
                              'c_part_a_gstamt' => $cpart_A_gstamt,
                              'c_part_b_amt' => $cpartb,
                              'curr_grossamt' => $cbillgrossamt,
                              'c_billamt' =>  $cbill_amt,
                              'c_gstamt' => $cGstamt,
                              'c_gstbase' => $cGstbase,
                              'c_abeffect' => $cabeffect,
                              'c_billamtgt' => $cbillamtgt,
                              'c_billamtro' => $cbillamtro,
                              'c_netamt' => $cnet_amt
                           ]);

                           $bitemdata=DB::table('bil_item')->where('b_item_id' , $bitemId)->get();
             //dd($bitemdata);



             $html .= '

             <div class="row mt-3">
                  <div class="col-md-3 offset-md-9">
                      <div class="form-group">
                          <label for="currentQty" class="font-weight-bold">Current Quantity:</label>
                          <input type="text" class="form-control" id="currentQty{{ $emb3->b_item_id }}" name="currentQty" value="' .$curqty. '" disabled>
                      </div>
                  </div>
              </div>


             <div class="row mt-3">
               <div class="col-md-3 offset-md-9">
                     <div class="form-group" >
                         <label for="previousQty" class="font-weight-bold">Previous bill Quantity:</label>
                         <input type="text" class="form-control" id="previousQty{{ $emb3->b_item_id }}" name="previousQty" value="' .$previousexecqty. '"  disabled>
                     </div>
                 </div>
             </div>



             <div class="row mt-3">
             <div class="col-md-3 offset-md-3">
                 <div class="form-group">
                     <label for="tndqty" class="font-weight-bold">Tender Quantity Of Item:</label>
                     <input type="text" class="form-control" id="tndqty{{ $emb3->b_item_id }}" name="tndqty" value="' .$tndqty. '" disabled>
                 </div>
             </div>
             <div class="col-md-3">
                 <div class="form-group">
                     <label for="percentage" class="font-weight-bold">Percentage Utilised:</label>
                     <input type="text" class="form-control" id="percentage{{ $emb3->b_item_id }}" name="percentage" value="' .$percentage. '" disabled>
                 </div>
             </div>
             <div class="col-md-3">
                 <div class="form-group">
                     <label for="totalQty" class="font-weight-bold">Total Uptodate Quantity:</label>
                     <input type="text" class="form-control" id="totalQty{{ $emb3->b_item_id }}" name="totalQty" value="' .$execqty. '" disabled>
                 </div>
             </div>
         </div>

                <div class="row mt-3"  >
                <div class="col-md-3 offset-md-3">
                    <div class="form-group">
                      <label for="tndcost" class="font-weight-bold">Tender Cost Of Item:</label>
                      <input type="text" class="form-control" id="tndcost{{ $emb3->b_item_id }}" name="tndcost" value="' .$tndcostitem. '" disabled>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="costdifference" class="font-weight-bold">Excess/Saving:</label>
                      <input type="text" class="form-control" id="costdifference{{ $emb3->b_item_id }}" name="costdifference" value="' .$costdifference. '" disabled>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="totalcost" class="font-weight-bold">Total Uptodate Amount:</label>
                      <input type="text" class="form-control" id="totalcost{{ $emb3->b_item_id }}" name="totalcost" value="' .$totlcostitem. '" disabled>
                    </div>
                  </div>
                </div>';


                $billtemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

                           $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();
                           
                            $convert=new CommonHelper();

                           $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
                           $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
                           '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
                           '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
                           '</div></div>';

                  return response()->json(['html' => $html , 'bitemId' => $bitemId , 'bitemdata' => $bitemdata ,'workdetail' => $workdetail]);
  
  
            } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage(), 'line' => $e->getLine()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle model not found errors
            return response()->json(['error' => 'Model not found: ' . $e->getMessage(), 'line' => $e->getLine()], 404);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
            // Handle file not found errors
            return response()->json(['error' => 'File not found: ' . $e->getMessage(), 'line' => $e->getLine()], 404);
        } catch (\Exception $e) {
            // Handle all other exceptions
            Log::error('Error in Allmeasurement: ' . $e->getMessage() . ' at line ' . $e->getLine());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage(), 'line' => $e->getLine()], 500);
        }

        }

        else
        {
            
            
            try{
                
            
    // Perform the database query using the b_item_id
    $modalData = DB::table('bil_item')
        ->join('bills', 'bills.t_bill_Id', '=', 'bil_item.t_bill_Id')
        ->join('embs', 'embs.b_item_id', '=', 'bil_item.b_item_id')
        ->where('bil_item.b_item_id', '=', $bitemId)
        ->select('embs.*')
        ->get();
  //dd($modalData);

if ($modalData->count() > 0) {
    // Prepare arrays to store URLs
    $image1Urls = [];
    $image2Urls = [];
    $image3Urls = [];
    $documentUrls = [];


    $previousTBillId = DB::table('bills')
    ->where('work_id' , $workid)
    ->where('t_bill_id', '<', $tbillid) // Add your condition here
    ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
    ->limit(1) // Limit the result to 1 row
    ->value('t_bill_id');
        //dd($previousTBillId);
        $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

        $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


        $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
       // dd($previousexecqty);

        if (is_null($previousexecqty)) {
            $previousexecqty = 0;
        }


$curqty = number_format(round(DB::table('embs')->where('b_item_id', $bitemId)->where('notforpayment', '=', 0)->sum('qty'), $Qtydec), 3, '.', '');
        //dd($previousexecqty);
        //dd($curqty);



$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
        //dd($execqty);
        //dd($totalqty);

        $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

        $curamt=$curqty*$billrt;

   $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

       $bitemamt=$curamt+$previousamt;

   DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

       'exec_qty' => $execqty,
       'cur_qty' => $curqty,
       'prv_bill_qty' => $previousexecqty,
       'cur_amt' => $curamt,
       'b_item_amt' => $bitemamt,
   ]);


        $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
        $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
       // dd($titemid);
        // $tndqty=round($tnditem->tnd_qty , 3);
        //dd($titemid);
        $tndqty=$tnditem->tnd_qty;
        
        
         $amountconvert=new CommonHelper();
                

        $tndcostitem=$tnditem->t_item_amt;
        //dd($tndqty);
        $percentage=round(($execqty / $tndqty)*100 , 2);
        //dd($percentage);
        $totlcostitem=round($billrt*$execqty , 2);

        $costdifference= round($tndcostitem-$totlcostitem , 2);
        
    
       $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
        $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
        $costdifference=$amountconvert->formatIndianRupees($costdifference);
        

        $parta = 0; // Initialize the sum for matched conditions
        $partb = 0; // Initialize the sum for unmatched conditions

        $cparta = 0; // Initialize the sum for matched conditions
        $cpartb = 0; // Initialize the sum for unmatched conditions

      $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                                     )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }


        $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

        $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
        //dd($billgrossamt);
        $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
        //dd($beloaboperc);
        $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

        $bill_amt=0;
       $abeffect = $parta * ($beloaboperc / 100);
       $cabeffect = $cparta * ($beloaboperc / 100);

                      if ($beloAbo === 'Above') {


                         $bill_amt = round(($parta + $abeffect), 2);
                         $cbill_amt = round(($cparta + $cabeffect), 2);

                     } elseif ($beloAbo === 'Below') {

                         $bill_amt = round(($parta - $abeffect), 2);
                         $cbill_amt = round(($cparta - $cabeffect), 2);

                     }

                      // Determine whether to add a minus sign
                      if ($beloAbo === 'Below') {
                          $abeffect = -$abeffect;
                          $cabeffect = -$cabeffect;
                          $beloaboperc = -$beloaboperc;
                         }
                         //dd($abeffect);
                        //$part_a_ab=($parta * $beloaboperc / 100);
                        //dd($partb);





                        $Gstbase = round($bill_amt, 2);
                        $cGstbase = round($cbill_amt, 2);
                               //dd($Gstbase);

                               $Gstamt= round($Gstbase*(18 / 100), 2);
                               $cGstamt= round($cGstbase*(18 / 100), 2);
                                //dd($Gstamt);

                                $part_A_gstamt=$Gstbase + $Gstamt;
                                $cpart_A_gstamt=$cGstbase + $cGstamt;


                                $billamtgt = $partb + $part_A_gstamt;
                                $cbillamtgt = $cpartb + $cpart_A_gstamt;

                  $integer_part = floor($billamtgt);  // Extract the integer part
                  $cinteger_part = floor($cbillamtgt);


                  $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                  $cdecimal_part = $cbillamtgt - $cinteger_part;
                  //dd($decimal_part);

                  $billamtro = round($decimal_part, 2);
                  $cbillamtro = round($cdecimal_part, 2);
                  //dd($rounded_decimal_part);

             //     // Round the total bill amount
             //     $billamtro = round($billamtgt);
             //     //dd($rounded_billamtgt);

             //    // Calculate the difference
             //     //$billamtro = $rounded_billamtgt - $billamtgt;
             //     dd($billamtro);
                 //$billamtro=0.37;
                 if ($billamtro > 0.50) {
                     // Calculate the absolute difference
                     $abs_diff = abs($billamtro);
                     $billamtro = 1 - $abs_diff;
                     //dd($billamtro);
                 }
                 else {
                     // If it is, add a minus sign to the difference
                     $billamtro = -$billamtro;
                     //dd($billamtro);
                 }

                 if ($cbillamtro > 0.50) {
                     // Calculate the absolute difference
                     $cabs_diff = abs($cbillamtro);
                     $cbillamtro = 1 - $cabs_diff;
                     //dd($billamtro);
                 }
                 else {
                     // If it is, add a minus sign to the difference
                     $cbillamtro = -$cbillamtro;
                     //dd($billamtro);
                 }
                  //dd($billamtro);

                  $net_amt= $billamtgt + $billamtro;
                  $cnet_amt= $cbillamtgt + $cbillamtro;
                  //dd($net_amt);

                   // Determine whether to add a minus sign


                  DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                     'part_a_amt' => $parta,
                     'part_a_gstamt' => $part_A_gstamt,
                     'part_b_amt' => $partb,
                     'gst_amt' => $Gstamt,
                     'gst_base' => $Gstbase,
                     'gross_amt' => $billgrossamt,
                     'a_b_effect' => $abeffect,
                     'bill_amt' => $bill_amt,
                     'bill_amt_gt' => $billamtgt,
                     'bill_amt_ro' => $billamtro,
                     'net_amt' => $net_amt,

                     'c_part_a_amt' => $cparta,
                     'c_part_a_gstamt' => $cpart_A_gstamt,
                     'c_part_b_amt' => $cpartb,
                     'curr_grossamt' => $cbillgrossamt,
                     'c_billamt' =>  $cbill_amt,
                     'c_gstamt' => $cGstamt,
                     'c_gstbase' => $cGstbase,
                     'c_abeffect' => $cabeffect,
                     'c_billamtgt' => $cbillamtgt,
                     'c_billamtro' => $cbillamtro,
                     'c_netamt' => $cnet_amt
                  ]);

             $bitemdata=DB::table('bil_item')->where('b_item_id' , $bitemId)->get();
            // dd($bitemdata);


            $billtemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

            $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();
            
            $convert=new CommonHelper();

            $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
            $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
            '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
            '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
            '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
            '<strong>Work Order Amount:</strong> ' .  $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
            '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
            '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
            '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
            '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .

            '</div></div>';
            //dd($bitemId);
                return response()->json([
                    'modalData' => $modalData,
                    'bitemid' => $bitemId,
                    'bitemdata' => $bitemdata ,
                    'previousexecqty' => $previousexecqty , 'curqty' => $curqty , 'execqty' => $execqty ,'tndqty' => $tndqty , 'tndcostitem' => $tndcostitem ,
                     'percentage' => $percentage , 'totlcostitem' => $totlcostitem , 'costdifference' => $costdifference , 'workdetail' => $workdetail,
                ]);
            } else {
                return response()->json(['error' => 'No data found for the given b_item_id'], 404);
            }


            } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage(), 'line' => $e->getLine()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle model not found errors
            return response()->json(['error' => 'Model not found: ' . $e->getMessage(), 'line' => $e->getLine()], 404);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
            // Handle file not found errors
            return response()->json(['error' => 'File not found: ' . $e->getMessage(), 'line' => $e->getLine()], 404);
        } catch (\Exception $e) {
            // Handle all other exceptions
            Log::error('Error in Allmeasurement: ' . $e->getMessage() . ' at line ' . $e->getLine());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage(), 'line' => $e->getLine()], 500);
        }

        }


}


    // fetch item description from bil_item to modal emb table
    public function fetchItemDesc(Request $request)
    {
        try{
            
            
        $bItemId = $request->input('b_item_id');
    //
    //dd($bItemId);
        // Fetch the item description from the bil_item table based on b_item_id
        $itemdata = DB::table('bil_item')->where('b_item_id', $bItemId)->get();

      //dd($itemdata);
        return response()->json(['itemdata' => $itemdata , 'bItemId' => $bItemId]);
        
        }catch(\Exception $e)
        {
            Log::error('Error Occurr during view  item description' .$e->getMessage());

            return response()->json(['error' => 'Error Occurr during the' .$e->getMessage()] , 500);
        }
    }


//fetch data for edit emb button
      public function fetchembdataedit(Request $request)
    {
        $bitemId = $request->input('b_item_id');
        //dd($bitemId);

        $tbillid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_bill_id');
        $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
        $html = '';
        $htmlnormal='';
        $itemResponse=null;
        $itemdata=null;
        $measid=null;
        $totalweight = 0;
         $index=null;
        $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
        //dd($itemid);
             if (
            in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])
                //in_array(substr($itemid, -6), ["001295", "001298", "002115", "003960", "003963", "004351", "003550", "003551", "002064", "002065", "002066", "002067", "002068", "002069", "003399", "003558", "004566", "004567"])
            ) {
                
                try{
                    
                
                $stldata = DB::table('stlmeas')
                ->where('b_item_id', $bitemId)
                ->get();
             //dd($stldata);
               $bill_rc_data = DB::table('bill_rcc_mbr')->get();

              // dd($stldata , $bill_rc_data);

               $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
                 'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

                 foreach ($stldata as &$data) {
                   if (is_object($data)) {
                       foreach ($ldiamColumns as $ldiamColumn) {
                           if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                               $temp = $data->$ldiamColumn;
                               $data->$ldiamColumn = $data->bar_length;
                               $data->bar_length = $temp;
                              // dd($data->bar_length , $data->$ldiamColumn);
                               break; // Stop checking other ldiam columns if we found a match
                           }
                       }
                   }
               }


               $sums = array_fill_keys($ldiamColumns, 0);

               foreach ($stldata as $row) {
                   foreach ($ldiamColumns as $ldiamColumn) {
                       $sums[$ldiamColumn] += $row->$ldiamColumn;
                   }
               }//dd($stldata);
           //dd($sums);


           $bill_member = DB::table('bill_rcc_mbr')
           ->whereExists(function ($query) use ($bitemId) {
               $query->select(DB::raw(1))
                     ->from('stlmeas')
                     ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                     ->where('bill_rcc_mbr.b_item_id', $bitemId);
           })
           ->get();
           //$bill_memberdata=DB::table('rcc_mbr')->get();
           //dd($bill_member);
           // Generate the HTML content

           $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();
           //d($rc_mbr_ids);






           //dd($stldata);
               // Check if there is data for this rc_mbr_id
               // if ($stldata->isEmpty()) {
               //     continue; // Skip if there's no data
               // }

               if (!empty($bill_member)) {

               foreach ($bill_member as $index => $member) {
                   $html .= '<div class="container-fluid">';
                   $html .= '
             <div class="container-fluid">
               <div class="row">
                   <div class="col-md-1">
                       <div class="form-group">
                           <label for="sr_no">Sr No</label>
                           <input type="text" class="form-control" id="sr_no" name="sr_no" value="' . $member->member_sr_no . '" disabled>
                       </div>
                   </div>
                   <div class="col-md-4">
                       <div class="form-group">
                           <label for="rcc_member">RCC Member</label>
                           <select class="form-control" id="rcc_member" name="rcc_member" disabled>
                               <option value="' . $member->rc_mbr_id . '">' . $member->rcc_member . '</option>
                           </select>
                       </div>
                   </div>
                   <div class="col-md-4">
                       <div class="form-group">
                           <label for="member_particular">Member Particular</label>
                           <input type="text" class="form-control" id="member_particular" name="member_particular" value="' . $member->member_particulars . '" disabled>
                       </div>
                   </div>
                   <div class="col-md-2">
                       <div class="form-group">
                            <label for="no_of_members">No Of Members</label>
                            <input type="text" class="form-control" id="no_of_members" name="no_of_members" value="' . $member->no_of_members . '" disabled>
                       </div>
                  </div>
                  <div class="col-md-1">
                       <div class="form-group">
                          <button type="button" class="btn btn-primary btn-sm editrcmbr-button" data-rcbillid="' . $member->rc_mbr_id . '" id="editrccmbr '.$bitemId . '" title="EDIT RCC MEMBER"><i class="fa fa-pencil" aria-hidden="true"></i></button>
                       </div>
                  </div>
               </div>
             </div>

               <div class="container-fluid" >
                 <div class="col-md-12">
                       <table class="table table-striped">

                           <thead>
                               <tr>
                               <th>Sr No</th>
                               <th>Bar Particulars</th>
                               <th>No of Bars</th>
                               <th>Length of Bars</th>
                               <th>6mm</th>
                               <th>8mm</th>
                               <th>10mm</th>
                               <th>12mm</th>
                               <th>16mm</th>
                               <th>20mm</th>
                               <th>25mm</th>
                               <th>28mm</th>
                               <th>32mm</th>
                               <th>36mm</th>
                               <th>40mm</th>
                               <th>Date</th>
                               <th>Action</th>
                               </tr>
                           </thead>
                           <tbody>';

                           foreach ($stldata as $bar) {
                               if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                               //dd($bar);// Assuming the bar data is within a property like "bar_data"
                               $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                               $html .= '<tr>
                                           <td>'. $bar->bar_sr_no .'</td>
                                           <td>'. $bar->bar_particulars .'</td>
                                           <td>'. $bar->no_of_bars .'</td>
                                           <td>'. $bar->bar_length .'</td>
                                           <td>'. $bar->ldiam6 .'</td>
                                           <td>'. $bar->ldiam8 .'</td>
                                           <td>'. $bar->ldiam10 .'</td>
                                           <td>'. $bar->ldiam12 .'</td>
                                           <td>'. $bar->ldiam16 .'</td>
                                           <td>'. $bar->ldiam20 .'</td>
                                           <td>'. $bar->ldiam25 .'</td>
                                           <td>'. $bar->ldiam28 .'</td>
                                           <td>'. $bar->ldiam32 .'</td>
                                           <td>'. $bar->ldiam36 .'</td>
                                           <td>'. $bar->ldiam40 .'</td>
                                           <td>'. $formattedDateMeas .'</td>
                                           <td>
                                           <button type="button" class="btn btn-primary btn-sm edit-button" data-steelid="' . $bar->steelid . '" title="EDIT STEEL MEASUREMENT"> <i class="fa fa-pencil" style="color:white;"></i></button>
                                           <button type="button" class="btn btn-danger btn-sm delete-button" data-steelid="' . $bar->steelid . '" title="DELETE STEEL MEASUREMENT"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                       </td>
                                           </tr>';
                           }
                       }

                       $html .= '
                           </tbody>
                       </table>
                   </div>
               </div>';
//dd($index);
               // Add a row for the totals for the last member
               if ($index === count($bill_member) - 1) {
                   $html .= '
                   <div><h4>TOTAL LENGTH</h4></div>
                  <div class="container-fluid">
                   <div class="row">
                       <div class="col-md-12">
                           <table class="table table-striped">
                           <thead>
                               <tr>
                               <th></th>
                               <th colspan="13"></th>
                               <th>Length</th>
                               <th>6mm</th>
                               <th>8mm</th>
                               <th>10mm</th>
                               <th>12mm</th>
                               <th>16mm</th>
                               <th>20mm</th>
                               <th>25mm</th>
                               <th>28mm</th>
                               <th>32mm</th>
                               <th>36mm</th>
                               <th>40mm</th>
                               <th colspan="8"></th>
                               </tr>
                           </thead>
                           <tbody>
                               <tr>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                               <th></th>
                                   <th>Total</th>
                                   <td>' . $sums['ldiam6'] . '</td>
                                   <td>' . $sums['ldiam8'] . '</td>
                                   <td>' . $sums['ldiam10'] . '</td>
                                   <td>' . $sums['ldiam12'] . '</td>
                                   <td>' . $sums['ldiam16'] . '</td>
                                   <td>' . $sums['ldiam20'] . '</td>
                                   <td>' . $sums['ldiam25'] . '</td>
                                   <td>' . $sums['ldiam28'] . '</td>
                                   <td>' . $sums['ldiam32'] . '</td>
                                   <td>' . $sums['ldiam36'] . '</td>
                                   <td>' . $sums['ldiam40'] . '</td>
                                   <th></th>
                                   <th></th>
                                   <th></th>
                                   <th></th>
                                   <th></th>
                                   <th></th>
                                   <th></th>
                                   <th></th>
                               </tr>
                               </tbody>
                           </table>
                       </div>
                   </div>
                   </div>';
               }

               $html .= '</div>'; // Close the container

            }
          }

          else{

          }

//dd($index);

        //   if (!empty($index))
        //   {
           if (in_array(substr($itemid, -6), ["003351", "003878"]))
                  {
                       $sec_type="HCRM/CRS Bar";
                  }
               else{
                       $sec_type="TMT Bar";
                   }

                   $selectedlength = [];
                   $size=null;
                   $sr_no = 0; // Initialize the serial number
                   // Initialize the total weight
   //dd($stldata);
           if ($index === count($bill_member) - 1)
                   {
                   $html .= ' <div><h4>TOTAL WEIGHT</h4></div> <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                      <table class="table table-striped" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Sr No</th>
                                <th>Particulars</th>
                                <th>Formula</th>
                                <th>Weight</th>
                            </tr>
                        </thead>
                        <tbody>';

$distinctStlDate = DB::table('stlmeas')
            ->select('date_meas') // Add other columns as needed
            ->where('b_item_id', $bitemId)
            ->groupBy('date_meas')
            ->orderBy('date_meas', 'asc') // Optional: Order by date in descending order
            ->get();


            DB::table('embs')->where('b_item_id', $bitemId)->delete();


            $Size=null;
           //dd($sums);
            foreach($distinctStlDate as $date)
           {
// //dd($date);
$barlenghtl6=0;
            $barlenghtl8=0;
            $barlenghtl10=0;
            $barlenghtl12=0;
            $barlenghtl16=0;
            $barlenghtl20=0;
            $barlenghtl25=0;
            $barlenghtl28=0;
            $barlenghtl32=0;
            $barlenghtl36=0;
            $barlenghtl40=0;
            $barlenghtl45=0;
                                $steelmeasdata=DB::table('stlmeas')->where('b_item_id', $bitemId)->where('date_meas', $date->date_meas)->get();

                              //dd($steelmeasdata);

                                foreach ($steelmeasdata as $row) {
//dd($row);
                                  $measurement=DB::table('stlmeas')->select('ldiam6','ldiam8' , 'ldiam10' , 'ldiam12' , 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40', 'ldiam45')->where('steelid' , $row->steelid)->first();

                                  $keyValuePairs = (object)[];

                                  foreach ($measurement as $column => $value) {
                                      if (!is_null($value)) {
                                          $keyValuePairs->$column = $value;
                                      }
                                  }
                                  //dd(key($keyValuePairs));
                                //   foreach ($row as $key => $value) {
                                //     }
                                    //dd($row);
                                    switch (key($keyValuePairs)) {
                                        case 'ldiam6':
                                            $Size = "6 mm dia";
                                            $barlenghtl6 += $row->bar_length;
                                            break;
                                        case 'ldiam8':
                                            $Size = "8 mm dia";
                                            $barlenghtl8 += $row->bar_length;
                                            break;
                                        case 'ldiam10':
                                            $Size = "10 mm dia";
                                            $barlenghtl10 += $row->bar_length;
                                            break;
                                        case 'ldiam12':
                                            $Size = "12 mm dia";
                                            $barlenghtl12 += $row->bar_length;
                                            break;
                                        case 'ldiam16':
                                            $Size = "16 mm dia";
                                            $barlenghtl16 += $row->bar_length;
                                            break;
                                        case 'ldiam20':
                                            $Size = "20 mm dia";
                                            $barlenghtl20 += $row->bar_length;
                                            break;
                                        case 'ldiam25':
                                            $Size = "25 mm dia";
                                            $barlenghtl25 += $row->bar_length;
                                            break;
                                        case 'ldiam28':
                                            $Size = "28 mm dia";
                                            $barlenghtl28 += $row->bar_length;
                                            break;
                                        case 'ldiam32':
                                            $Size = "32 mm dia";
                                            $barlenghtl32 += $row->bar_length;
                                            break;
                                        case 'ldiam36':
                                            $Size = "36 mm dia";
                                            $barlenghtl36 += $row->bar_length;
                                            break;
                                        case 'ldiam40':
                                            $Size = "40 mm dia";
                                            $barlenghtl40 += $row->bar_length;
                                            break;
                                        case 'ldiam45':
                                            $Size = "45 mm dia";
                                            $barlenghtl45 += $row->bar_length;
                                            break;
                                    }
                                }//dd($stldata);



                                                              $excelimportclass = new ExcelImport();


                                if($barlenghtl6 > 0)
                                {

                                   $size="6 mm dia";
                                    
                                   $sr_no++;
                                   //function is created 
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl6 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
    //dd($tmtdata);           
                                             
                                }





                            
                           
                                if($barlenghtl8 > 0)
                                {
                                        $size="8 mm dia";

                                        $sr_no++;
                                        //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl8 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
    $html .= $tmtdata['html']; // Accessing html
                       
                                             

                                }
                             
                                if($barlenghtl10 > 0)
                                {
                                        $size="10 mm dia";
                                       
                                        $sr_no++;
                                        //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl10 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
                                             

                                }
                                if($barlenghtl12 > 0)
                                {
                                        $size="12 mm dia";

                                        $sr_no++;
                                        //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl12 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html

                                }
                                if($barlenghtl16 > 0)
                                {
                                        $size="16 mm dia";

                                        $sr_no++;
                                         //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl16 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html                                                                          

                                }

                               
                              
                                if($barlenghtl20 > 0)
                                {
                                        $size="20 mm dia";

                                        $sr_no++;
                                        //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl20 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
                
                                }

                                if($barlenghtl25 > 0)
                                {
                                        $size="25 mm dia";

                                        $sr_no++;
                                          //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl25 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
                                                                  
                                }
                               
                              
                                if($barlenghtl28 > 0)
                                {
                                        $size="28 mm dia";

                                        $sr_no++;



                                        //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl28 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
                                                 
                
                                }
                              
                               
                                if($barlenghtl32 > 0)
                                {
                                        $size="32 mm dia";

                                        $sr_no++;
                                            //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl32 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
                                                 
                
                                }
                              
                               
                               
                                if($barlenghtl36 > 0)
                                {
                                        $size="36 mm dia";

                                        $sr_no++;
                                           //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl36 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
                
                                }


                                if($barlenghtl40 > 0)
                                {
                                        $size="40 mm dia";

                                        $sr_no++;
                                        //function call for the total weight and emb table in that insert steel data
                                $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl40 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
                                                                 
                                }
                               // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];

                               // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];
                               // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];
                                //dd($totalweight);
 
                               // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];






                            }

                          // dd($html); 
                $html .= '<tr style="background-color: #333; color: #fff;">
                            <td></td>
                            <td><strong>Total Weight:</strong></td>
                            <td></td>
                            <td><strong>' . $totalweight . ' M.T</strong></td>
                          </tr>';

                          $html .= '</tbody>
                       </table>
                   </div>
               </div>
           </div>';



                

              // dd($$html);
                       // Check if this is the last member in the list
                    } else {
                        // Handle the case when $bill_member is empty

                    }

                    $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

       $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

       $previousTBillId = DB::table('bills')
       ->where('work_id' , $workid)
       ->where('t_bill_id', '<', $tbillid) // Add your condition here
       ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
       ->limit(1) // Limit the result to 1 row
       ->value('t_bill_id');
           //dd($previousTBillId);

           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

           $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


           $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
          // dd($previousexecqty);

           if (is_null($previousexecqty)) {
               $previousexecqty = 0;
           }

$curqty = number_format(round($totalweight, $Qtydec), 3, '.', '');
//dd($curqty);

$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
           //dd($execqty);

           $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                $curamt=$curqty*$billrt;

           $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

               $bitemamt=$curamt+$previousamt;

           DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

               'exec_qty' => $execqty,
               'cur_qty' => $curqty,
               'prv_bill_qty' => $previousexecqty,
               'cur_amt' => $curamt,
               'b_item_amt' => $bitemamt,
           ]);

           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
           $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
        //   $tndqty=round($tnditem->tnd_qty , 3);
        $tndqty=$tnditem->tnd_qty;
        
         $amountconvert=new CommonHelper();
                

           $tndcostitem=$tnditem->t_item_amt;
           //dd($tndqty);
           $percentage=round(($execqty / $tndqty)*100 , 2);
           //dd($percentage);
           $totlcostitem=round($billrt*$execqty , 2);

           $costdifference= round($tndcostitem-$totlcostitem , 2);


                 $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);


           $parta = 0; // Initialize the sum for matched conditions
           $partb = 0; // Initialize the sum for unmatched conditions

           $cparta = 0; // Initialize the sum for matched conditions
           $cpartb = 0; // Initialize the sum for unmatched conditions

         $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                         )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }



           $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

           $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
           //dd($billgrossamt);
           $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
           //dd($beloaboperc);
           $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

           $bill_amt=0;
          $abeffect = $parta * ($beloaboperc / 100);
          $cabeffect = $cparta * ($beloaboperc / 100);

                         if ($beloAbo === 'Above') {


                            $bill_amt = round(($parta + $abeffect), 2);
                            $cbill_amt = round(($cparta + $cabeffect), 2);

                        } elseif ($beloAbo === 'Below') {

                            $bill_amt = round(($parta - $abeffect), 2);
                            $cbill_amt = round(($cparta - $cabeffect), 2);

                        }

                         // Determine whether to add a minus sign
                         if ($beloAbo === 'Below') {
                             $abeffect = -$abeffect;
                             $cabeffect = -$cabeffect;
                             $beloaboperc = -$beloaboperc;
                            }
                            //dd($abeffect);
                           //$part_a_ab=($parta * $beloaboperc / 100);
                           //dd($partb);





                           $Gstbase = round($bill_amt, 2);
                           $cGstbase = round($cbill_amt, 2);
                          // dd($Gstbase);

                                  $Gstamt= round($Gstbase*(18 / 100), 2);
                                  $cGstamt= round($cGstbase*(18 / 100), 2);
                                   //dd($Gstamt);

                                   $part_A_gstamt=$Gstbase + $Gstamt;
                                   $cpart_A_gstamt=$cGstbase + $cGstamt;


                                   $billamtgt = $partb + $part_A_gstamt;
                                   $cbillamtgt = $cpartb + $cpart_A_gstamt;

                     $integer_part = floor($billamtgt);  // Extract the integer part
                     $cinteger_part = floor($cbillamtgt);


                     $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                     $cdecimal_part = $cbillamtgt - $cinteger_part;
                     //dd($decimal_part);

                     $billamtro = round($decimal_part, 2);
                     $cbillamtro = round($cdecimal_part, 2);
                     //dd($rounded_decimal_part);

                //     // Round the total bill amount
                //     $billamtro = round($billamtgt);
                //     //dd($rounded_billamtgt);

                //    // Calculate the difference
                //     //$billamtro = $rounded_billamtgt - $billamtgt;
                //     dd($billamtro);
                    //$billamtro=0.37;
                    if ($billamtro > 0.50) {
                        // Calculate the absolute difference
                        $abs_diff = abs($billamtro);
                        $billamtro = 1 - $abs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $billamtro = -$billamtro;
                        //dd($billamtro);
                    }

                    if ($cbillamtro > 0.50) {
                        // Calculate the absolute difference
                        $cabs_diff = abs($cbillamtro);
                        $cbillamtro = 1 - $cabs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $cbillamtro = -$cbillamtro;
                        //dd($billamtro);
                    }
                     //dd($billamtro);

                     $net_amt= $billamtgt + $billamtro;
                     $cnet_amt= $cbillamtgt + $cbillamtro;
                     //dd($net_amt);

                      // Determine whether to add a minus sign


                     DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                        'part_a_amt' => $parta,
                        'part_a_gstamt' => $part_A_gstamt,
                        'part_b_amt' => $partb,
                        'gst_amt' => $Gstamt,
                        'gst_base' => $Gstbase,
                        'gross_amt' => $billgrossamt,
                        'a_b_effect' => $abeffect,
                        'bill_amt' => $bill_amt,
                        'bill_amt_gt' => $billamtgt,
                        'bill_amt_ro' => $billamtro,
                        'net_amt' => $net_amt,

                        'c_part_a_amt' => $cparta,
                        'c_part_a_gstamt' => $cpart_A_gstamt,
                        'c_part_b_amt' => $cpartb,
                        'curr_grossamt' => $cbillgrossamt,
                        'c_billamt' =>  $cbill_amt,
                        'c_gstamt' => $cGstamt,
                        'c_gstbase' => $cGstbase,
                        'c_abeffect' => $cabeffect,
                        'c_billamtgt' => $cbillamtgt,
                        'c_billamtro' => $cbillamtro,
                        'c_netamt' => $cnet_amt
                     ]);


                      if ($index === count($bill_member) - 1)
                     {

                     $html .= '

                     

                <div class="row mt-3">
                     <div class="col-md-3 offset-md-9">
                         <div class="form-group">
                             <label for="currentQty" class="font-weight-bold">Current Quantity:</label>
                             <input type="text" class="form-control" id="currentQty{{ $emb3->b_item_id }}" name="currentQty" value="' .$curqty. '" disabled style="text-align:right">
                         </div>
                     </div>
                 </div>


                <div class="row mt-3">
                  <div class="col-md-3 offset-md-9">
                        <div class="form-group" >
                            <label for="previousQty" class="font-weight-bold">Previous bill Quantity:</label>
                            <input type="text" class="form-control" id="previousQty{{ $emb3->b_item_id }}" name="previousQty" value="' .$previousexecqty. '"  disabled style="text-align:right">
                        </div>
                    </div>
                </div>



                <div class="row mt-3">
                <div class="col-md-3 offset-md-3">
                    <div class="form-group">
                        <label for="tndqty" class="font-weight-bold">Tender Quantity Of Item:</label>
                        <input type="text" class="form-control" id="tndqty{{ $emb3->b_item_id }}" name="tndqty" value="' .$tndqty. '" disabled style="text-align:right">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="percentage" class="font-weight-bold">Percentage Utilised:</label>
                        <input type="text" class="form-control" id="percentage{{ $emb3->b_item_id }}" name="percentage" value="' .$percentage. '" disabled style="text-align:right">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="totalQty" class="font-weight-bold">Total Uptodate Quantity:</label>
                        <input type="text" class="form-control" id="totalQty{{ $emb3->b_item_id }}" name="totalQty" value="' .$execqty. '" disabled style="text-align:right">
                    </div>
                </div>
            </div>

                   <div class="row mt-3"  >
                   <div class="col-md-3 offset-md-3">
                       <div class="form-group">
                         <label for="tndcost" class="font-weight-bold">Tender Cost Of Item:</label>
                         <input type="text" class="form-control" id="tndcost{{ $emb3->b_item_id }}" name="tndcost" value="' .$tndcostitem. '" disabled style="text-align:right">
                       </div>
                     </div>
                     <div class="col-md-3">
                       <div class="form-group">
                         <label for="costdifference" class="font-weight-bold">Excess/Saving:</label>
                         <input type="text" class="form-control" id="costdifference{{ $emb3->b_item_id }}" name="costdifference" value="' .$costdifference. '" disabled style="text-align:right">
                       </div>
                     </div>
                     <div class="col-md-3">
                       <div class="form-group">
                         <label for="totalcost" class="font-weight-bold">Total Uptodate Amount:</label>
                         <input type="text" class="form-control" id="totalcost{{ $emb3->b_item_id }}" name="totalcost" value="' .$totlcostitem. '" disabled style="text-align:right">
                       </div>
                     </div>
                   </div>';

}


                } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage(), 'line' => $e->getLine()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle model not found errors
            return response()->json(['error' => 'Model not found: ' . $e->getMessage(), 'line' => $e->getLine()], 404);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
            // Handle file not found errors
            return response()->json(['error' => 'File not found: ' . $e->getMessage(), 'line' => $e->getLine()], 404);
        } catch (\Exception $e) {
            // Handle all other exceptions
            Log::error('Error in Allmeasurement: ' . $e->getMessage() . ' at line ' . $e->getLine());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage(), 'line' => $e->getLine()], 500);
        }
        //dd($workdetail);
              }

              else {

        // Perform the database query using the b_item_id
        $itemResponse = DB::table('embs')
            ->where('embs.b_item_id', '=', $bitemId)
            ->select('embs.*')
            ->get();
      //dd($itemResponse);




        $measid=DB::table('embs')->where('b_item_id', $bitemId)->select('meas_id')->get();

        //dd($measid);

             }


        try{

             // Fetch the item description from the bil_item table based on b_item_id
        $itemdata = DB::table('bil_item')->where('b_item_id', $bitemId)->get();

        $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

        $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

        $previousTBillId = DB::table('bills')
        ->where('work_id' , $workid)
        ->where('t_bill_id', '<', $tbillid) // Add your condition here
        ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
        ->limit(1) // Limit the result to 1 row
        ->value('t_bill_id');
        //dd($previousTBillId);

        $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

        $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


        $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
       // dd($previousexecqty);

        if (is_null($previousexecqty)) {
            $previousexecqty = 0;
        }

$curqty = number_format(round(DB::table('embs')->where('b_item_id', $bitemId)->where('notforpayment', 0)->sum('qty'), $Qtydec), 3, '.', '');
        //dd($previousexecqty);
        //dd($curqty);



$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
        //dd($execqty);
        //dd($totalqty);

        $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

        $curamt=$curqty*$billrt;

   $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

       $bitemamt=$curamt+$previousamt;

   DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

       'exec_qty' => $execqty,
       'cur_qty' => $curqty,
       'prv_bill_qty' => $previousexecqty,
       'cur_amt' => $curamt,
       'b_item_amt' => $bitemamt,
   ]);

   $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
                 $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
                //  $tndqty=round($tnditem->tnd_qty , 3);
                $tndqty=$tnditem->tnd_qty;
                
                 $amountconvert=new CommonHelper();
                

                 $tndcostitem=$tnditem->t_item_amt;
                 //dd($tndqty);
                 $percentage=round(($execqty / $tndqty)*100 , 2);
                 //dd($percentage);
                 $totlcostitem=round($billrt*$execqty , 2);

                 $costdifference= round($tndcostitem-$totlcostitem , 2);

                 $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);


                 $parta = 0; // Initialize the sum for matched conditions
                 $partb = 0; // Initialize the sum for unmatched conditions

                 $cparta = 0; // Initialize the sum for matched conditions
                 $cpartb = 0; // Initialize the sum for unmatched conditions

                 $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bItemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bItemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bItemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bItemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bItemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }

                 //dd($partb);

                 $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

                 $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
                 //dd($billgrossamt);
                 $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
                 //dd($beloaboperc);
                 $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

                 $bill_amt=0;
                $abeffect = $parta * ($beloaboperc / 100);
                $cabeffect = $cparta * ($beloaboperc / 100);
                //dd($cabeffect);

                               if ($beloAbo === 'Above') {


                                  $bill_amt = round(($parta + $abeffect), 2);
                                  $cbill_amt = round(($cparta + $cabeffect), 2);

                              } elseif ($beloAbo === 'Below') {

                                  $bill_amt = round(($parta - $abeffect), 2);
                                  $cbill_amt = round(($cparta - $cabeffect), 2);

                              }

                               // Determine whether to add a minus sign
                               if ($beloAbo === 'Below') {
                                   $abeffect = -$abeffect;
                                   $cabeffect = -$cabeffect;
                                   $beloaboperc = -$beloaboperc;
                                  }
                                  //dd($abeffect);
                                 //$part_a_ab=($parta * $beloaboperc / 100);
                                 //dd($partb);





                                 $Gstbase = round($bill_amt, 2);
                                 $cGstbase = round($cbill_amt, 2);
                                        //dd($Gstbase);

                                        $Gstamt= round($Gstbase*(18 / 100), 2);
                                        $cGstamt= round($cGstbase*(18 / 100), 2);
                                         //dd($cGstamt);

                                         $part_A_gstamt=$Gstbase + $Gstamt;
                                         $cpart_A_gstamt=$cGstbase + $cGstamt;


                                         $billamtgt = $partb + $part_A_gstamt;

                                         $cbillamtgt = $cpartb + $cpart_A_gstamt;
                                        // dd($cbillamtgt);
                           $integer_part = floor($billamtgt);  // Extract the integer part
                           $cinteger_part = floor($cbillamtgt);


                           $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                           $cdecimal_part = $cbillamtgt - $cinteger_part;
                           //dd($decimal_part);

                           $billamtro = round($decimal_part, 2);
                           $cbillamtro = round($cdecimal_part, 2);
                           //dd($rounded_decimal_part);

                      //     // Round the total bill amount
                      //     $billamtro = round($billamtgt);
                      //     //dd($rounded_billamtgt);

                      //    // Calculate the difference
                      //     //$billamtro = $rounded_billamtgt - $billamtgt;
                      //     dd($billamtro);
                          //$billamtro=0.37;
                          if ($billamtro > 0.50) {
                              // Calculate the absolute difference
                              $abs_diff = abs($billamtro);
                              $billamtro = 1 - $abs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $billamtro = -$billamtro;
                              //dd($billamtro);
                          }

                          if ($cbillamtro > 0.50) {
                              // Calculate the absolute difference
                              $cabs_diff = abs($cbillamtro);
                              $cbillamtro = 1 - $cabs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $cbillamtro = -$cbillamtro;
                              //dd($billamtro);
                          }
                           //dd($billamtro);

                           $net_amt= $billamtgt + $billamtro;
                           $cnet_amt= $cbillamtgt + $cbillamtro;
                           //dd($cnet_amt);

                            // Determine whether to add a minus sign


                           DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                              'part_a_amt' => $parta,
                              'part_a_gstamt' => $part_A_gstamt,
                              'part_b_amt' => $partb,
                              'gst_amt' => $Gstamt,
                              'gst_base' => $Gstbase,
                              'gross_amt' => $billgrossamt,
                              'a_b_effect' => $abeffect,
                              'bill_amt' => $bill_amt,
                              'bill_amt_gt' => $billamtgt,
                              'bill_amt_ro' => $billamtro,
                              'net_amt' => $net_amt,

                              'c_part_a_amt' => $cparta,
                              'c_part_a_gstamt' => $cpart_A_gstamt,
                              'c_part_b_amt' => $cpartb,
                              'curr_grossamt' => $cbillgrossamt,
                              'c_billamt' =>  $cbill_amt,
                              'c_gstamt' => $cGstamt,
                              'c_gstbase' => $cGstbase,
                              'c_abeffect' => $cabeffect,
                              'c_billamtgt' => $cbillamtgt,
                              'c_billamtro' => $cbillamtro,
                              'c_netamt' => $cnet_amt
                           ]);

                           $bitemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

                           $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();
                           
                           $convert=new CommonHelper();

                           $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
                           $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
                           '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
                           '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($bitemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
                           '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
                           '</div></div>';






            return response()->json(['b_item_id' => $bitemId, 'itemResponse' => $itemResponse, 'itemdata' => $itemdata, 'measid' => $measid , 'html' => $html ,
            'previousexecqty' => $previousexecqty , 'curqty' => $curqty , 'execqty' => $execqty ,'tndqty' => $tndqty , 'tndcostitem' => $tndcostitem , 'percentage' => $percentage ,
             'totlcostitem' => $totlcostitem , 'costdifference' => $costdifference , 'htmlnormal' =>  $htmlnormal ,'workdetail' => $workdetail]);
             
             
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage(), 'line' => $e->getLine()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle model not found errors
            return response()->json(['error' => 'Model not found: ' . $e->getMessage(), 'line' => $e->getLine()], 404);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
            // Handle file not found errors
            return response()->json(['error' => 'File not found: ' . $e->getMessage(), 'line' => $e->getLine()], 404);
        } catch (\Exception $e) {
            // Handle all other exceptions
            Log::error('Error in Allmeasurement: ' . $e->getMessage() . ' at line ' . $e->getLine());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage(), 'line' => $e->getLine()], 500);
        }

    }





//delete the bill_items from given bill
public function deletebillitem(Request $request)
{
    
   
    DB::beginTransaction();

    try{
        
    
    $bItemId = $request->input('b_item_id');
    // dd( $bItemId);
    $workid = $request->input('work_id');
    //dd( $work_id);
    // Find the row by b_item_id and delete it
    $billItem =DB::table('bil_item')->where('b_item_id', $bItemId)->first();
 //dd($billItem);

  $tbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->value('t_bill_id');
 //dd($tbillid);

  if (!$tbillid) {
        return response()->json(['message' => 'Bill not found'], 404);
    }

    // Perform the delete operation using the DB facade
    // DB::table('bil_item')->where('b_item_id', $bItemId)->delete();
    $itemid=DB::table('bil_item')
    ->where('b_item_id' , $bItemId)
    ->get()
    ->value('item_id');
    // dd($itemid);
         if (
        in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])
        )
{
    $billItemdele=DB::table('bil_item')
    ->where('item_id',$itemid)
    ->where('b_item_id', $bItemId)
    ->delete();
    // dd($billdele);
    $billRCMember=DB::table('bill_rcc_mbr')
    ->where('b_item_id', $bItemId)
    ->delete();
    // dd($billRCMember);

    $part_rt_d=DB::table('part_rt_d')
    ->where('b_item_id', $bItemId)
    ->delete();
    // dd($part_rt_d);

    $part_rt_ms=DB::table('part_rt_ms')
    ->where('b_item_id', $bItemId)
    ->delete();
    // dd($part_rt_ms);
    $stlmeas=DB::table('stlmeas')
    ->where('b_item_id', $bItemId)
    ->delete();
    // dd($stlmeas);
}
else
{
    $billItemNormaldele=DB::table('bil_item')
    ->where('b_item_id', $bItemId)
    ->delete();
// dd($billItemNormaldele);

// dd($billRCMember);

$embs=DB::table('embs')
->where('b_item_id', $bItemId)
->delete();
// dd($embs);

$part_rt_dnormal=DB::table('part_rt_d')
->where('b_item_id', $bItemId)
->delete();
// dd($part_rt_dnormal);

$part_rt_msmormal=DB::table('part_rt_ms')
->where('b_item_id', $bItemId)
->delete();
// dd($part_rt_msmormal);
}

DB::table('mat_cons_d')
->where('b_item_id', $bItemId)
->delete();

DB::table('mat_cons_m')
->where('b_item_id', $bItemId)
->delete();

DB::table('royal_d')
->where('b_item_id', $bItemId)
->delete();

DB::table('royal_m')
->where('b_item_id', $bItemId)
->delete();




$bitemids=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get('b_item_id');


if ($bitemids->isEmpty()) {
    // dd($royalm);
 
     // If $royalm is empty, display an alert and return the view
     DB::table('bills')->where('t_bill_id' , $tbillid)->update(['mb_status' => 1]);
 }
//dd($bitemids);

$parta = 0; // Initialize the sum for matched conditions
$partb = 0; // Initialize the sum for unmatched conditions

$cparta = 0; // Initialize the sum for matched conditions
$cpartb = 0; // Initialize the sum for unmatched conditions

$itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }


$billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

$cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
//dd($billgrossamt);
$beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
//dd($beloaboperc);
$beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

$bill_amt=0;
$abeffect = $parta * ($beloaboperc / 100);
$cabeffect = $cparta * ($beloaboperc / 100);

              if ($beloAbo === 'Above') {


                 $bill_amt = round(($parta + $abeffect), 2);
                 $cbill_amt = round(($cparta + $cabeffect), 2);

             } elseif ($beloAbo === 'Below') {

                 $bill_amt = round(($parta - $abeffect), 2);
                 $cbill_amt = round(($cparta - $cabeffect), 2);

             }

              // Determine whether to add a minus sign
              if ($beloAbo === 'Below') {
                  $abeffect = -$abeffect;
                  $cabeffect = -$cabeffect;
                  $beloaboperc = -$beloaboperc;
                 }
                 //dd($abeffect);
                //$part_a_ab=($parta * $beloaboperc / 100);
                //dd($partb);





                $Gstbase = round($bill_amt, 2);
                $cGstbase = round($cbill_amt, 2);
                       //dd($Gstbase);

                       $Gstamt= round($Gstbase*(18 / 100), 2);
                       $cGstamt= round($cGstbase*(18 / 100), 2);
                        //dd($Gstamt);

                        $part_A_gstamt=$Gstbase + $Gstamt;
                        $cpart_A_gstamt=$cGstbase + $cGstamt;


                        $billamtgt = $partb + $part_A_gstamt;
                        $cbillamtgt = $cpartb + $cpart_A_gstamt;

          $integer_part = floor($billamtgt);  // Extract the integer part
          $cinteger_part = floor($cbillamtgt);


          $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
          $cdecimal_part = $cbillamtgt - $cinteger_part;
          //dd($decimal_part);

          $billamtro = round($decimal_part, 2);
          $cbillamtro = round($cdecimal_part, 2);
          //dd($rounded_decimal_part);

     //     // Round the total bill amount
     //     $billamtro = round($billamtgt);
     //     //dd($rounded_billamtgt);

     //    // Calculate the difference
     //     //$billamtro = $rounded_billamtgt - $billamtgt;
     //     dd($billamtro);
         //$billamtro=0.37;
         if ($billamtro > 0.50) {
             // Calculate the absolute difference
             $abs_diff = abs($billamtro);
             $billamtro = 1 - $abs_diff;
             //dd($billamtro);
         }
         else {
             // If it is, add a minus sign to the difference
             $billamtro = -$billamtro;
             //dd($billamtro);
         }

         if ($cbillamtro > 0.50) {
             // Calculate the absolute difference
             $cabs_diff = abs($cbillamtro);
             $cbillamtro = 1 - $cabs_diff;
             //dd($billamtro);
         }
         else {
             // If it is, add a minus sign to the difference
             $cbillamtro = -$cbillamtro;
             //dd($billamtro);
         }
          //dd($billamtro);

          $net_amt= $billamtgt + $billamtro;
          $cnet_amt= $cbillamtgt + $cbillamtro;
          //dd($net_amt);

           // Determine whether to add a minus sign


          DB::table('bills')->where('t_bill_id' , $tbillid)->update([

             'part_a_amt' => $parta,
             'part_a_gstamt' => $part_A_gstamt,
             'part_b_amt' => $partb,
             'gst_amt' => $Gstamt,
             'gst_base' => $Gstbase,
             'gross_amt' => $billgrossamt,
             'a_b_effect' => $abeffect,
             'bill_amt' => $bill_amt,
             'bill_amt_gt' => $billamtgt,
             'bill_amt_ro' => $billamtro,
             'net_amt' => $net_amt,

             'c_part_a_amt' => $cparta,
             'c_part_a_gstamt' => $cpart_A_gstamt,
             'c_part_b_amt' => $cpartb,
             'curr_grossamt' => $cbillgrossamt,
             'c_billamt' =>  $cbill_amt,
             'c_gstamt' => $cGstamt,
             'c_gstbase' => $cGstbase,
             'c_abeffect' => $cabeffect,
             'c_billamtgt' => $cbillamtgt,
             'c_billamtro' => $cbillamtro,
             'c_netamt' => $cnet_amt
          ]);








                 //$billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
                  $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderBy('bil_item.t_item_no', 'asc')->paginate(5);
                  //dd($billitemdata);
                  $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();



//dd($billitemdata);

   // Get the remaining bill items after deletion
    //$billitems = DB::table('bil_item')->where('t_bill_id', '=', $lasttbillid->t_bill_Id)->select('bil_item.*')->get()->toArray();

    //dd($billitems); // Output the remaining bill items in array format
    $previousPage = session()->get('previous_page');
    // Append the route to the current URL
    $redirectUrl = redirect()->route('emb.workmasterdata', ['id' => $lasttbillid->t_bill_Id, 'page' => $previousPage])->getTargetUrl();
    
     DB::commit();

    return response()->json(['message' => 'Item deleted successfully',  'billdata' => $billdata,
    'billitemdata' => $billitemdata,
    'lasttbillid' => $lasttbillid,
    'redirect_url' => $redirectUrl]);
    
    
    
    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json(['error' => 'Validation failed: ' . $e->getMessage()], 422);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['error' => 'Model not found: ' . $e->getMessage()], 404);
    } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
        DB::rollBack();
        return response()->json(['error' => 'File not found: ' . $e->getMessage()], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error in Edit Measurement Box ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}




//edit mb data in edit emb button
public function editmbdata(Request $request)
{
    try{

    $measid = $request->input('meas_id');
  //dd($measid);

  $embdata=DB::table('embs')->where('meas_id', '=', $measid)->get();
  if ($embdata->isEmpty()) {
    throw new \Exception('Measurement data not found.');
    }

  $bItemId=DB::table('embs')->where('meas_id', '=', $measid)->value('b_item_id');
  if (!$bItemId) {
    throw new \Exception('Bill item ID not found.');
   }

  //workdetails
  $billtemdata=DB::table('bil_item')->where('b_item_id', $bItemId)->first();
  if (!$billtemdata) {
    throw new \Exception('Bill item data not found.');
}

  $tbillid=DB::table('bil_item')->where('b_item_id', $bItemId)->value('t_bill_id');
  if (!$tbillid) {
    throw new \Exception('Total bill ID not found.');
}

  $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();
  if (!$tbilldata) {
    throw new \Exception('Total bill data not found.');
}

  $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');
  if (!$workid) {
    throw new \Exception('Work ID not found.');
}


$convert=new CommonHelper();

  $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
  if (!$workmasterdetail) {
    throw new \Exception('Work master detail not found.');
}

  $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
  '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
  '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
  '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
  '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees(
$workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
  '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
  '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
  '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees(
$billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
  '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees(
$tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
  '</div></div>';
  //dd($embdata);
 // Adjust the base path according to your project




 $photo1Urls = [];
$photo2Urls = [];
$photo3Urls = [];
$docUrls = [];

foreach ($embdata as $row) {
    $photo1Urls[] = asset('storage/' . $row->photo1);
    $photo2Urls[] = asset('storage/' . $row->photo2);
    $photo3Urls[] = asset('storage/' . $row->photo3);
    $docUrls[] = asset('storage/' . $row->drg);
}

return response()->json([
    'embdata' => $embdata,
    'photo1Urls' => $photo1Urls,
    'photo2Urls' => $photo2Urls,
    'photo3Urls' => $photo3Urls,
    'docUrls' => $docUrls,
    'workdetail' => $workdetail,
    'tbilldata' => $tbilldata
]);

 } catch (\Exception $e) {
        Log::error('Error in editmbdata: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}
//update mb data edited function
public function updatembdata(Request $request)
{
    $formdata = $request->except(['meas_id']);

    // Access uploaded files

//dd($formdata);
    // Access non-file data
    $length = $request->input('length');
    $breadth = $request->input('breadth');
    $height = $request->input('height');
    $formula = $request->input('formula');
    $particulars = $request->input('particulars');
    $number = $request->input('number');
    $quantity = $request->input('quantity');
    $dom = $request->input('dom');
     $measid = $request->input('meas_id');
     $notforpay=$request->input('notforpayment');
   // dd($notforpay);

  $measdata=DB::table('embs')->where('meas_id', '=', $measid)->first();
 //dd($measdata);
 $timestamp = time(); // Get the current timestamp

 // Fetch existing item_desc data
//  $existingDesc = DB::table('embs')
//  ->where('meas_id', '=', $measid)
//  ->value('parti');

// Check if $existingDesc is not empty
// if ($existingDesc !== null) {
 if ($notforpay == '1') {
     // Append the string to the existing data
     $particulars = $particulars . " (Not for payment)";
 } else {
     // Remove the string if present
     $particulars = str_replace(" (Not for payment)", "", $particulars);
 }


try{
 // ... (other code)

 // Update the record with the rest of the data
 $updateResult = DB::table('embs')
     ->where('meas_id', $measid)
     ->update([
         'length' => $length,
         'breadth' => $breadth,
         'height' => $height,
         'formula' => $formula,
         'parti' => $particulars,
         'number' => $number,
         'qty' => $quantity,
         'measurment_dt' => $dom,
         'notforpayment' => $notforpay,
         'dyE_chk_dt' => $dom,
     ]);

 // ... (other code)


 $bitemId=DB::table('embs')->where('meas_id', $measid)->value('b_item_id');
 //dd($bitemid);
 $updateembrow=DB::table('embs')->where('meas_id', $measid)->get();
 //dd($updateembrow);

 $embdata=DB::table('embs')->where('b_item_id', $bitemId)->get();
//dd($embdata);

$billitem = DB::table('bil_item')->where('b_item_id', $bitemId)->get();
//dd($billitem);


$tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

$workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

        $previousTBillId = DB::table('bills')
        ->where('work_id' , $workid)
        ->where('t_bill_id', '<', $tbillid) // Add your condition here
        ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
        ->limit(1) // Limit the result to 1 row
        ->value('t_bill_id');

//dd($previousTBillId);

$titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

$Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


$previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
//dd($previousexecqty);

if (is_null($previousexecqty)) {
    $previousexecqty = 0;
}

$curqty = number_format(round(DB::table('embs')->where('b_item_id', $bitemId)->where('notforpayment', 0)->sum('qty'), $Qtydec), 3, '.', '');
//dd($previousexecqty);
//dd($curqty);



$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
//dd($execqty);



//dd($totalqty);
$billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                      $curamt=$curqty*$billrt;

                 $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

                     $bitemamt=$curamt+$previousamt;

                 DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

                     'exec_qty' => $execqty,
                     'cur_qty' => $curqty,
                     'prv_bill_qty' => $previousexecqty,
                     'cur_amt' => $curamt,
                     'b_item_amt' => $bitemamt,
                 ]);


                 //$bitemId
                 $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
                 $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
                //  $tndqty=round($tnditem->tnd_qty , 3);
                $tndqty=$tnditem->tnd_qty;
                
                
                
                 $amountconvert=new CommonHelper();
                


                 $tndcostitem=$tnditem->t_item_amt;
                 //dd($tndqty);
                 $percentage=round(($execqty / $tndqty)*100 , 2);
                 //dd($percentage);
                 $totlcostitem=round($billrt*$execqty , 2);

                 $costdifference= round($tndcostitem-$totlcostitem , 2);

                 $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);



                 $parta = 0; // Initialize the sum for matched conditions
                 $partb = 0; // Initialize the sum for unmatched conditions

                 $cparta = 0; // Initialize the sum for matched conditions
                 $cpartb = 0; // Initialize the sum for unmatched conditions

                 $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }


                 $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

                 $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
                 //dd($billgrossamt);
                 $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
                 //dd($beloaboperc);
                 $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

                 $bill_amt=0;
                $abeffect = $parta * ($beloaboperc / 100);
                $cabeffect = $cparta * ($beloaboperc / 100);

                               if ($beloAbo === 'Above') {


                                  $bill_amt = round(($parta + $abeffect), 2);
                                  $cbill_amt = round(($cparta + $cabeffect), 2);

                              } elseif ($beloAbo === 'Below') {

                                  $bill_amt = round(($parta - $abeffect), 2);
                                  $cbill_amt = round(($cparta - $cabeffect), 2);

                              }

                               // Determine whether to add a minus sign
                               if ($beloAbo === 'Below') {
                                   $abeffect = -$abeffect;
                                   $cabeffect = -$cabeffect;
                                   $beloaboperc = -$beloaboperc;
                                  }
                                  //dd($abeffect);
                                 //$part_a_ab=($parta * $beloaboperc / 100);
                                 //dd($partb);





                                 $Gstbase = round($bill_amt, 2);
                                 $cGstbase = round($cbill_amt, 2);
                                        //dd($Gstbase);

                                        $Gstamt= round($Gstbase*(18 / 100), 2);
                                        $cGstamt= round($cGstbase*(18 / 100), 2);
                                         //dd($Gstamt);

                                         $part_A_gstamt=$Gstbase + $Gstamt;
                                         $cpart_A_gstamt=$cGstbase + $cGstamt;


                                         $billamtgt = $partb + $part_A_gstamt;
                                         $cbillamtgt = $cpartb + $cpart_A_gstamt;

                           $integer_part = floor($billamtgt);  // Extract the integer part
                           $cinteger_part = floor($cbillamtgt);


                           $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                           $cdecimal_part = $cbillamtgt - $cinteger_part;
                           //dd($decimal_part);

                           $billamtro = round($decimal_part, 2);
                           $cbillamtro = round($cdecimal_part, 2);
                           //dd($rounded_decimal_part);

                      //     // Round the total bill amount
                      //     $billamtro = round($billamtgt);
                      //     //dd($rounded_billamtgt);

                      //    // Calculate the difference
                      //     //$billamtro = $rounded_billamtgt - $billamtgt;
                      //     dd($billamtro);
                          //$billamtro=0.37;
                          if ($billamtro > 0.50) {
                              // Calculate the absolute difference
                              $abs_diff = abs($billamtro);
                              $billamtro = 1 - $abs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $billamtro = -$billamtro;
                              //dd($billamtro);
                          }

                          if ($cbillamtro > 0.50) {
                              // Calculate the absolute difference
                              $cabs_diff = abs($cbillamtro);
                              $cbillamtro = 1 - $cabs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $cbillamtro = -$cbillamtro;
                              //dd($billamtro);
                          }
                           //dd($billamtro);

                           $net_amt= $billamtgt + $billamtro;
                           $cnet_amt= $cbillamtgt + $cbillamtro;
                           //dd($net_amt);

                            // Determine whether to add a minus sign


                           DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                              'part_a_amt' => $parta,
                              'part_a_gstamt' => $part_A_gstamt,
                              'part_b_amt' => $partb,
                              'gst_amt' => $Gstamt,
                              'gst_base' => $Gstbase,
                              'gross_amt' => $billgrossamt,
                              'a_b_effect' => $abeffect,
                              'bill_amt' => $bill_amt,
                              'bill_amt_gt' => $billamtgt,
                              'bill_amt_ro' => $billamtro,
                              'net_amt' => $net_amt,

                              'c_part_a_amt' => $cparta,
                              'c_part_a_gstamt' => $cpart_A_gstamt,
                              'c_part_b_amt' => $cpartb,
                              'curr_grossamt' => $cbillgrossamt,
                              'c_billamt' =>  $cbill_amt,
                              'c_gstamt' => $cGstamt,
                              'c_gstbase' => $cGstbase,
                              'c_abeffect' => $cabeffect,
                              'c_billamtgt' => $cbillamtgt,
                              'c_billamtro' => $cbillamtro,
                              'c_netamt' => $cnet_amt
                           ]);




                           $billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
                  $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->paginate(5);

                  $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();




                  //workdetails
   $biltemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

   $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();
   
   $convert=new CommonHelper();

   $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
   $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
   '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees(
$workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees(
$biltemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
   '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees(
$tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .

   '</div></div>';

if ($updateembrow) {
 return response()->json(['success' => true, 'updateembrow' => $updateembrow, 'embdata' => $embdata, 'billitem' => $billitem, 'previousexecqty' => $previousexecqty , 'curqty' => $curqty , 'execqty' => $execqty , 'billdata' => $billdata,
 'billitemdata' => $billitemdata,
 'lasttbillid' => $lasttbillid, 'tndqty' => $tndqty , 'tndcostitem' => $tndcostitem , 'percentage' => $percentage , 'totlcostitem' => $totlcostitem , 'costdifference' => $costdifference , 'bitemId' => $bitemId , 'workdetail' => $workdetail ]);
} else {
 return response()->json(['success' => false]);
}


    } catch (\Exception $e) {
        Log::error('Error in editmbdata: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }

}


//delete the mb from emb table
public function deletemb(Request $request)
{
    
     DB::beginTransaction();
     
     try{
         
    $measid =$request->input('measid');
    //dd($measid);

      // Delete the record from the embs table based on the meas_id
    // Find the Emb record by meas_id
            $emb = DB::table('embs')->where('meas_id', $measid)->first();
        
            $bitemid=DB::table('embs')->where('meas_id', $measid)->value('b_item_id');
        //dd($bitemid);
         //dd($emb);
         $billitemdata=DB::table('bil_item')->where('b_item_id', '=', $bitemid)->first();
         //dd($billitemdata);
    if ($emb) {
        // Delete the record
        $deletedRows = DB::table('embs')->where('meas_id', $measid)->delete();
        // After deletion, retrieve the remaining data with the same meas_id



        $remainingData = DB::table('embs')->where('b_item_id', $bitemid)->get();

        $tbillid=DB::table('bil_item')->where('b_item_id', $bitemid)->value('t_bill_id');

        $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

        $previousTBillId = DB::table('bills')
        ->where('work_id' , $workid)
        ->where('t_bill_id', '<', $tbillid) // Add your condition here
        ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
        ->limit(1) // Limit the result to 1 row
        ->value('t_bill_id');

        $titemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('t_item_id');

        $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


        $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemid)->value('prv_bill_qty') , 3);
        //dd($previousexecqty);

        if (is_null($previousexecqty)) {
            $previousexecqty = 0;
        }

            $curqty = number_format(round(DB::table('embs')->where('b_item_id', $bitemid)->where('notforpayment', 0)->sum('qty'), $Qtydec), 3, '.', '');
                    //dd($previousexecqty);
                    //dd($curqty);
            
            
            
            $execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
                    //dd($execqty);
            
            
            $billrt=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('bill_rt');

                      $curamt=$curqty*$billrt;
                     // dd($curamt);

                 $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('previous_amt');

                     $bitemamt=$curamt+$previousamt;

                 DB::table('bil_item')->where('b_item_id' , $bitemid)->update([

                     'exec_qty' => $execqty,
                     'cur_qty' => $curqty,
                     'prv_bill_qty' => $previousexecqty,
                     'cur_amt' => $curamt,
                     'b_item_amt' => $bitemamt,
                 ]);

                //  $a=DB::table('bil_item')->where('b_item_id' , $bitemid)->get();
                //  dd($a);

                 $titemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('t_item_id');
                 $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
                //  $tndqty=round($tnditem->tnd_qty , 3);
                $tndqty=$tnditem->tnd_qty;
                
                 $amountconvert=new CommonHelper();
                

                 $tndcostitem=$tnditem->t_item_amt;
                 //dd($tndqty);
                 $percentage=round(($execqty / $tndqty)*100 , 2);
                 //dd($percentage);
                 $totlcostitem=round($billrt*$execqty , 2);

                 $costdifference= round($tndcostitem-$totlcostitem , 2);

                 $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);



                 $parta = 0; // Initialize the sum for matched conditions
                 $partb = 0; // Initialize the sum for unmatched conditions

                 $cparta = 0; // Initialize the sum for matched conditions
                 $cpartb = 0; // Initialize the sum for unmatched conditions

                 $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bItemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bItemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bItemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bItemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bItemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }


                 $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

                 $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
                 //dd($billgrossamt);
                 $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
                 //dd($beloaboperc);
                 $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

                 $bill_amt=0;
                $abeffect = $parta * ($beloaboperc / 100);
                $cabeffect = $cparta * ($beloaboperc / 100);

                               if ($beloAbo === 'Above') {


                                  $bill_amt = round(($parta + $abeffect), 2);
                                  $cbill_amt = round(($cparta + $cabeffect), 2);

                              } elseif ($beloAbo === 'Below') {

                                  $bill_amt = round(($parta - $abeffect), 2);
                                  $cbill_amt = round(($cparta - $cabeffect), 2);

                              }

                               // Determine whether to add a minus sign
                               if ($beloAbo === 'Below') {
                                   $abeffect = -$abeffect;
                                   $cabeffect = -$cabeffect;
                                   $beloaboperc = -$beloaboperc;
                                  }
                                  //dd($abeffect);
                                 //$part_a_ab=($parta * $beloaboperc / 100);
                                 //dd($partb);





                                 $Gstbase = round($bill_amt, 2);
                                 $cGstbase = round($cbill_amt, 2);
                                        //dd($Gstbase);

                                        $Gstamt= round($Gstbase*(18 / 100), 2);
                                        $cGstamt= round($cGstbase*(18 / 100), 2);
                                         //dd($Gstamt);

                                         $part_A_gstamt=$Gstbase + $Gstamt;
                                         $cpart_A_gstamt=$cGstbase + $cGstamt;


                                         $billamtgt = $partb + $part_A_gstamt;
                                         $cbillamtgt = $cpartb + $cpart_A_gstamt;

                           $integer_part = floor($billamtgt);  // Extract the integer part
                           $cinteger_part = floor($cbillamtgt);


                           $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                           $cdecimal_part = $cbillamtgt - $cinteger_part;
                           //dd($decimal_part);

                           $billamtro = round($decimal_part, 2);
                           $cbillamtro = round($cdecimal_part, 2);
                           //dd($rounded_decimal_part);

                      //     // Round the total bill amount
                      //     $billamtro = round($billamtgt);
                      //     //dd($rounded_billamtgt);

                      //    // Calculate the difference
                      //     //$billamtro = $rounded_billamtgt - $billamtgt;
                      //     dd($billamtro);
                          //$billamtro=0.37;
                          if ($billamtro > 0.50) {
                              // Calculate the absolute difference
                              $abs_diff = abs($billamtro);
                              $billamtro = 1 - $abs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $billamtro = -$billamtro;
                              //dd($billamtro);
                          }

                          if ($cbillamtro > 0.50) {
                              // Calculate the absolute difference
                              $cabs_diff = abs($cbillamtro);
                              $cbillamtro = 1 - $cabs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $cbillamtro = -$cbillamtro;
                              //dd($billamtro);
                          }
                           //dd($billamtro);

                           $net_amt= $billamtgt + $billamtro;
                           $cnet_amt= $cbillamtgt + $cbillamtro;
                           //dd($net_amt);

                            // Determine whether to add a minus sign


                           DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                              'part_a_amt' => $parta,
                              'part_a_gstamt' => $part_A_gstamt,
                              'part_b_amt' => $partb,
                              'gst_amt' => $Gstamt,
                              'gst_base' => $Gstbase,
                              'gross_amt' => $billgrossamt,
                              'a_b_effect' => $abeffect,
                              'bill_amt' => $bill_amt,
                              'bill_amt_gt' => $billamtgt,
                              'bill_amt_ro' => $billamtro,
                              'net_amt' => $net_amt,

                              'c_part_a_amt' => $cparta,
                              'c_part_a_gstamt' => $cpart_A_gstamt,
                              'c_part_b_amt' => $cpartb,
                              'curr_grossamt' => $cbillgrossamt,
                              'c_billamt' =>  $cbill_amt,
                              'c_gstamt' => $cGstamt,
                              'c_gstbase' => $cGstbase,
                              'c_abeffect' => $cabeffect,
                              'c_billamtgt' => $cbillamtgt,
                              'c_billamtro' => $cbillamtro,
                              'c_netamt' => $cnet_amt
                           ]);


                           $billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
                           $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->paginate(5);

                          // dd($billitemdata);
                           $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();

                           $itemdata = DB::table('bil_item')->where('b_item_id' ,  $bitemid)->get();
                           //dd($itemdata);

                           $biltemdata=DB::table('bil_item')->where('b_item_id', $bitemid)->first();
                          // dd($biltemdata);

                           $tbillid=DB::table('bil_item')->where('b_item_id', $bitemid)->value('t_bill_id');

                           $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

                           $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

                           $convert=new CommonHelper();

                             $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
                             $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
                             '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
                             '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
                             '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
                             '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
                             '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
                             '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
                             '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($biltemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
                             '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
                             '</div></div>';




     DB::commit();
     

        //dd($remainingData);
        if ($deletedRows > 0) {


             return response()->json(['success' => true, 'remainingData' => $remainingData, 'billitemdata' => $billitemdata , 'previousexecqty' => $previousexecqty , 'curqty' => $curqty , 'execqty' => $execqty , 'billdata' => $billdata,
             'billitemdata' => $billitemdata,
             'lasttbillid' => $lasttbillid, 'tndqty' => $tndqty , 'tndcostitem' => $tndcostitem , 'percentage' => $percentage , 'totlcostitem' => $totlcostitem ,
              'costdifference' => $costdifference , 'bitemid' => $bitemid , 'itemdata' => $itemdata , 'workdetail' => $workdetail]);
                } else {
                    return response()->json(['success' => false]);
                }
            } else {
                return response()->json(['success' => false]);
            }
    
    
        

  } catch (\Exception $e) {
    DB::rollback();
    Log::error('Error in editmbdata: ' . $e->getMessage());
    return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
}

}

//item description for manual entery form
public function fetchItemDescription(Request $request)
{
    
    try{
        
    
    $bItemId = $request->input('bItemId');
     $itemid = DB::table('bil_item')->where('b_item_id' , $bItemId)->value('item_id');

      // Fetch the item description based on bItemId
    $itemDescription = DB::table('bil_item')->where('b_item_id', $bItemId)->value('item_desc');
    $bitemdata = DB::table('bil_item')->where('b_item_id', $bItemId)->get();


    //workdetails
    $billtemdata=DB::table('bil_item')->where('b_item_id', $bItemId)->first();

 $tbillid=DB::table('bil_item')->where('b_item_id', $bItemId)->value('t_bill_id');

 $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

 $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

   $convert=new CommonHelper();

   $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
   $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
   '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
   '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->curr_grossamt) . '</span>&nbsp;&nbsp;&nbsp;' .
   '</div></div>';





    if (
        in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])
            //in_array(substr($itemid, -6), ["001295", "001298", "002115", "003960", "003963", "004351", "003550", "003551", "002064", "002065", "002066", "002067", "002068", "002069", "003399", "003558", "004566", "004567"])
        ) {

            $rccbillmember=DB::table('rcc_mbr')->select('rcc_mbr_id' , 'rcc_memb')->get();

            return response()->json(['itemid' => $itemid , 'rccbillmember' => $rccbillmember , 'itemDescription' => $itemDescription , 'bitemdata' => $bitemdata , 'workdetail' => $workdetail , 'tbilldata' => $tbilldata]);
        }



    return response()->json(['itemDescription' => $itemDescription , 'bitemdata' => $bitemdata , 'workdetail' => $workdetail , 'tbilldata' => $tbilldata]);
    
    
    } catch (\Exception $e) {
                Log::error('Error in editmbdata: ' . $e->getMessage());
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
      }

}


//submit the new measurement or mb with photos and document uploads
public function submitmeasurement(Request $request)
{
    
     DB::beginTransaction();
     
     try{
     
    $particulars='';
    $formdata = $request->except(['Bitemid']); // Exclude Bitemid from form data
    $bitemId = $request->input('Bitemid'); // Get Bitemid from form data

    // dd($request);  
    $notforpay=$request->input('newnotforpayment') ;
    $Parti=$request->input('Particulars') ;
   //dd($notforpay);
     // Extract tbillid by removing the last four digits
     $tbillid = substr($bitemId, 0, -4);
     //dd($tbillid);
     // Extract work_id by removing the last four digits of tbillid
     $work_id = substr($tbillid, 0, -4);
     //dd($work_id);
     // Get the previous meas_id from embs table
     $previousmeasidObj = DB::table('embs')->where('b_item_id', '=', $bitemId)->orderBy('meas_id', 'desc')->select('meas_id')->first();

     if ($previousmeasidObj) {
         $previousmeasid = $previousmeasidObj->meas_id; // Convert object to string
         // Increment the last four digits of the previous meas_id
          $lastFourDigits = intval(substr($previousmeasid, -4));
          $newLastFourDigits = str_pad(($lastFourDigits + 1), 4, '0', STR_PAD_LEFT);
          $newmeasid = $bitemId.$newLastFourDigits;
          //dd($newmeasid);
    } else {
        // If no previous meas_id, start with bitemid.0001
        $newmeasid = $bitemId.'0001';
    }
  //dd($newmeasid);
  // Increment sr_no
  $srNo = DB::table('embs')->where('b_item_id', '=', $bitemId)->orderBy('sr_no', 'desc')->select('sr_no')->first();


       if ($srNo) {
             $srNo = $srNo->sr_no + 1;
       } else {
          // If no previous entry, start with 1 or any desired value
         $srNo = 1;
        }
    //dd($srNo);

    $timestamp = now()->timestamp; // Get the current timestamp

    $photo1Path = null;
    $photo2Path = null;
    $photo3Path = null;
    $documentsPath = null;

    $timestamp = time(); // You can use this timestamp for generating unique file paths

    // if ($request->hasFile('photo1')) {
    //     $photo1Path = $request->file('photo1')->store('photos', 'public');
    // }
    // if ($request->hasFile('photo2')) {
    //     $photo2Path = $request->file('photo2')->store('photos/' . $timestamp, 'public');
    // }
    // //dd( $photo2Path);

    // if ($request->hasFile('photo3')) {
    //     $photo3Path = $request->file('photo3')->store('photos/' . $timestamp, 'public');
    // }

    // if ($request->hasFile('documents')) {
    //     $documentsPath = $request->file('documents')->store('documents/' . $timestamp, 'public');
    // }

    $date=$formdata['dom'];

    $quantity=$formdata['Quantity'];
    //dd($quantity);

       $measdtfrom=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_from');
                       //dd($measdtfrom);
     $measdtupto=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_upto');
                              //dd($measdtupto);
        if ($notforpay == '1') {
        // Append the string to the existing data
        $particulars = $Parti . " (Not for payment)";
    } else {
        // Remove the string if present
        $particulars = str_replace(" (Not for payment)", "", $Parti);
    }
    // dd($particulars);
     if ( $date >= $measdtfrom && $date <= $measdtupto && $quantity != 0) {
   DB::table('embs')->insert([
    'Work_Id' => $work_id,
    't_bill_id' => $tbillid,
    'meas_id' => $newmeasid,
    'b_item_id' => $bitemId,
    'parti' => $particulars,
    'number' => $formdata['Number'],
    'length' => $formdata['Length'],
    'breadth' => $formdata['Breadth'],
    'height' => $formdata['Height'],
    'qty' => $formdata['Quantity'],
    'formula' => $formdata['Formula'],
    'measurment_dt' => $formdata['dom'],
    'notforpayment' => $formdata['newnotforpayment'],
    'sr_no' => $srNo,
    'dyE_chk_dt'=> $formdata['dom'],
    ]);

     }
    //       else{
    //     //dd($formdata['dom']);
    //     return response()->json(['error' => 'Please fill in the correct measurement date' , 'bitemid' => $bitemId]);
    //  }

    $embdata=DB::table('embs')->where('b_item_id', '=', $bitemId)->get();
    //dd($embdata);

    $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

    $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

        $previousTBillId = DB::table('bills')
        ->where('work_id' , $workid)
        ->where('t_bill_id', '<', $tbillid) // Add your condition here
        ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
        ->limit(1) // Limit the result to 1 row
            ->value('t_bill_id');
    //dd($previousTBillId);

    $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

    $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


    $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
    //dd($previousexecqty);

    if (is_null($previousexecqty)) {
        $previousexecqty = 0;
    }

$curqty = number_format(round(DB::table('embs')->where('b_item_id', $bitemId)->where('notforpayment', 0)->sum('qty'), $Qtydec), 3, '.', '');
    //dd($previousexecqty);
    //dd($curqty);



$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
    //dd($execqty);
    //dd($totalqty);
    $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                      $curamt=$curqty*$billrt;

                 $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

                     $bitemamt=$curamt+$previousamt;

                 DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

                     'exec_qty' => $execqty,
                     'cur_qty' => $curqty,
                     'prv_bill_qty' => $previousexecqty,
                     'cur_amt' => $curamt,
                     'b_item_amt' => $bitemamt,
                 ]);

                 $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
                 $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
                //  $tndqty=round($tnditem->tnd_qty , 3);
                $tndqty=$tnditem->tnd_qty;
                
                 $amountconvert=new CommonHelper();
                

                
                 $tndcostitem=$tnditem->t_item_amt;
                 //dd($tndqty);
                 $percentage=round(($execqty / $tndqty)*100 , 2);
                 //dd($percentage);
                 $totlcostitem=round($billrt*$execqty , 2);

                 $costdifference= round($tndcostitem-$totlcostitem , 2);
                 //dd($costdifference);

                 $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);



                 $parta = 0; // Initialize the sum for matched conditions
                 $partb = 0; // Initialize the sum for unmatched conditions

                 $cparta = 0; // Initialize the sum for matched conditions
                 $cpartb = 0; // Initialize the sum for unmatched conditions

                $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }



                 $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

                 $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
                 //dd($billgrossamt);
                 $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
                 //dd($beloaboperc);
                 $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

                 $bill_amt=0;
                $abeffect = $parta * ($beloaboperc / 100);
                $cabeffect = $cparta * ($beloaboperc / 100);

                               if ($beloAbo === 'Above') {


                                  $bill_amt = round(($parta + $abeffect), 2);
                                  $cbill_amt = round(($cparta + $cabeffect), 2);

                              } elseif ($beloAbo === 'Below') {

                                  $bill_amt = round(($parta - $abeffect), 2);
                                  $cbill_amt = round(($cparta - $cabeffect), 2);

                              }

                               // Determine whether to add a minus sign
                               if ($beloAbo === 'Below') {
                                   $abeffect = -$abeffect;
                                   $cabeffect = -$cabeffect;
                                   $beloaboperc = -$beloaboperc;
                                  }
                                  //dd($abeffect);
                                 //$part_a_ab=($parta * $beloaboperc / 100);
                                 //dd($partb);





                                 $Gstbase = round($bill_amt, 2);
                                 $cGstbase = round($cbill_amt, 2);
                                        //dd($Gstbase);

                                        $Gstamt= round($Gstbase*(18 / 100), 2);
                                        $cGstamt= round($cGstbase*(18 / 100), 2);
                                         //dd($Gstamt);

                                         $part_A_gstamt=$Gstbase + $Gstamt;
                                         $cpart_A_gstamt=$cGstbase + $cGstamt;


                                         $billamtgt = $partb + $part_A_gstamt;
                                         $cbillamtgt = $cpartb + $cpart_A_gstamt;

                           $integer_part = floor($billamtgt);  // Extract the integer part
                           $cinteger_part = floor($cbillamtgt);


                           $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                           $cdecimal_part = $cbillamtgt - $cinteger_part;
                           //dd($decimal_part);

                           $billamtro = round($decimal_part, 2);
                           $cbillamtro = round($cdecimal_part, 2);
                           //dd($rounded_decimal_part);

                      //     // Round the total bill amount
                      //     $billamtro = round($billamtgt);
                      //     //dd($rounded_billamtgt);

                      //    // Calculate the difference
                      //     //$billamtro = $rounded_billamtgt - $billamtgt;
                      //     dd($billamtro);
                          //$billamtro=0.37;
                          if ($billamtro > 0.50) {
                              // Calculate the absolute difference
                              $abs_diff = abs($billamtro);
                              $billamtro = 1 - $abs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $billamtro = -$billamtro;
                              //dd($billamtro);
                          }

                          if ($cbillamtro > 0.50) {
                              // Calculate the absolute difference
                              $cabs_diff = abs($cbillamtro);
                              $cbillamtro = 1 - $cabs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $cbillamtro = -$cbillamtro;
                              //dd($billamtro);
                          }
                           //dd($billamtro);

                           $net_amt= $billamtgt + $billamtro;
                           $cnet_amt= $cbillamtgt + $cbillamtro;
                           //dd($net_amt);

                            // Determine whether to add a minus sign


                           DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                              'part_a_amt' => $parta,
                              'part_a_gstamt' => $part_A_gstamt,
                              'part_b_amt' => $partb,
                              'gst_amt' => $Gstamt,
                              'gst_base' => $Gstbase,
                              'gross_amt' => $billgrossamt,
                              'a_b_effect' => $abeffect,
                              'bill_amt' => $bill_amt,
                              'bill_amt_gt' => $billamtgt,
                              'bill_amt_ro' => $billamtro,
                              'net_amt' => $net_amt,
                           
                              'c_part_a_amt' => $cparta,
                              'c_part_a_gstamt' => $cpart_A_gstamt,
                              'c_part_b_amt' => $cpartb,
                              'curr_grossamt' => $cbillgrossamt,
                              'c_billamt' =>  $cbill_amt,
                              'c_gstamt' => $cGstamt,
                              'c_gstbase' => $cGstbase,
                              'c_abeffect' => $cabeffect,
                              'c_billamtgt' => $cbillamtgt,
                              'c_billamtro' => $cbillamtro,
                              'c_netamt' => $cnet_amt
                           ]);

                           $billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
                  $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->paginate(5);

                  $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();

                  $bitemdata=DB::table('bil_item')->where('b_item_id' , $bitemId)->get();

                  //workdetails
    $biltemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

    $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

    $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

     $convert=new CommonHelper();

      $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
      $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
      '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($biltemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '</div></div>';

    //   $previousPage = session()->get('previous_page');
    //   // Append the route to the current URL
    //   $redirectUrl = redirect()->route('emb.workmasterdata', ['id' => $lasttbillid->t_bill_Id, 'page' => $previousPage])->getTargetUrl();
      

  DB::commit();

    return response()->json(['embdata' => $embdata, 'newmeasid' => $newmeasid , 'previousexecqty' => $previousexecqty , 'curqty' => $curqty , 'execqty' => $execqty , 'tndqty' => $tndqty , 'tndcostitem' => $tndcostitem , 'percentage' => $percentage , 'totlcostitem' => $totlcostitem , 'costdifference' => $costdifference , 'billdata' => $billdata,
    'billitemdata' => $billitemdata,
    'lasttbillid' => $lasttbillid, 'bitemdata' => $bitemdata , 'workdetail' => $workdetail ,]);
    
 
   
   } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error in Edit Measurement Box ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}


//delete the image and document in measurement
public function deleteImageOrDoc(Request $request)
{
    $imageId = $request->input('id');
    $measid = $request->input('measid');
    //dd($measid);
    // Perform your validation and security checks

    if ($imageId == 'img1' || $imageId == 'img2' || $imageId == 'img3') {
        // Delete image logic here using $measid
       // Update the corresponding photo field to null or delete the image URL
       $photoField = 'photo' . substr($imageId, -1); // Get the field name (e.g., 'photo1', 'photo2', 'photo3')

       // Update the photo field value to null for the given measurement ID
       DB::table('embs')->where('meas_id', $measid)->update([$photoField => null]);
   } elseif ($imageId == 'doc') {
       // Delete document logic here using $measid

       // Update the 'document' field value to null for the given measurement ID
       DB::table('embs')->where('meas_id', $measid)->update(['drg' => null]);
   }


    // Return success response
    return response()->json(['message' => 'Image or document deleted successfully']);
}


//excel submit for new measurement
public function excelsubmit(Request $request)
{
    try {
        // Validate the request
        $request->validate([
            'excelFile' => 'required|mimes:xls,xlsx', // Add any validation rules you need
        ]);

        // Get the uploaded file
        $uploadedFile = $request->file('excelFile');
        $timestamp = time(); // You can use this timestamp for generating unique file paths

        // Get bitemid from the request
        $bitemid = $request->input('bitem_id');

        try {
            // Use the ExcelImport class to process the Excel file
            $data = ExcelImport::process($uploadedFile, $bitemid);

            // If data is not present, return an error response
            if (empty($data)) {
                return response()->json([
                    'error' => 'No data processed from the Excel file.'
                ], 422);
            }

            // Process or save the $data array as needed
            return $data;

        } catch (\Exception $e) {
            // Handle errors that occur during the processing of the Excel file
            return response()->json([
                'error' => 'An error occurred while processing the Excel file: ' . $e->getMessage()], 500);
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Handle validation errors
        return response()->json([
            'error' => 'Validation failed: ' . $e->getMessage()], 422);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Handle model not found errors
        return response()->json([
            'error' => 'Model not found: ' . $e->getMessage()], 404);

    } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
        // Handle file not found errors
        return response()->json([
            'error' => 'File not found: ' . $e->getMessage()], 404);

    } catch (\Exception $e) {
        // Handle all other exceptions
        Log::error('Error in excelsubmit: ' . $e->getMessage());
        return response()->json([
            'error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}


//edit and delete steel measurement
public function editsteelmeas($steelid)
    {
        
         try{

        //dd('ok');
        $steeldata=DB::table('stlmeas')->where('steelid' , $steelid)->get();

        // Store the $steelid value in a session variable
              session(['steelid' => $steelid]);
        //dd($steeldata);
        $bitemid=DB::table('stlmeas')->where('steelid' , $steelid)->get()->value('b_item_id');
        //dd($bitemid);

        $billitemdata=DB::table('bil_item')->where('b_item_id' , $bitemid)->get();

        $rcmbrid=DB::table('stlmeas')->where('steelid' , $steelid)->get()->value('rc_mbr_id');

        $billmbrdata=DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->get();
        // Implement your logic to fetch and return data for editing based on $steelid
        // Return the data as a JSON response
        // Example response: return response()->json(['success' => true, 'data' => $editedData]);
        $lengthdata=DB::table('stlmeas')->select('ldiam6' , 'ldiam8' , 'ldiam10' , 'ldiam12' , 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32', 'ldiam36' , 'ldiam40')->where('steelid' , $steelid)->get();
        // If the edit operation fails or data is not available, you can return an error response.
        // Example error response: return response()->json(['success' => false, 'message' => 'Edit operation failed']);
        $photo1Urls = [];
        $photo2Urls = [];
        $photo3Urls = [];
        $docUrls = [];

        foreach ($steeldata as $row) {
            $photo1Urls[] = asset('storage/' . $row->photo1);
            $photo2Urls[] = asset('storage/' . $row->photo2);
            $photo3Urls[] = asset('storage/' . $row->photo3);
            $docUrls[] = asset('storage/' . $row->doc);
        }


           //workdetails
    $billtemdata=DB::table('bil_item')->where('b_item_id', $bitemid)->first();

    $tbillid=DB::table('bil_item')->where('b_item_id', $bitemid)->value('t_bill_id');

    $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

    $convert=new CommonHelper();

      $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
      $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
      '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees(
      $workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees(
      $billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees(
       $tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
         '</div></div>';





        return response()->json(['steeldata' => $steeldata ,
         'billitemdata' => $billitemdata ,
         'billmbrdata' => $billmbrdata ,
         'lengthdata' => $lengthdata,
         'photo1Urls' => $photo1Urls,
         'photo2Urls' => $photo2Urls,
         'photo3Urls' => $photo3Urls,
         'docUrls' => $docUrls,
         'bitemid' => $bitemid,
         'workdetail' => $workdetail,
         'tbilldata' => $tbilldata
        ]);
        
        
        
    }catch(\Exception $e)
        {
            Log::error('Error Occurr during edit steel data' .  $e->getMessage());

            return response()->json(['error' => 'An error occur during Edit steel box'. $e->getMessage()]);
        }
    }



    //update or submit steel measurement data
    public function submitsteelupdate(Request $request)
    {



             DB::beginTransaction();

                 try{

        // Retrieve the $steelid from the session
    $steelid = session('steelid');

    $bitemId=DB::table('stlmeas')->where('steelid' , $steelid)->value('b_item_id');

    $tbillid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_bill_id');
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');

        // $request->validate([
        //     'photo1steel' => 'required|image|mimes:jpeg,jpg',
        //     'photo2steel' => 'required|image|mimes:jpeg,jpg',
        //     'photo3steel' => 'required|image|mimes:jpeg,jpg',
        //     'documentssteel' => 'required|mimes:pdf,jpeg,jpg,png,xlsx,xls,doc,docx',
        // ]);
        $Length = $request->input('length');
        //dd($Length);
        // Handle Date of Measurement
        $steelmeasdate = $request->input('steelmeasdate');
//dd($steelmeasdate);



        // Handle Photo 1 upload
    //     $timestamp = now()->timestamp; // Get the current timestamp

    //     // Handle Photo 1 upload
    //     $photo1steelPath = $request->file('photo1steel')->store('photos', 'public');

    //     // Handle Photo 2 upload
    //     $photo2steelPath = $request->file('photo2steel')->store('photos', 'public');

    //     // Handle Photo 3 upload
    //     $photo3steelPath = $request->file('photo3steel')->store('photos', 'public');
    //    // dd($photo3steelPath);
    //     // Handle Documents upload
    //     $documentssteelPath = $request->file('documentssteel')->store('documents', 'public');

        // Handle additional input fields
        $barParticulars = $request->input('barParticulars');
        $noofbars = $request->input('noofbars');
        $selectedLength = $request->input('selectedLength');
        $barlength = $request->input('barlength');
        $barsrno = $request->input('barsrno');
        $steelmeasdate=$request->input('steelmeasdate');


        //$length=null;

       /// / Define an array of possible column names
    $columnNames = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40' /* Add more column names as needed */];
    // if (in_array($Length, $columnNames)) {

    //     $length=$selectedLength;


    // }

    $timestamp = now()->timestamp; // Get the current timestamp

    $photo1steelPath = null; $photo2steelPath = null; $photo3steelPath = null; $documentssteelPath = null;
$timestamp = time(); // You can use this timestamp for generating unique file paths

if ($request->hasFile('photo1steel')) {
    $photo1steelPath = $request->file('photo1steel')->store('photos', 'public');
}
if ($request->hasFile('photo2steel')) {
    $photo2steelPath = $request->file('photo2steel')->store('photos/' . $timestamp, 'public');
}
//dd( $photo2Path);

if ($request->hasFile('photo3steel')) {
    $photo3steelPath = $request->file('photo3steel')->store('photos/' . $timestamp, 'public');
}

if ($request->hasFile('documentssteel')) {
    $documentssteelPath = $request->file('documentssteel')->store('documents/' . $timestamp, 'public');
}


$columnValues = [
    'bar_particulars' => $barParticulars,
    'no_of_bars' => $noofbars,
    'bar_length' => $barlength,
    'bar_sr_no' => $barsrno,
    'date_meas' => $steelmeasdate,
    'photo1' => $photo1steelPath,
    'photo2' => $photo2steelPath,
    'photo3' => $photo3steelPath,
    'doc' => $documentssteelPath,
    'dyE_chk_dt' => $steelmeasdate,
];

// Iterate through all possible column names
foreach ($columnNames as $columnName) {
    // Check if $Length matches the current column name
    if ($Length === $columnName) {
        // If it matches, set the column value to $selectedLength
        $columnValues[$columnName] = $selectedLength;
    } else {
        // If it doesn't match, set the column value to null
        $columnValues[$columnName] = null;
    }
}

// Update the database table using the $columnValues array
DB::table('stlmeas')->where('steelid', $steelid)->update($columnValues);
$html = '' ;

    $stldata = DB::table('stlmeas')
                ->where('b_item_id', $bitemId)
                ->get();
             //dd($stldata);
               $bill_rc_data = DB::table('bill_rcc_mbr')->get();

              // dd($stldata , $bill_rc_data);

               $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
                 'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

                 foreach ($stldata as &$data) {
                   if (is_object($data)) {
                       foreach ($ldiamColumns as $ldiamColumn) {
                           if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                               $temp = $data->$ldiamColumn;
                               $data->$ldiamColumn = $data->bar_length;
                               $data->bar_length = $temp;
                              // dd($data->bar_length , $data->$ldiamColumn);
                               break; // Stop checking other ldiam columns if we found a match
                           }
                       }
                   }
               }


               $sums = array_fill_keys($ldiamColumns, 0);

               foreach ($stldata as $row) {
                   foreach ($ldiamColumns as $ldiamColumn) {
                       $sums[$ldiamColumn] += $row->$ldiamColumn;
                   }
               }//dd($stldata);
           //dd($sums);

           $bill_member = DB::table('bill_rcc_mbr')
           ->whereExists(function ($query) use ($bitemId) {
               $query->select(DB::raw(1))
                     ->from('stlmeas')
                     ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                     ->where('bill_rcc_mbr.b_item_id', $bitemId);
           })
           ->get();
           //$bill_memberdata=DB::table('rcc_mbr')->get();
           //dd($bill_member);
           // Generate the HTML content

           $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();
           //d($rc_mbr_ids);






           //dd($stldata);
               // Check if there is data for this rc_mbr_id
               // if ($stldata->isEmpty()) {
               //     continue; // Skip if there's no data
               // }


               foreach ($bill_member as $index => $member) {
                   $html .= '<div class="container-fluid">';
                   $html .= '
                   <div class="container-fluid">
               <div class="row">
                   <div class="col-md-1">
                       <div class="form-group">
                           <label for="sr_no">Sr No</label>
                           <input type="text" class="form-control" id="sr_no" name="sr_no" value="' . $member->member_sr_no . '" disabled>
                       </div>
                   </div>
                   <div class="col-md-4">
                       <div class="form-group">
                           <label for="rcc_member">RCC Member</label>
                           <select class="form-control" id="rcc_member" name="rcc_member" disabled>
                               <option value="' . $member->rc_mbr_id . '">' . $member->rcc_member . '</option>
                           </select>
                       </div>
                   </div>
                   <div class="col-md-4">
                       <div class="form-group">
                           <label for="member_particular">Member Particular</label>
                           <input type="text" class="form-control" id="member_particular" name="member_particular" value="' . $member->member_particulars . '" disabled>
                       </div>
                   </div>
                   <div class="col-md-2">
                       <div class="form-group">
                            <label for="no_of_members">No Of Members</label>
                            <input type="text" class="form-control" id="no_of_members" name="no_of_members" value="' . $member->no_of_members . '" disabled>
                       </div>
                  </div>
                  <div class="col-md-1">
                       <div class="form-group">
                          <button type="button" class="btn btn-primary btn-sm editrcmbr-button" data-rcbillid="' . $member->rc_mbr_id . '" title="EDIT RCC MEMBER"><i class="fa fa-pencil" aria-hidden="true"></i></button>
                       </div>
                  </div>
               </div>


               <div class="container-fluid">
                 <div class="col-md-12">
                       <table class="table table-striped">

                           <thead>
                               <tr>
                               <th>Sr No</th>
                               <th>Bar Particulars</th>
                               <th>No of Bars</th>
                               <th>Length of Bars</th>
                               <th>6mm</th>
                               <th>8mm</th>
                               <th>10mm</th>
                               <th>12mm</th>
                               <th>16mm</th>
                               <th>20mm</th>
                               <th>25mm</th>
                               <th>28mm</th>
                               <th>32mm</th>
                               <th>36mm</th>
                               <th>40mm</th>
                               <th>Date</th>
                               <th>Action</th>
                               </tr>
                           </thead>
                           <tbody>';

                           foreach ($stldata as $bar) {
                               if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                               //dd($bar);// Assuming the bar data is within a property like "bar_data"
                               $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                               $html .= '<tr>
                                           <td>'. $bar->bar_sr_no .'</td>
                                           <td>'. $bar->bar_particulars .'</td>
                                           <td>'. $bar->no_of_bars .'</td>
                                           <td>'. $bar->bar_length .'</td>
                                           <td>'. $bar->ldiam6 .'</td>
                                           <td>'. $bar->ldiam8 .'</td>
                                           <td>'. $bar->ldiam10 .'</td>
                                           <td>'. $bar->ldiam12 .'</td>
                                           <td>'. $bar->ldiam16 .'</td>
                                           <td>'. $bar->ldiam20 .'</td>
                                           <td>'. $bar->ldiam25 .'</td>
                                           <td>'. $bar->ldiam28 .'</td>
                                           <td>'. $bar->ldiam32 .'</td>
                                           <td>'. $bar->ldiam36 .'</td>
                                           <td>'. $bar->ldiam40 .'</td>
                                           <td>'. $formattedDateMeas .'</td>
                                           <td>
                                           <button type="button" class="btn btn-primary btn-sm edit-button"  data-steelid="' . $bar->steelid . '" title="EDIT STEEL MEASUREMENT"> <i class="fa fa-pencil" style="color:white;"></i></button>
                                           <button type="button" class="btn btn-danger btn-sm delete-button" data-steelid="' . $bar->steelid . '" title="DELETE STEEL MEASUREMENT"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                       </td>
                                           </tr>';
                           }
                       }

                       $html .= '
                           </tbody>
                       </table>
                   </div>
               </div>
               </div>';

               // Add a row for the totals for the last member
               if ($index === count($bill_member) - 1) {
                $html .= '
                <div><h4>TOTAL LENGTH</h4></div>
               <div class="container-fluid"  style="max-height: 1000px; max-width: 1500px;">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped">
                        <thead>
                            <tr>
                            <th></th>
                            <th colspan="13"></th>
                            <th>Length</th>
                            <th>6mm</th>
                            <th>8mm</th>
                            <th>10mm</th>
                            <th>12mm</th>
                            <th>16mm</th>
                            <th>20mm</th>
                            <th>25mm</th>
                            <th>28mm</th>
                            <th>32mm</th>
                            <th>36mm</th>
                            <th>40mm</th>
                            <th colspan="8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                                <th>Total</th>
                                <td>' . $sums['ldiam6'] . '</td>
                                <td>' . $sums['ldiam8'] . '</td>
                                <td>' . $sums['ldiam10'] . '</td>
                                <td>' . $sums['ldiam12'] . '</td>
                                <td>' . $sums['ldiam16'] . '</td>
                                <td>' . $sums['ldiam20'] . '</td>
                                <td>' . $sums['ldiam25'] . '</td>
                                <td>' . $sums['ldiam28'] . '</td>
                                <td>' . $sums['ldiam32'] . '</td>
                                <td>' . $sums['ldiam36'] . '</td>
                                <td>' . $sums['ldiam40'] . '</td>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>';
            }

               $html .= '</div>'; // Close the container
           }

           $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('item_id');

           if (in_array(substr($itemid, -6), ["003351", "003878"]))
           {
                $sec_type="HCRM/CRS Bar";
           }
        else{
                $sec_type="TMT Bar";
            }

            $selectedlength = [];
            $size=null;
            $sr_no = 0; // Initialize the serial number
            $totalweight = 0; // Initialize the total weight

            $html .= '<div><h4>TOTAL WEIGHT</h4></div> <div class="container-fluid">
     <div class="row">
         <div class="col-md-12">
               <table class="table table-striped" style="width: 100%;">
                 <thead>
                     <tr>
                         <th>Sr No</th>
                         <th>Particulars</th>
                         <th>Formula</th>
                         <th>Weight</th>
                     </tr>
                 </thead>
                 <tbody>';

                 $distinctStlDate = DB::table('stlmeas')
                 ->select('date_meas') // Add other columns as needed
                 ->where('b_item_id', $bitemId)
                 ->groupBy('date_meas')
                 ->orderBy('date_meas', 'asc') // Optional: Order by date in descending order
                 ->get();


                 DB::table('embs')->where('b_item_id', $bitemId)->delete();


                 $Size=null;
                //dd($sums);
                 foreach($distinctStlDate as $date)
                {
     // //dd($date);
     $barlenghtl6=0;
                 $barlenghtl8=0;
                 $barlenghtl10=0;
                 $barlenghtl12=0;
                 $barlenghtl16=0;
                 $barlenghtl20=0;
                 $barlenghtl25=0;
                 $barlenghtl28=0;
                 $barlenghtl32=0;
                 $barlenghtl36=0;
                 $barlenghtl40=0;
                 $barlenghtl45=0;
                                     $steelmeasdata=DB::table('stlmeas')->where('b_item_id', $bitemId)->where('date_meas', $date->date_meas)->get();

                                   //dd($steelmeasdata);

                                     foreach ($steelmeasdata as $row) {
     //dd($row);
                                       $measurement=DB::table('stlmeas')->select('ldiam6','ldiam8' , 'ldiam10' , 'ldiam12' , 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40', 'ldiam45')->where('steelid' , $row->steelid)->first();

                                       $keyValuePairs = (object)[];

                                       foreach ($measurement as $column => $value) {
                                           if (!is_null($value)) {
                                               $keyValuePairs->$column = $value;
                                           }
                                       }
                                       //dd(key($keyValuePairs));
                                     //   foreach ($row as $key => $value) {
                                     //     }
                                         //dd($row);
                                         switch (key($keyValuePairs)) {
                                             case 'ldiam6':
                                                 $Size = "6 mm dia";
                                                 $barlenghtl6 += $row->bar_length;
                                                 break;
                                             case 'ldiam8':
                                                 $Size = "8 mm dia";
                                                 $barlenghtl8 += $row->bar_length;
                                                 break;
                                             case 'ldiam10':
                                                 $Size = "10 mm dia";
                                                 $barlenghtl10 += $row->bar_length;
                                                 break;
                                             case 'ldiam12':
                                                 $Size = "12 mm dia";
                                                 $barlenghtl12 += $row->bar_length;
                                                 break;
                                             case 'ldiam16':
                                                 $Size = "16 mm dia";
                                                 $barlenghtl16 += $row->bar_length;
                                                 break;
                                             case 'ldiam20':
                                                 $Size = "20 mm dia";
                                                 $barlenghtl20 += $row->bar_length;
                                                 break;
                                             case 'ldiam25':
                                                 $Size = "25 mm dia";
                                                 $barlenghtl25 += $row->bar_length;
                                                 break;
                                             case 'ldiam28':
                                                 $Size = "28 mm dia";
                                                 $barlenghtl28 += $row->bar_length;
                                                 break;
                                             case 'ldiam32':
                                                 $Size = "32 mm dia";
                                                 $barlenghtl32 += $row->bar_length;
                                                 break;
                                             case 'ldiam36':
                                                 $Size = "36 mm dia";
                                                 $barlenghtl36 += $row->bar_length;
                                                 break;
                                             case 'ldiam40':
                                                 $Size = "40 mm dia";
                                                 $barlenghtl40 += $row->bar_length;
                                                 break;
                                             case 'ldiam45':
                                                 $Size = "45 mm dia";
                                                 $barlenghtl45 += $row->bar_length;
                                                 break;
                                         }
                                     }//dd($stldata);



                                                                         $excelimportclass = new ExcelImport();


                                     if($barlenghtl6 > 0)
                                     {
     
                                        $size="6 mm dia";
                                         
                                        $sr_no++;
                                        //function is created 
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl6 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html
         //dd($tmtdata);           
                                                  
                                     }
     
     
     
     
     
                                 
                                
                                     if($barlenghtl8 > 0)
                                     {
                                             $size="8 mm dia";
     
                                             $sr_no++;
                                             //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl8 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
         $html .= $tmtdata['html']; // Accessing html
                            
                                                  
     
                                     }
                                  
                                     if($barlenghtl10 > 0)
                                     {
                                             $size="10 mm dia";
                                            
                                             $sr_no++;
                                             //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl10 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html
                                                  
     
                                     }
                                     if($barlenghtl12 > 0)
                                     {
                                             $size="12 mm dia";
     
                                             $sr_no++;
                                             //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl12 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html
     
                                     }
                                     if($barlenghtl16 > 0)
                                     {
                                             $size="16 mm dia";
     
                                             $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl16 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html                                                                          
     
                                     }
     
                                    
                                   
                                     if($barlenghtl20 > 0)
                                     {
                                             $size="20 mm dia";
     
                                             $sr_no++;
                                             //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl20 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html
                     
                                     }
     
                                     if($barlenghtl25 > 0)
                                     {
                                             $size="25 mm dia";
     
                                             $sr_no++;
                                               //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl25 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html
                                                                       
                                     }
                                    
                                   
                                     if($barlenghtl28 > 0)
                                     {
                                             $size="28 mm dia";
     
                                             $sr_no++;
     
     
     
                                             //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl28 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html
                                                      
                     
                                     }
                                   
                                    
                                     if($barlenghtl32 > 0)
                                     {
                                             $size="32 mm dia";
     
                                             $sr_no++;
                                                 //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl32 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html
                                                      
                     
                                     }
                                   
                                    
                                    
                                     if($barlenghtl36 > 0)
                                     {
                                             $size="36 mm dia";
     
                                             $sr_no++;
                                                //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl36 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html
                     
                                     }
     
     
                                     if($barlenghtl40 > 0)
                                     {
                                             $size="40 mm dia";
     
                                             $sr_no++;
                                             //function call for the total weight and emb table in that insert steel data
                                     $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl40 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                     $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                     $html .= $tmtdata['html']; // Accessing html
                                                                      
                                     }
                                    // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];





                                 }


         $html .= '<tr style="background-color: #333; color: #fff;">
                     <td></td>
                     <td><strong>Total Weight:</strong></td>
                     <td></td>
                     <td><strong>' . $totalweight . ' M.T</strong></td>
                   </tr>';
                   $html .= '</tbody>
                   </table>
               </div>
           </div>
       </div>';




            $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

       $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

       $previousTBillId = DB::table('bills')
       ->where('work_id' , $workid)
       ->where('t_bill_id', '<', $tbillid) // Add your condition here
       ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
       ->limit(1) // Limit the result to 1 row
       ->value('t_bill_id');
           //dd($previousTBillId);

           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

           $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


           $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
           //dd($previousexecqty);

           if (is_null($previousexecqty)) {
               $previousexecqty = 0;
           }

$curqty = number_format(round(DB::table('embs')->where('b_item_id', $bitemId)->where('notforpayment', 0)->sum('qty'), $Qtydec), 3, '.', '');
           //dd($previousexecqty);
           //dd($curqty);



$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
           //dd($execqty);
           //dd($execqty);

           $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                $curamt=$curqty*$billrt;

           $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

               $bitemamt=$curamt+$previousamt;

           DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

               'exec_qty' => $execqty,
               'cur_qty' => $curqty,
               'prv_bill_qty' => $previousexecqty,
               'cur_amt' => $curamt,
               'b_item_amt' => $bitemamt,
           ]);


           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
           $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
        //   $tndqty=round($tnditem->tnd_qty , 3);
        $tndqty=$tnditem->tnd_qty;
        
         $amountconvert=new CommonHelper();
                
               
        
           $tndcostitem=$tnditem->t_item_amt;
           //dd($tndqty);
           $percentage=round(($execqty / $tndqty)*100 , 2);
           //dd($percentage);
           $totlcostitem=round($billrt*$execqty , 2);

           $costdifference= round($tndcostitem-$totlcostitem , 2);
           
           
             $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);

       // dd($$html);
                // Check if this is the last member in the list

                $parta = 0; // Initialize the sum for matched conditions
                 $partb = 0; // Initialize the sum for unmatched conditions

                 $cparta = 0; // Initialize the sum for matched conditions
                 $cpartb = 0; // Initialize the sum for unmatched conditions

                 $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }



                 $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

                 $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
                 //dd($billgrossamt);
                 $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
                 //dd($beloaboperc);
                 $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

                 $bill_amt=0;
                $abeffect = $parta * ($beloaboperc / 100);
                $cabeffect = $cparta * ($beloaboperc / 100);

                               if ($beloAbo === 'Above') {


                                  $bill_amt = round(($parta + $abeffect), 2);
                                  $cbill_amt = round(($cparta + $cabeffect), 2);

                              } elseif ($beloAbo === 'Below') {

                                  $bill_amt = round(($parta - $abeffect), 2);
                                  $cbill_amt = round(($cparta - $cabeffect), 2);

                              }

                               // Determine whether to add a minus sign
                               if ($beloAbo === 'Below') {
                                   $abeffect = -$abeffect;
                                   $cabeffect = -$cabeffect;
                                   $beloaboperc = -$beloaboperc;
                                  }
                                  //dd($abeffect);
                                 //$part_a_ab=($parta * $beloaboperc / 100);
                                 //dd($partb);





                                 $Gstbase = round($bill_amt, 2);
                                 $cGstbase = round($cbill_amt, 2);
                                        //dd($Gstbase);

                                        $Gstamt= round($Gstbase*(18 / 100), 2);
                                        $cGstamt= round($cGstbase*(18 / 100), 2);
                                         //dd($Gstamt);

                                         $part_A_gstamt=$Gstbase + $Gstamt;
                                         $cpart_A_gstamt=$cGstbase + $cGstamt;


                                         $billamtgt = $partb + $part_A_gstamt;
                                         $cbillamtgt = $cpartb + $cpart_A_gstamt;

                           $integer_part = floor($billamtgt);  // Extract the integer part
                           $cinteger_part = floor($cbillamtgt);


                           $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                           $cdecimal_part = $cbillamtgt - $cinteger_part;
                           //dd($decimal_part);

                           $billamtro = round($decimal_part, 2);
                           $cbillamtro = round($cdecimal_part, 2);
                           //dd($rounded_decimal_part);

                      //     // Round the total bill amount
                      //     $billamtro = round($billamtgt);
                      //     //dd($rounded_billamtgt);

                      //    // Calculate the difference
                      //     //$billamtro = $rounded_billamtgt - $billamtgt;
                      //     dd($billamtro);
                          //$billamtro=0.37;
                          if ($billamtro > 0.50) {
                              // Calculate the absolute difference
                              $abs_diff = abs($billamtro);
                              $billamtro = 1 - $abs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $billamtro = -$billamtro;
                              //dd($billamtro);
                          }

                          if ($cbillamtro > 0.50) {
                              // Calculate the absolute difference
                              $cabs_diff = abs($cbillamtro);
                              $cbillamtro = 1 - $cabs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $cbillamtro = -$cbillamtro;
                              //dd($billamtro);
                          }
                           //dd($billamtro);

                           $net_amt= $billamtgt + $billamtro;
                           $cnet_amt= $cbillamtgt + $cbillamtro;
                           //dd($net_amt);

                            // Determine whether to add a minus sign


                           DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                              'part_a_amt' => $parta,
                              'part_a_gstamt' => $part_A_gstamt,
                              'part_b_amt' => $partb,
                              'gst_amt' => $Gstamt,
                              'gst_base' => $Gstbase,
                              'gross_amt' => $billgrossamt,
                              'a_b_effect' => $abeffect,
                              'bill_amt' => $bill_amt,
                              'bill_amt_gt' => $billamtgt,
                              'bill_amt_ro' => $billamtro,
                              'net_amt' => $net_amt,

                              'c_part_a_amt' => $cparta,
                              'c_part_a_gstamt' => $cpart_A_gstamt,
                              'c_part_b_amt' => $cpartb,
                              'curr_grossamt' => $cbillgrossamt,
                              'c_billamt' =>  $cbill_amt,
                              'c_gstamt' => $cGstamt,
                              'c_gstbase' => $cGstbase,
                              'c_abeffect' => $cabeffect,
                              'c_billamtgt' => $cbillamtgt,
                              'c_billamtro' => $cbillamtro,
                              'c_netamt' => $cnet_amt
                           ]);

                          $html .= '

                          <div class="row mt-3">
                               <div class="col-md-3 offset-md-9">
                                   <div class="form-group">
                                       <label for="currentQty" class="font-weight-bold">Current Quantity:</label>
                                       <input type="text" class="form-control" id="currentQty{{ $emb3->b_item_id }}" name="currentQty" value="' .$curqty. '" disabled>
                                   </div>
                               </div>
                           </div>


                          <div class="row mt-3">
                            <div class="col-md-3 offset-md-9">
                                  <div class="form-group" >
                                      <label for="previousQty" class="font-weight-bold">Previous bill Quantity:</label>
                                      <input type="text" class="form-control" id="previousQty{{ $emb3->b_item_id }}" name="previousQty" value="' .$previousexecqty. '"  disabled>
                                  </div>
                              </div>
                          </div>



                          <div class="row mt-3">
                          <div class="col-md-3 offset-md-3">
                              <div class="form-group">
                                  <label for="tndqty" class="font-weight-bold">Tender Quantity Of Item:</label>
                                  <input type="text" class="form-control" id="tndqty{{ $emb3->b_item_id }}" name="tndqty" value="' .$tndqty. '" disabled>
                              </div>
                          </div>
                          <div class="col-md-3">
                              <div class="form-group">
                                  <label for="percentage" class="font-weight-bold">Percentage Utilised:</label>
                                  <input type="text" class="form-control" id="percentage{{ $emb3->b_item_id }}" name="percentage" value="' .$percentage. '" disabled>
                              </div>
                          </div>
                          <div class="col-md-3">
                              <div class="form-group">
                                  <label for="totalQty" class="font-weight-bold">Total Uptodate Quantity:</label>
                                  <input type="text" class="form-control" id="totalQty{{ $emb3->b_item_id }}" name="totalQty" value="' .$execqty. '" disabled>
                              </div>
                          </div>
                      </div>

                             <div class="row mt-3"  >
                             <div class="col-md-3 offset-md-3">
                                 <div class="form-group">
                                   <label for="tndcost" class="font-weight-bold">Tender Cost Of Item:</label>
                                   <input type="text" class="form-control" id="tndcost{{ $emb3->b_item_id }}" name="tndcost" value="' .$tndcostitem. '" disabled>
                                 </div>
                               </div>
                               <div class="col-md-3">
                                 <div class="form-group">
                                   <label for="costdifference" class="font-weight-bold">Excess/Saving:</label>
                                   <input type="text" class="form-control" id="costdifference{{ $emb3->b_item_id }}" name="costdifference" value="' .$costdifference. '" disabled>
                                 </div>
                               </div>
                               <div class="col-md-3">
                                 <div class="form-group">
                                   <label for="totalcost" class="font-weight-bold">Total Uptodate Amount:</label>
                                    <input type="text" class="form-control" id="totalcost{{ $emb3->b_item_id }}" name="totalcost" value="' .$totlcostitem. '" disabled>
                                 </div>
                               </div>
                             </div>';


//dd($billgrossamt);
$billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
                  $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->paginate(5);

                  $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();



                   //workdetails
    $biltemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

    $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

    $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

     $convert=new CommonHelper();

      $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
      $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
      '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($biltemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '</div></div>';
      
      

        DB::commit();
        
        

    $itemdata=DB::table('bil_item')->where('b_item_id' , $bitemId)->get();
                       // Check if this is the last member in the list
                 return response()->json(['html' => $html ,  'billdata' => $billdata,
                 'billitemdata' => $billitemdata,
                 'lasttbillid' => $lasttbillid,
                 'itemdata' => $itemdata,
                 'workdetail' => $workdetail
         ]);
         
         
                 } catch (\Exception $e) {

            DB::rollback();

            Log::error('Error in editmbdata: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

              }

    //steel new measurement by manually data
    public function steelmanualnew(Request $request)
    {
        
         DB::beginTransaction();

        try{
            
            
        $bitemId = $request->input('btemid');
        $membersrno = $request->input('sr_no');
        $rccmember = $request->input('rcc_member');
        $meberparticulars = $request->input('member_particular');
        $noofmemb = $request->input('no_of_members');
        $barsrno = $request->input('barsrno');
        //dd($barsrno);
        $barparticulars = $request->input('barParticulars');
        $noofbars = $request->input('noofbars');
        $lengthDropdown = $request->input('lengthDropdown');
        //dd($lengthDropdown);
        $selectedLength = $request->input('selectedLength');
        $barlength = $request->input('barlength');
        $steelmeasdate = $request->input('steelmeasdate');

        $html = '';
        //dd($steelmeasdate);
        //$date= Date::excelToDateTimeObject(intval($steelmeasdate))->format('Y-m-d');

        $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

        $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

        $measdtfrom=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_from');
                       //dd($measdtfrom);
         $measdtupto=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_upto');
         //dd($measdtupto);
         if ( $steelmeasdate >= $measdtfrom && $steelmeasdate <= $measdtupto) {

            $previoussteelid=DB::table('stlmeas')->where('b_item_id', '=', $bitemId)->orderby('steelid', 'desc')->first('steelid');
           // dd($previoussteelid);
            //dd( $previousmeasid);
            if ($previoussteelid) {
                $previousstld = $previoussteelid->steelid; // Convert object to string
                // Increment the last four digits of the previous meas_id
                 $lastFourDigits = intval(substr($previousstld, -4));
                 $newLastFourDigits = str_pad(($lastFourDigits + 1), 4, '0', STR_PAD_LEFT);
                 $newsteelid = $bitemId.$newLastFourDigits;
                 //dd($newmeasid);
           } else {
               // If no previous meas_id, start with bitemid.0001
               $newsteelid = $bitemId.'0001';
               //dd($newsteelid);
           }

           $rcmbrid = DB::table('bill_rcc_mbr')->where('b_item_id', '=', $bitemId)->where('rcc_member' , $rccmember)->where('member_particulars' , $meberparticulars)->first('rc_mbr_id');
           //dd($bitemId);

           if ($rcmbrid) {
            // If no previous meas_id, start with bitemid.0001
            $newrcmbrid = $rcmbrid->rc_mbr_id; // Access rc_mbr_id property
            //dd($newrcmbrid);
      } else {


       $previousrcmbrid = DB::table('bill_rcc_mbr')->where('b_item_id', '=', $bitemId)->orderby('rc_mbr_id' , 'desc')->first('rc_mbr_id');

          // Increment the last four digits of the previous meas_id

          if($previousrcmbrid)
          {

           $previousrcid = $previousrcmbrid->rc_mbr_id; // Convert object to string

           $lastFiveDigits = intval(substr($previousrcid, -5));
           $newLastFiveDigits = str_pad(($lastFiveDigits + 1), 5, '0', STR_PAD_LEFT);
           $newrcmbrid = $bitemId.$newLastFiveDigits;
          }
          else
          {
           $newrcmbrid = $bitemId.'00001';
          }
           //dd($newrcmbrid);

      }

      if($membersrno)
             {
             if ($rcmbrid) {
                // If $rcmbrid is not null, do not insert 'rc_mbr_id' in the insert query

            } else {
                // If $rcmbrid is null, insert 'rc_mbr_id' in the insert query

                //dd($membersrno);
                DB::table('bill_rcc_mbr')->insert([
                    'work_id' => $workid,
                    't_bill_id' => $tbillid,
                    'b_item_id' => $bitemId,
                    'rc_mbr_id' => $newrcmbrid, // Insert 'rc_mbr_id'
                    'member_sr_no' => $membersrno,
                    'rcc_member' => $rccmember,
                    'member_particulars' => $meberparticulars,
                    'no_of_members' => $noofmemb,
                ]);
            }

          }


        $columnNames = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40' /* Add more column names as needed */];

        $lengthDropdown = $request->input('lengthDropdown');

        $selectedLength = $request->input('selectedLength');

        $preferredLength = null;
        $l6   = 'ldiam6';
        $l8   = 'ldiam8' ;
        $l10  = 'ldiam10';
        $l12 =  'ldiam12';
        $l16 =  'ldiam16';
        $l20 =  'ldiam20';
        $l25 =  'ldiam25';
        $l28 =  'ldiam28';
        $l32 =   'ldiam32';
        $l36 =   'ldiam36';
        $l40 =   'ldiam40';

        $l6m   = null;
        $l8m   = null;
        $l10m  = null;
        $l12m =  null;
        $l16m =  null;
        $l20m =  null;
        $l25m =  null;
        $l28m =  null;
        $l32m =  null;
        $l36m =  null;
        $l40m =  null;
        //dd($lengthDropdown);
        //dd($l32);

                      if ($l6 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l6m = $selectedLength;
                        } elseif ($l8 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l8m = $selectedLength;
                        } elseif ($l10 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l10m = $selectedLength;
                        }
                         elseif ($l12 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l12m = $selectedLength;
                        }
                          elseif ($l16 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l16m = $selectedLength;
                        } elseif ($l20 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l20m = $selectedLength;
                            //dd($l20m);
                        } elseif ($l25 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l25m = $selectedLength;
                        }
                        elseif ($l28 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l28m = $selectedLength;
                           // dd($l28m);
                        }
                           elseif ($l32 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l32m = $selectedLength;
                            //dd($l32m);
                        }
                          elseif ($l36 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l36m = $selectedLength;
                        } elseif ($l40 === $lengthDropdown) {
                            $preferredLength = $selectedLength;
                            $l40m = $selectedLength;
                         }


                         if ($preferredLength !== null) {
                            // Calculate bar length using the preferred value
                                   $barlength = $noofmemb * $noofbars * $preferredLength;
                                  // dd($barlength);
                                   }
     // dd($barlength);
     //dd($barsrno);
                                   if($barsrno)  {

                                    DB::table('stlmeas')->insert([


                                        'work_id' => $workid,
                                        't_bill_id' => $tbillid,
                                        'b_item_id' => $bitemId,
                                        'steelid' => $newsteelid,
                                        'rc_mbr_id' => $newrcmbrid,
                                        'bar_sr_no' => $barsrno,
                                        'bar_particulars' => $barparticulars,
                                        'no_of_bars' => $noofbars,
                                        'ldiam6' => $l6m,
                                        'ldiam8' => $l8m,
                                         'ldiam10' => $l10m,
                                        'ldiam12' => $l12m,
                                        'ldiam16' => $l16m,
                                        'ldiam20' => $l20m,
                                        'ldiam25' => $l25m,
                                        'ldiam28' => $l28m,
                                        'ldiam32' => $l32m,
                                        'ldiam36' => $l36m,
                                        'ldiam40' => $l40m,
                                        'date_meas' => $steelmeasdate,
                                        'bar_length' => $barlength,
                                        'dyE_chk_dt' => $steelmeasdate,


                                    ]);

                                }

         }
         $stldata = DB::table('stlmeas')
         ->where('b_item_id', $bitemId)
         ->get();

//dd($stldata);
         $bill_rc_data = DB::table('bill_rcc_mbr')->get();

         // dd($stldata , $bill_rc_data);

          $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

            foreach ($stldata as &$data) {
              if (is_object($data)) {
                  foreach ($ldiamColumns as $ldiamColumn) {
                      if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                          $temp = $data->$ldiamColumn;
                          $data->$ldiamColumn = $data->bar_length;
                          $data->bar_length = $temp;
                         // dd($data->bar_length , $data->$ldiamColumn);
                          break; // Stop checking other ldiam columns if we found a match
                      }
                  }
              }
          }


          $sums = array_fill_keys($ldiamColumns, 0);

          foreach ($stldata as $row) {
              foreach ($ldiamColumns as $ldiamColumn) {
                  $sums[$ldiamColumn] += $row->$ldiamColumn;
              }
          }//dd($stldata);
      //dd($sums);

      $bill_member = DB::table('bill_rcc_mbr')
      ->whereExists(function ($query) use ($bitemId) {
          $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemId);
      })
      ->get();

      //dd($sums);
      //$bill_memberdata=DB::table('rcc_mbr')->get();
      //dd($bill_member);
      // Generate the HTML content

      $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();
      //d($rc_mbr_ids);






      //dd($stldata);
          // Check if there is data for this rc_mbr_id
          // if ($stldata->isEmpty()) {
          //     continue; // Skip if there's no data
          // }


          foreach ($bill_member as $index => $member) {
              $html .= '<div class="container-fluid">';
              $html .= '
              <div class="container-fluid">
          <div class="row">
              <div class="col-md-1">
                  <div class="form-group">
                      <label for="sr_no">Sr No</label>
                      <input type="text" class="form-control" id="sr_no" name="sr_no" value="' . $member->member_sr_no . '" disabled>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="form-group">
                      <label for="rcc_member">RCC Member</label>
                      <select class="form-control" id="rcc_member" name="rcc_member" disabled>
                          <option value="' . $member->rc_mbr_id . '">' . $member->rcc_member . '</option>
                      </select>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="form-group">
                      <label for="member_particular">Member Particular</label>
                      <input type="text" class="form-control" id="member_particular" name="member_particular" value="' . $member->member_particulars . '" disabled>
                  </div>
              </div>
              <div class="col-md-2">
                  <div class="form-group">
                       <label for="no_of_members">No Of Members</label>
                       <input type="text" class="form-control" id="no_of_members" name="no_of_members" value="' . $member->no_of_members . '" disabled>
                  </div>
             </div>
             <div class="col-md-1">
             <div class="form-group">
                <button type="button" class="btn btn-primary btn-sm editrcmbr-button" data-rcbillid="' . $member->rc_mbr_id . '" title="EDIT RCC MEMBER"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button>
             </div>
        </div>
      </div>


      <div class="container-fluid">
      <div class="col-md-12">
                  <table class="table table-striped">

                      <thead>
                          <tr>
                          <th>Sr No</th>
                          <th>Bar Particulars</th>
                          <th>No of Bars</th>
                          <th>Length of Bars</th>
                          <th>6mm</th>
                          <th>8mm</th>
                          <th>10mm</th>
                          <th>12mm</th>
                          <th>16mm</th>
                          <th>20mm</th>
                          <th>25mm</th>
                          <th>28mm</th>
                          <th>32mm</th>
                          <th>36mm</th>
                          <th>40mm</th>
                          <th>Date</th>
                          <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>';

                      foreach ($stldata as $bar) {
                          if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                          //dd($bar);// Assuming the bar data is within a property like "bar_data"
                          $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                          $html .= '<tr>
                                      <td>'. $bar->bar_sr_no .'</td>
                                      <td>'. $bar->bar_particulars .'</td>
                                      <td>'. $bar->no_of_bars .'</td>
                                      <td>'. $bar->bar_length .'</td>
                                      <td>'. $bar->ldiam6 .'</td>
                                      <td>'. $bar->ldiam8 .'</td>
                                      <td>'. $bar->ldiam10 .'</td>
                                      <td>'. $bar->ldiam12 .'</td>
                                      <td>'. $bar->ldiam16 .'</td>
                                      <td>'. $bar->ldiam20 .'</td>
                                      <td>'. $bar->ldiam25 .'</td>
                                      <td>'. $bar->ldiam28 .'</td>
                                      <td>'. $bar->ldiam32 .'</td>
                                      <td>'. $bar->ldiam36 .'</td>
                                      <td>'. $bar->ldiam40 .'</td>
                                      <td>'. $formattedDateMeas .'</td>
                                      <td>
                                      <button type="button" class="btn btn-primary btn-sm edit-button" data-steelid="' . $bar->steelid . '" title="EDIT STEEL MEASUREMENT"> <i class="fa fa-pencil" style="color:white;"></i></button>
                                      <button type="button" class="btn btn-danger btn-sm delete-button" data-steelid="' . $bar->steelid . '" title="DELETE STEEL MEASUREMENT"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                  </td>
                                      </tr>';
                      }
                  }

                  $html .= '
                      </tbody>
                  </table>
              </div>
          </div>
          </div>';

          // Add a row for the totals for the last member
          if ($index === count($bill_member) - 1) {
            $html .= '
            <div><h4>TOTAL LENGTH</h4></div>
           <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-striped">
                    <thead>
                        <tr>
                        <th></th>
                        <th colspan="13"></th>
                        <th>Length</th>
                        <th>6mm</th>
                        <th>8mm</th>
                        <th>10mm</th>
                        <th>12mm</th>
                        <th>16mm</th>
                        <th>20mm</th>
                        <th>25mm</th>
                        <th>28mm</th>
                        <th>32mm</th>
                        <th>36mm</th>
                        <th>40mm</th>
                        <th colspan="8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                            <th>Total</th>
                            <td>' . $sums['ldiam6'] . '</td>
                            <td>' . $sums['ldiam8'] . '</td>
                            <td>' . $sums['ldiam10'] . '</td>
                            <td>' . $sums['ldiam12'] . '</td>
                            <td>' . $sums['ldiam16'] . '</td>
                            <td>' . $sums['ldiam20'] . '</td>
                            <td>' . $sums['ldiam25'] . '</td>
                            <td>' . $sums['ldiam28'] . '</td>
                            <td>' . $sums['ldiam32'] . '</td>
                            <td>' . $sums['ldiam36'] . '</td>
                            <td>' . $sums['ldiam40'] . '</td>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>';
        }
          $html .= '</div>'; // Close the container


      }
      $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('item_id');

      if (in_array(substr($itemid, -6), ["003351", "003878"]))
      {
           $sec_type="HCRM/CRS Bar";
      }
   else{
           $sec_type="TMT Bar";
       }

       DB::table('embs')->where('b_item_id', '=' , $bitemId)->delete();


       $selectedlength = [];
       $size=null;
       $sr_no = 0; // Initialize the serial number
       $totalweight = 0; // Initialize the total weight

       $html .= '<div><h4>TOTAL WEIGHT</h4></div> <div class="container-fluid">
<div class="row">
    <div class="col-md-12">
          <table class="table table-striped" style="width: 100%;">
            <thead>
                <tr>
                    <th>Sr No</th>
                    <th>Particulars</th>
                    <th>Formula</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>';




$distinctStlDate = DB::table('stlmeas')
            ->select('date_meas') // Add other columns as needed
            ->where('b_item_id', $bitemId)
            ->groupBy('date_meas')
            ->orderBy('date_meas', 'asc') // Optional: Order by date in descending order
            ->get();

            DB::table('embs')->where('b_item_id', $bitemId)->delete();



            $Size=null;
           //dd($sums);
            foreach($distinctStlDate as $date)
           {
// //dd($date);
$barlenghtl6=0;
            $barlenghtl8=0;
            $barlenghtl10=0;
            $barlenghtl12=0;
            $barlenghtl16=0;
            $barlenghtl20=0;
            $barlenghtl25=0;
            $barlenghtl28=0;
            $barlenghtl32=0;
            $barlenghtl36=0;
            $barlenghtl40=0;
            $barlenghtl45=0;
                                $steelmeasdata=DB::table('stlmeas')->where('b_item_id', $bitemId)->where('date_meas', $date->date_meas)->get();

                              //dd($steelmeasdata);

                                foreach ($steelmeasdata as $row) {
//dd($row);
                                  $measurement=DB::table('stlmeas')->select('ldiam6','ldiam8' , 'ldiam10' , 'ldiam12' , 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40', 'ldiam45')->where('steelid' , $row->steelid)->first();

                                  $keyValuePairs = (object)[];

                                  foreach ($measurement as $column => $value) {
                                      if (!is_null($value)) {
                                          $keyValuePairs->$column = $value;
                                      }
                                  }
                                  //dd(key($keyValuePairs));
                                //   foreach ($row as $key => $value) {
                                //     }
                                    //dd($row);
                                    switch (key($keyValuePairs)) {
                                        case 'ldiam6':
                                            $Size = "6 mm dia";
                                            $barlenghtl6 += $row->bar_length;
                                            break;
                                        case 'ldiam8':
                                            $Size = "8 mm dia";
                                            $barlenghtl8 += $row->bar_length;
                                            break;
                                        case 'ldiam10':
                                            $Size = "10 mm dia";
                                            $barlenghtl10 += $row->bar_length;
                                            break;
                                        case 'ldiam12':
                                            $Size = "12 mm dia";
                                            $barlenghtl12 += $row->bar_length;
                                            break;
                                        case 'ldiam16':
                                            $Size = "16 mm dia";
                                            $barlenghtl16 += $row->bar_length;
                                            break;
                                        case 'ldiam20':
                                            $Size = "20 mm dia";
                                            $barlenghtl20 += $row->bar_length;
                                            break;
                                        case 'ldiam25':
                                            $Size = "25 mm dia";
                                            $barlenghtl25 += $row->bar_length;
                                            break;
                                        case 'ldiam28':
                                            $Size = "28 mm dia";
                                            $barlenghtl28 += $row->bar_length;
                                            break;
                                        case 'ldiam32':
                                            $Size = "32 mm dia";
                                            $barlenghtl32 += $row->bar_length;
                                            break;
                                        case 'ldiam36':
                                            $Size = "36 mm dia";
                                            $barlenghtl36 += $row->bar_length;
                                            break;
                                        case 'ldiam40':
                                            $Size = "40 mm dia";
                                            $barlenghtl40 += $row->bar_length;
                                            break;
                                        case 'ldiam45':
                                            $Size = "45 mm dia";
                                            $barlenghtl45 += $row->bar_length;
                                            break;
                                    }
                                }//dd($stldata);



                                   $excelimportclass = new ExcelImport();


                                      if($barlenghtl6 > 0)
                                      {
      
                                         $size="6 mm dia";
                                          
                                         $sr_no++;
                                         //function is created 
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl6 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
          //dd($tmtdata);           
                                                   
                                      }
      
      
      
      
      
                                  
                                 
                                      if($barlenghtl8 > 0)
                                      {
                                              $size="8 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl8 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
          $html .= $tmtdata['html']; // Accessing html
                             
                                                   
      
                                      }
                                   
                                      if($barlenghtl10 > 0)
                                      {
                                              $size="10 mm dia";
                                             
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl10 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                   
      
                                      }
                                      if($barlenghtl12 > 0)
                                      {
                                              $size="12 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl12 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
      
                                      }
                                      if($barlenghtl16 > 0)
                                      {
                                              $size="16 mm dia";
      
                                              $sr_no++;
                                               //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl16 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html                                                                          
      
                                      }
      
                                     
                                    
                                      if($barlenghtl20 > 0)
                                      {
                                              $size="20 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl20 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                      
                                      }
      
                                      if($barlenghtl25 > 0)
                                      {
                                              $size="25 mm dia";
      
                                              $sr_no++;
                                                //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl25 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                                        
                                      }
                                     
                                    
                                      if($barlenghtl28 > 0)
                                      {
                                              $size="28 mm dia";
      
                                              $sr_no++;
      
      
      
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl28 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                       
                      
                                      }
                                    
                                     
                                      if($barlenghtl32 > 0)
                                      {
                                              $size="32 mm dia";
      
                                              $sr_no++;
                                                  //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl32 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                       
                      
                                      }
                                    
                                     
                                     
                                      if($barlenghtl36 > 0)
                                      {
                                              $size="36 mm dia";
      
                                              $sr_no++;
                                                 //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl36 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                      
                                      }
      
      
                                      if($barlenghtl40 > 0)
                                      {
                                              $size="40 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl40 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                                       
                                      }
                                     // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];





                            }




// dd($sums);
//        foreach ($sums as $length => $value) {
//            if ($value !== 0 && $value !== null) {
//                // Only consider key-value pairs where the value is not 0 or null
//                $selectedlength[$length] = $value;

//                switch ($length) {
//                 case 'ldiam6':
//                     $size = "6 mm dia";
//                     //dd($size);
//                     break;
//                 case 'ldiam8':
//                     $size = "8 mm dia";
//                     //dd($size);
//                     break;
//                 case 'ldiam10':
//                     $size = "10 mm dia";
//                     break;
//                 case 'ldiam12':
//                     $size = "12 mm dia";
//                     //dd($size);
//                     break;
//                 case 'ldiam16':
//                     $size = "16 mm dia";
//                     break;
//                 case 'ldiam20':
//                     $size = "20 mm dia";
//                     break;
//                  case 'ldiam25':
//                     $size = "25 mm dia";
//                     break;
//                 case 'ldiam28':
//                     $size = "28 mm dia";
//                     break;
//                 case 'ldiam32':
//                     $size = "32 mm dia";
//                     break;
//                 case 'ldiam36':
//                     $size = "36 mm dia";
//                     break;
//                 case 'ldiam40':
//                     $size = "40 mm dia";
//                     break;
//                 case 'ldiam45':
//                     $size = "45 mm dia";
//                     break;

//             }

//               if($size)
//               {
//                  $weightquery=DB::table('stl_tbl')->where('size' , $size)->get('weight');

//                  $weight=$weightquery[1]->weight;
//                 // dd($weight);
//                  $unit= DB::table('stl_tbl')->where('size' , $size)->value('unit');

//                  $particulars = $sec_type . " - " . $size . " Total Length " . $selectedlength[$length] ." " . $unit . "& Weight " . $weight . " Kg/R.Mt.";
// //dd($particulars);
//                  $formula =  $selectedlength[$length] . " * " . $weight . " / " . 1000;
//                  //dd($formula);

//                  $singleweight = round(($selectedlength[$length] * $weight) / 1000, 3);
//                  //dd($singleweight);

//                   // Add the singleweight to the total weight
//                   $totalweight += round($singleweight, 3);



//                     // Create the row for the current item
//                      $html .= '<tr>
//                      <td>' . $sr_no . '</td>
//                      <td>' . $particulars . '</td>
//                      <td>' . $formula . '</td>
//                      <td>' . $singleweight . '</td>
//                    </tr>';

//                 // Increment the serial number for the next iteration
//                   $sr_no++;

//                  // $tbillid  $workid

//                   $previousmeasidObj = DB::table('embs')->where('b_item_id', '=', $bitemId)->orderBy('meas_id', 'desc')->select('meas_id')->first();

//                   if ($previousmeasidObj) {
//                       $previousmeasid = $previousmeasidObj->meas_id; // Convert object to string
//                       // Increment the last four digits of the previous meas_id
//                        $lastFourDigits = intval(substr($previousmeasid, -4));
//                        $newLastFourDigits = str_pad(($lastFourDigits + 1), 4, '0', STR_PAD_LEFT);
//                        $newmeasid = $bitemId.$newLastFourDigits;
//                        //dd($newmeasid);
//                  } else {
//                      // If no previous meas_id, start with bitemid.0001
//                      $newmeasid = $bitemId.'0001';
//                  }

//                  $stldate = DB::table('stlmeas')->where('b_item_id', $bitemId)->orderBy('date_meas' , 'desc')->first();
//                  // dd($stldate->date_meas);

//                             DB::table('embs')->insert([
//                                 'Work_Id' => $workid,
//                                 't_bill_id' => $tbillid,
//                                 'b_item_id' => $bitemId,
//                                 'meas_id' => $newmeasid,
//                                 'sr_no' => $sr_no,
//                                 'parti' => $particulars,
//                                 'formula' => $formula,
//                                 'qty' => $singleweight,
//                                 'measurment_dt' => $stldate->date_meas, // Insert the current date_meas value
//                             ]);




//               }

// //dd($particulars);


//            }
//        }

    $html .= '<tr style="background-color: #333; color: #fff;">
                <td></td>
                <td><strong>Total Weight:</strong></td>
                <td></td>
                <td><strong>' . $totalweight . ' M.T</strong></td>
              </tr>';

              $html .= '</tbody>
                       </table>
                   </div>
               </div>
           </div>';


     // dd($html);
       $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

       $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

       $previousTBillId = DB::table('bills')
       ->where('work_id' , $workid)
       ->where('t_bill_id', '<', $tbillid) // Add your condition here
       ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
       ->limit(1) // Limit the result to 1 row
       ->value('t_bill_id');
           //dd($previousTBillId);

           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

           $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


           $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
           //dd($previousexecqty);

           if (is_null($previousexecqty)) {
               $previousexecqty = 0;
           }

$curqty = number_format(round($totalweight, $Qtydec), 3, '.', '');


$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
           //dd($execqty);

           $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                $curamt=$curqty*$billrt;

           $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

               $bitemamt=$curamt+$previousamt;

           DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

               'exec_qty' => $execqty,
               'cur_qty' => $curqty,
               'prv_bill_qty' => $previousexecqty,
               'cur_amt' => $curamt,
               'b_item_amt' => $bitemamt,
           ]);

          $amountconvert=new CommonHelper();
                
             


           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
           $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
        //   $tndqty=round($tnditem->tnd_qty , 3);
        $tndqty=$tnditem->tnd_qty;
           $tndcostitem=$tnditem->t_item_amt;
           //dd($tndqty);
           $percentage=round(($execqty / $tndqty)*100 , 2);
           //dd($percentage);
           $totlcostitem=round($billrt*$execqty , 2);

           $costdifference= round($tndcostitem-$totlcostitem , 2);
           
           
               $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);

           $parta = 0; // Initialize the sum for matched conditions
           $partb = 0; // Initialize the sum for unmatched conditions

           $cparta = 0; // Initialize the sum for matched conditions
           $cpartb = 0; // Initialize the sum for unmatched conditions

          $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }



           $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

           $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
           //dd($billgrossamt);
           $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
           //dd($beloaboperc);
           $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

           $bill_amt=0;
          $abeffect = $parta * ($beloaboperc / 100);
          $cabeffect = $cparta * ($beloaboperc / 100);

                         if ($beloAbo === 'Above') {


                            $bill_amt = round(($parta + $abeffect), 2);
                            $cbill_amt = round(($cparta + $cabeffect), 2);

                        } elseif ($beloAbo === 'Below') {

                            $bill_amt = round(($parta - $abeffect), 2);
                            $cbill_amt = round(($cparta - $cabeffect), 2);

                        }

                         // Determine whether to add a minus sign
                         if ($beloAbo === 'Below') {
                             $abeffect = -$abeffect;
                             $cabeffect = -$cabeffect;
                             $beloaboperc = -$beloaboperc;
                            }
                            //dd($abeffect);
                           //$part_a_ab=($parta * $beloaboperc / 100);
                           //dd($partb);





                           $Gstbase = round($bill_amt, 2);
                           $cGstbase = round($cbill_amt, 2);
                                  //dd($Gstbase);

                                  $Gstamt= round($Gstbase*(18 / 100), 2);
                                  $cGstamt= round($cGstbase*(18 / 100), 2);
                                   //dd($Gstamt);

                                   $part_A_gstamt=$Gstbase + $Gstamt;
                                   $cpart_A_gstamt=$cGstbase + $cGstamt;


                                   $billamtgt = $partb + $part_A_gstamt;
                                   $cbillamtgt = $cpartb + $cpart_A_gstamt;

                     $integer_part = floor($billamtgt);  // Extract the integer part
                     $cinteger_part = floor($cbillamtgt);


                     $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                     $cdecimal_part = $cbillamtgt - $cinteger_part;
                     //dd($decimal_part);

                     $billamtro = round($decimal_part, 2);
                     $cbillamtro = round($cdecimal_part, 2);
                     //dd($rounded_decimal_part);

                //     // Round the total bill amount
                //     $billamtro = round($billamtgt);
                //     //dd($rounded_billamtgt);

                //    // Calculate the difference
                //     //$billamtro = $rounded_billamtgt - $billamtgt;
                //     dd($billamtro);
                    //$billamtro=0.37;
                    if ($billamtro > 0.50) {
                        // Calculate the absolute difference
                        $abs_diff = abs($billamtro);
                        $billamtro = 1 - $abs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $billamtro = -$billamtro;
                        //dd($billamtro);
                    }

                    if ($cbillamtro > 0.50) {
                        // Calculate the absolute difference
                        $cabs_diff = abs($cbillamtro);
                        $cbillamtro = 1 - $cabs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $cbillamtro = -$cbillamtro;
                        //dd($billamtro);
                    }
                     //dd($billamtro);

                     $net_amt= $billamtgt + $billamtro;
                     $cnet_amt= $cbillamtgt + $cbillamtro;
                     //dd($net_amt);

                      // Determine whether to add a minus sign


                     DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                        'part_a_amt' => $parta,
                        'part_a_gstamt' => $part_A_gstamt,
                        'part_b_amt' => $partb,
                        'gst_amt' => $Gstamt,
                        'gst_base' => $Gstbase,
                        'gross_amt' => $billgrossamt,
                        'a_b_effect' => $abeffect,
                        'bill_amt' => $bill_amt,
                        'bill_amt_gt' => $billamtgt,
                        'bill_amt_ro' => $billamtro,
                        'net_amt' => $net_amt,

                        'c_part_a_amt' => $cparta,
                        'c_part_a_gstamt' => $cpart_A_gstamt,
                        'c_part_b_amt' => $cpartb,
                        'curr_grossamt' => $cbillgrossamt,
                        'c_billamt' =>  $cbill_amt,
                        'c_gstamt' => $cGstamt,
                        'c_gstbase' => $cGstbase,
                        'c_abeffect' => $cabeffect,
                        'c_billamtgt' => $cbillamtgt,
                        'c_billamtro' => $cbillamtro,
                        'c_netamt' => $cnet_amt
                     ]);


             $html .= '

             <div class="row mt-3">
                  <div class="col-md-3 offset-md-9">
                      <div class="form-group">
                          <label for="currentQty" class="font-weight-bold">Current Quantity:</label>
                          <input type="text" class="form-control" id="currentQty{{ $emb3->b_item_id }}" name="currentQty" value="' .$curqty. '" disabled>
                      </div>
                  </div>
              </div>


             <div class="row mt-3">
               <div class="col-md-3 offset-md-9">
                     <div class="form-group" >
                         <label for="previousQty" class="font-weight-bold">Previous bill Quantity:</label>
                         <input type="text" class="form-control" id="previousQty{{ $emb3->b_item_id }}" name="previousQty" value="' .$previousexecqty. '"  disabled>
                     </div>
                 </div>
             </div>



             <div class="row mt-3">
             <div class="col-md-3 offset-md-3">
                 <div class="form-group">
                     <label for="tndqty" class="font-weight-bold">Tender Quantity Of Item:</label>
                     <input type="text" class="form-control" id="tndqty{{ $emb3->b_item_id }}" name="tndqty" value="' .$tndqty. '" disabled>
                 </div>
             </div>
             <div class="col-md-3">
                 <div class="form-group">
                     <label for="percentage" class="font-weight-bold">Percentage Utilised:</label>
                     <input type="text" class="form-control" id="percentage{{ $emb3->b_item_id }}" name="percentage" value="' .$percentage. '" disabled>
                 </div>
             </div>
             <div class="col-md-3">
                 <div class="form-group">
                     <label for="totalQty" class="font-weight-bold">Total Uptodate Quantity:</label>
                     <input type="text" class="form-control" id="totalQty{{ $emb3->b_item_id }}" name="totalQty" value="' .$execqty. '" disabled>
                 </div>
             </div>
         </div>

                <div class="row mt-3"  >
                <div class="col-md-3 offset-md-3">
                    <div class="form-group">
                      <label for="tndcost" class="font-weight-bold">Tender Cost Of Item:</label>
                      <input type="text" class="form-control" id="tndcost{{ $emb3->b_item_id }}" name="tndcost" value="' .$tndcostitem. '" disabled>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="costdifference" class="font-weight-bold">Excess/Saving:</label>
                      <input type="text" class="form-control" id="costdifference{{ $emb3->b_item_id }}" name="costdifference" value="' .$costdifference. '" disabled>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="totalcost" class="font-weight-bold">Total Uptodate Amount:</label>
                      <input type="text" class="form-control" id="totalcost{{ $emb3->b_item_id }}" name="totalcost" value="' .$totlcostitem. '" disabled>
                    </div>
                  </div>
                </div>';


    //dd();
    $billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
    $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->paginate(5);

    $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();

    $itemdata=DB::table('bil_item')->where('b_item_id' , $bitemId)->get();


    //workdetails
    $biltemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

 $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

 $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

 $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

$convert=new CommonHelper();


   $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
   $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
   '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
   '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($biltemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
   '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
   '</div></div>';

       DB::commit();

 //dd($itemdata);

         return response()->json([ 'stldata' => $stldata , 'html' => $html , 'billdata' => $billdata,
         'billitemdata' => $billitemdata,
         'lasttbillid' => $lasttbillid, 'itemdata' => $itemdata , 'workdetail' => $workdetail]);
         
         
        }catch(\Exception $e)
         {
            DB::rollback();
            Log::error('An error Occurr during save steel measurement' . $e->getMessage());

            return response()->json(["error" => 'An error Occurr during save steel measurement' . $e->getMessage()] , 500);

         }
    }



    public function deletesteelmeas(Request $request)
    {
        
         DB::beginTransaction();

      try {
          
        $steelid = $request->input('steelid');
      //dd($steelid);

      $steeldata=DB::table('stlmeas')->where('steelid' , $steelid)->first();
      //dd( $steeldata);

      $bitemId=DB::table('stlmeas')->where('steelid' , $steelid)->get()->value('b_item_id');
      $tbillid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_bill_id');
      $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');

      $html = '';

      if($steeldata)
      {
        $delstlid=DB::table('stlmeas')->where('steelid' , $steelid)->delete();


//dd($bitemId);
        $stldata = DB::table('stlmeas')
        ->where('stlmeas.b_item_id', $bitemId)
        ->get();
     //dd($stldata);
       $bill_rc_data = DB::table('bill_rcc_mbr')->get();

      // dd($stldata , $bill_rc_data);

       $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
         'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

         foreach ($stldata as &$data) {
           if (is_object($data)) {
               foreach ($ldiamColumns as $ldiamColumn) {
                   if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                       $temp = $data->$ldiamColumn;
                       $data->$ldiamColumn = $data->bar_length;
                       $data->bar_length = $temp;
                      // dd($data->bar_length , $data->$ldiamColumn);
                       break; // Stop checking other ldiam columns if we found a match
                   }
               }
           }
       }


       $sums = array_fill_keys($ldiamColumns, 0);

       foreach ($stldata as $row) {
           foreach ($ldiamColumns as $ldiamColumn) {
               $sums[$ldiamColumn] += $row->$ldiamColumn;
           }
       }//dd($stldata);
   //dd($sums);

   $bill_member = DB::table('bill_rcc_mbr')
   ->whereExists(function ($query) use ($bitemId) {
       $query->select(DB::raw(1))
             ->from('stlmeas')
             ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
             ->where('bill_rcc_mbr.b_item_id', $bitemId);
   })
   ->get();


   //$bill_memberdata=DB::table('rcc_mbr')->get();
   //dd($bill_member);
   // Generate the HTML content

   $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();
   //d($rc_mbr_ids);






   //dd($stldata);
       // Check if there is data for this rc_mbr_id
       // if ($stldata->isEmpty()) {
       //     continue; // Skip if there's no data
       // }


       foreach ($bill_member as $index => $member) {
           $html .= '<div class="container-fluid">';
           $html .= '
           <div class="container-fluid">
       <div class="row">
           <div class="col-md-1">
               <div class="form-group">
                   <label for="sr_no">Sr No</label>
                   <input type="text" class="form-control" id="sr_no" name="sr_no" value="' . $member->member_sr_no . '" disabled>
               </div>
           </div>
           <div class="col-md-4">
               <div class="form-group">
                   <label for="rcc_member">RCC Member</label>
                   <select class="form-control" id="rcc_member" name="rcc_member" disabled>
                       <option value="' . $member->rc_mbr_id . '">' . $member->rcc_member . '</option>
                   </select>
               </div>
           </div>
           <div class="col-md-4">
               <div class="form-group">
                   <label for="member_particular">Member Particular</label>
                   <input type="text" class="form-control" id="member_particular" name="member_particular" value="' . $member->member_particulars . '" disabled>
               </div>
           </div>
           <div class="col-md-2">
               <div class="form-group">
                    <label for="no_of_members">No Of Members</label>
                    <input type="text" class="form-control" id="no_of_members" name="no_of_members" value="' . $member->no_of_members . '" disabled>
               </div>
          </div>
          <div class="col-md-1">
          <div class="form-group">
             <button type="button" class="btn btn-primary btn-sm editrcmbr-button" data-rcbillid="' . $member->rc_mbr_id . '" title="EDIT RCC MEMBER"><i class="fa fa-pencil" aria-hidden="true"></i></button>
          </div>
     </div>
  </div>

  <div class="container-fluid">
  <div class="col-md-12">
               <table class="table table-striped">

                   <thead>
                       <tr>
                       <th>Sr No</th>
                       <th>Bar Particulars</th>
                       <th>No of Bars</th>
                       <th>Length of Bars</th>
                       <th>6mm</th>
                       <th>8mm</th>
                       <th>10mm</th>
                       <th>12mm</th>
                       <th>16mm</th>
                       <th>20mm</th>
                       <th>25mm</th>
                       <th>28mm</th>
                       <th>32mm</th>
                       <th>36mm</th>
                       <th>40mm</th>
                       <th>Date</th>
                       <th>Action</th>
                       </tr>
                   </thead>
                   <tbody>';

                   foreach ($stldata as $bar) {
                       if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                       //dd($bar);// Assuming the bar data is within a property like "bar_data"
                       $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                       $html .= '<tr>
                                   <td>'. $bar->bar_sr_no .'</td>
                                   <td>'. $bar->bar_particulars .'</td>
                                   <td>'. $bar->no_of_bars .'</td>
                                   <td>'. $bar->bar_length .'</td>
                                   <td>'. $bar->ldiam6 .'</td>
                                   <td>'. $bar->ldiam8 .'</td>
                                   <td>'. $bar->ldiam10 .'</td>
                                   <td>'. $bar->ldiam12 .'</td>
                                   <td>'. $bar->ldiam16 .'</td>
                                   <td>'. $bar->ldiam20 .'</td>
                                   <td>'. $bar->ldiam25 .'</td>
                                   <td>'. $bar->ldiam28 .'</td>
                                   <td>'. $bar->ldiam32 .'</td>
                                   <td>'. $bar->ldiam36 .'</td>
                                   <td>'. $bar->ldiam40 .'</td>
                                   <td>'. $formattedDateMeas .'</td>
                                   <td>
                                   <button type="button" class="btn btn-primary btn-sm edit-button" data-steelid="' . $bar->steelid . '" title="EDIT STEEL MEASUREMENT"> <i class="fa fa-pencil" style="color:white;"></i></button>
                                   <button type="button" class="btn btn-danger btn-sm delete-button" data-steelid="' . $bar->steelid . '" title="DELETE STEEL MEASUREMENT"><i class="fa fa-trash" aria-hidden="true"></i></button>
                               </td>
                                   </tr>';
                   }
               }

               $html .= '
                   </tbody>
               </table>
           </div>
       </div>
       </div>';

       // Add a row for the totals for the last member
       if ($index === count($bill_member) - 1) {
        $html .= '
        <div><h4>TOTAL LENGTH</h4></div>
       <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                <thead>
                    <tr>
                    <th></th>
                    <th colspan="13"></th>
                    <th>Length</th>
                    <th>6mm</th>
                    <th>8mm</th>
                    <th>10mm</th>
                    <th>12mm</th>
                    <th>16mm</th>
                    <th>20mm</th>
                    <th>25mm</th>
                    <th>28mm</th>
                    <th>32mm</th>
                    <th>36mm</th>
                    <th>40mm</th>
                    <th colspan="8"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                        <th>Total</th>
                        <td>' . $sums['ldiam6'] . '</td>
                        <td>' . $sums['ldiam8'] . '</td>
                        <td>' . $sums['ldiam10'] . '</td>
                        <td>' . $sums['ldiam12'] . '</td>
                        <td>' . $sums['ldiam16'] . '</td>
                        <td>' . $sums['ldiam20'] . '</td>
                        <td>' . $sums['ldiam25'] . '</td>
                        <td>' . $sums['ldiam28'] . '</td>
                        <td>' . $sums['ldiam32'] . '</td>
                        <td>' . $sums['ldiam36'] . '</td>
                        <td>' . $sums['ldiam40'] . '</td>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </div>';
    }

       $html .= '</div>'; // Close the container
   }
               // Check if this is the last member in the list


      }

      $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('item_id');

      if (in_array(substr($itemid, -6), ["003351", "003878"]))
      {
           $sec_type="HCRM/CRS Bar";
      }
   else{
           $sec_type="TMT Bar";
       }

       $selectedlength = [];
       $size=null;
       $sr_no = 0; // Initialize the serial number
       $totalweight = 0; // Initialize the total weight

       $html .= '<div><h4>TOTAL WEIGHT</h4></div> <div class="container-fluid">
<div class="row">
    <div class="col-md-12">
          <table class="table table-striped" style="width: 100%;">
            <thead>
                <tr>
                    <th>Sr No</th>
                    <th>Particulars</th>
                    <th>Formula</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>';

            $distinctStlDate = DB::table('stlmeas')
            ->select('date_meas') // Add other columns as needed
            ->where('b_item_id', $bitemId)
            ->groupBy('date_meas')
            ->orderBy('date_meas', 'asc') // Optional: Order by date in descending order
            ->get();


            DB::table('embs')->where('b_item_id', $bitemId)->delete();


            $Size=null;
           //dd($sums);
            foreach($distinctStlDate as $date)
           {
// //dd($date);
$barlenghtl6=0;
            $barlenghtl8=0;
            $barlenghtl10=0;
            $barlenghtl12=0;
            $barlenghtl16=0;
            $barlenghtl20=0;
            $barlenghtl25=0;
            $barlenghtl28=0;
            $barlenghtl32=0;
            $barlenghtl36=0;
            $barlenghtl40=0;
            $barlenghtl45=0;
                                $steelmeasdata=DB::table('stlmeas')->where('b_item_id', $bitemId)->where('date_meas', $date->date_meas)->get();

                              //dd($steelmeasdata);

                                foreach ($steelmeasdata as $row) {
//dd($row);
                                  $measurement=DB::table('stlmeas')->select('ldiam6','ldiam8' , 'ldiam10' , 'ldiam12' , 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40', 'ldiam45')->where('steelid' , $row->steelid)->first();

                                  $keyValuePairs = (object)[];

                                  foreach ($measurement as $column => $value) {
                                      if (!is_null($value)) {
                                          $keyValuePairs->$column = $value;
                                      }
                                  }
                                  //dd(key($keyValuePairs));
                                //   foreach ($row as $key => $value) {
                                //     }
                                    //dd($row);
                                    switch (key($keyValuePairs)) {
                                        case 'ldiam6':
                                            $Size = "6 mm dia";
                                            $barlenghtl6 += $row->bar_length;
                                            break;
                                        case 'ldiam8':
                                            $Size = "8 mm dia";
                                            $barlenghtl8 += $row->bar_length;
                                            break;
                                        case 'ldiam10':
                                            $Size = "10 mm dia";
                                            $barlenghtl10 += $row->bar_length;
                                            break;
                                        case 'ldiam12':
                                            $Size = "12 mm dia";
                                            $barlenghtl12 += $row->bar_length;
                                            break;
                                        case 'ldiam16':
                                            $Size = "16 mm dia";
                                            $barlenghtl16 += $row->bar_length;
                                            break;
                                        case 'ldiam20':
                                            $Size = "20 mm dia";
                                            $barlenghtl20 += $row->bar_length;
                                            break;
                                        case 'ldiam25':
                                            $Size = "25 mm dia";
                                            $barlenghtl25 += $row->bar_length;
                                            break;
                                        case 'ldiam28':
                                            $Size = "28 mm dia";
                                            $barlenghtl28 += $row->bar_length;
                                            break;
                                        case 'ldiam32':
                                            $Size = "32 mm dia";
                                            $barlenghtl32 += $row->bar_length;
                                            break;
                                        case 'ldiam36':
                                            $Size = "36 mm dia";
                                            $barlenghtl36 += $row->bar_length;
                                            break;
                                        case 'ldiam40':
                                            $Size = "40 mm dia";
                                            $barlenghtl40 += $row->bar_length;
                                            break;
                                        case 'ldiam45':
                                            $Size = "45 mm dia";
                                            $barlenghtl45 += $row->bar_length;
                                            break;
                                    }
                                }//dd($stldata);



                                $excelimportclass = new ExcelImport();


                                      if($barlenghtl6 > 0)
                                      {
      
                                         $size="6 mm dia";
                                          
                                         $sr_no++;
                                         //function is created 
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl6 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
          //dd($tmtdata);           
                                                   
                                      }
      
      
      
      
      
                                  
                                 
                                      if($barlenghtl8 > 0)
                                      {
                                              $size="8 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl8 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
          $html .= $tmtdata['html']; // Accessing html
                             
                                                   
      
                                      }
                                   
                                      if($barlenghtl10 > 0)
                                      {
                                              $size="10 mm dia";
                                             
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl10 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                   
      
                                      }
                                      if($barlenghtl12 > 0)
                                      {
                                              $size="12 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl12 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
      
                                      }
                                      if($barlenghtl16 > 0)
                                      {
                                              $size="16 mm dia";
      
                                              $sr_no++;
                                               //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl16 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html                                                                          
      
                                      }
      
                                     
                                    
                                      if($barlenghtl20 > 0)
                                      {
                                              $size="20 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl20 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                      
                                      }
      
                                      if($barlenghtl25 > 0)
                                      {
                                              $size="25 mm dia";
      
                                              $sr_no++;
                                                //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl25 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                                        
                                      }
                                     
                                    
                                      if($barlenghtl28 > 0)
                                      {
                                              $size="28 mm dia";
      
                                              $sr_no++;
      
      
      
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl28 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                       
                      
                                      }
                                    
                                     
                                      if($barlenghtl32 > 0)
                                      {
                                              $size="32 mm dia";
      
                                              $sr_no++;
                                                  //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl32 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                       
                      
                                      }
                                    
                                     
                                     
                                      if($barlenghtl36 > 0)
                                      {
                                              $size="36 mm dia";
      
                                              $sr_no++;
                                                 //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl36 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                      
                                      }
      
      
                                      if($barlenghtl40 > 0)
                                      {
                                              $size="40 mm dia";
      
                                              $sr_no++;
                                              //function call for the total weight and emb table in that insert steel data
                                      $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl40 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                      $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                      $html .= $tmtdata['html']; // Accessing html
                                                                       
                                      }
                                     // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];







                            }

    $html .= '<tr style="background-color: #333; color: #fff;">
                <td></td>
                <td><strong>Total Weight:</strong></td>
                <td></td>
                <td><strong>' . $totalweight . ' M.T</strong></td>
              </tr>';

              $html .= '</tbody>
              </table>
          </div>
      </div>
  </div>';



       $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

       $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

       $previousTBillId = DB::table('bills')
       ->where('work_id' , $workid)
       ->where('t_bill_id', '<', $tbillid) // Add your condition here
       ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
       ->limit(1) // Limit the result to 1 row
       ->value('t_bill_id');
           //dd($previousTBillId);

           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

$Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


$previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
//dd($previousexecqty);

if (is_null($previousexecqty)) {
    $previousexecqty = 0;
}

$curqty = number_format(round($totalweight, $Qtydec), 3, '.', '');
//dd($execqty);
//dd($previousexecqty);
//dd($curqty);



$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
//dd($execqty);




           //dd($execqty);

           $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                $curamt=$curqty*$billrt;

           $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

               $bitemamt=$curamt+$previousamt;

           DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

               'exec_qty' => $execqty,
               'cur_qty' => $curqty,
               'prv_bill_qty' => $previousexecqty,
               'cur_amt' => $curamt,
               'b_item_amt' => $bitemamt,
           ]);


           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
           $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
        //   $tndqty=round($tnditem->tnd_qty , 3);
        $tndqty=$tnditem->tnd_qty;
        
         $amountconvert=new CommonHelper();
                

           $tndcostitem=$tnditem->t_item_amt;
           //dd($tndqty);
           $percentage=round(($execqty / $tndqty)*100 , 2);
           //dd($percentage);
           $totlcostitem=round($billrt*$execqty , 2);

           $costdifference= round($tndcostitem-$totlcostitem , 2);
           
                 $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);


           $parta = 0; // Initialize the sum for matched conditions
                 $partb = 0; // Initialize the sum for unmatched conditions

                 $cparta = 0; // Initialize the sum for matched conditions
                 $cpartb = 0; // Initialize the sum for unmatched conditions

                 $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }


                 $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

                 $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
                 //dd($billgrossamt);
                 $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
                 //dd($beloaboperc);
                 $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

                 $bill_amt=0;
                $abeffect = $parta * ($beloaboperc / 100);
                $cabeffect = $cparta * ($beloaboperc / 100);

                               if ($beloAbo === 'Above') {


                                  $bill_amt = round(($parta + $abeffect), 2);
                                  $cbill_amt = round(($cparta + $cabeffect), 2);

                              } elseif ($beloAbo === 'Below') {

                                  $bill_amt = round(($parta - $abeffect), 2);
                                  $cbill_amt = round(($cparta - $cabeffect), 2);

                              }

                               // Determine whether to add a minus sign
                               if ($beloAbo === 'Below') {
                                   $abeffect = -$abeffect;
                                   $cabeffect = -$cabeffect;
                                   $beloaboperc = -$beloaboperc;
                                  }
                                  //dd($abeffect);
                                 //$part_a_ab=($parta * $beloaboperc / 100);
                                 //dd($partb);





                                 $Gstbase = round($bill_amt, 2);
                                 $cGstbase = round($cbill_amt, 2);
                                        //dd($Gstbase);

                                        $Gstamt= round($Gstbase*(18 / 100), 2);
                                        $cGstamt= round($cGstbase*(18 / 100), 2);
                                         //dd($Gstamt);

                                         $part_A_gstamt=$Gstbase + $Gstamt;
                                         $cpart_A_gstamt=$cGstbase + $cGstamt;


                                         $billamtgt = $partb + $part_A_gstamt;
                                         $cbillamtgt = $cpartb + $cpart_A_gstamt;

                           $integer_part = floor($billamtgt);  // Extract the integer part
                           $cinteger_part = floor($cbillamtgt);


                           $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                           $cdecimal_part = $cbillamtgt - $cinteger_part;
                           //dd($decimal_part);

                           $billamtro = round($decimal_part, 2);
                           $cbillamtro = round($cdecimal_part, 2);
                           //dd($rounded_decimal_part);

                      //     // Round the total bill amount
                      //     $billamtro = round($billamtgt);
                      //     //dd($rounded_billamtgt);

                      //    // Calculate the difference
                      //     //$billamtro = $rounded_billamtgt - $billamtgt;
                      //     dd($billamtro);
                          //$billamtro=0.37;
                          if ($billamtro > 0.50) {
                              // Calculate the absolute difference
                              $abs_diff = abs($billamtro);
                              $billamtro = 1 - $abs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $billamtro = -$billamtro;
                              //dd($billamtro);
                          }

                          if ($cbillamtro > 0.50) {
                              // Calculate the absolute difference
                              $cabs_diff = abs($cbillamtro);
                              $cbillamtro = 1 - $cabs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $cbillamtro = -$cbillamtro;
                              //dd($billamtro);
                          }
                           //dd($billamtro);

                           $net_amt= $billamtgt + $billamtro;
                           $cnet_amt= $cbillamtgt + $cbillamtro;
                           //dd($net_amt);

                            // Determine whether to add a minus sign


                           DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                              'part_a_amt' => $parta,
                              'part_a_gstamt' => $part_A_gstamt,
                              'part_b_amt' => $partb,
                              'gst_amt' => $Gstamt,
                              'gst_base' => $Gstbase,
                              'gross_amt' => $billgrossamt,
                              'a_b_effect' => $abeffect,
                              'bill_amt' => $bill_amt,
                              'bill_amt_gt' => $billamtgt,
                              'bill_amt_ro' => $billamtro,
                              'net_amt' => $net_amt,

                              'c_part_a_amt' => $cparta,
                              'c_part_a_gstamt' => $cpart_A_gstamt,
                              'c_part_b_amt' => $cpartb,
                              'curr_grossamt' => $cbillgrossamt,
                              'c_billamt' =>  $cbill_amt,
                              'c_gstamt' => $cGstamt,
                              'c_gstbase' => $cGstbase,
                              'c_abeffect' => $cabeffect,
                              'c_billamtgt' => $cbillamtgt,
                              'c_billamtro' => $cbillamtro,
                              'c_netamt' => $cnet_amt
                           ]);

                     $html .= '

                     <div class="row mt-3">
                          <div class="col-md-3 offset-md-9">
                              <div class="form-group">
                                  <label for="currentQty" class="font-weight-bold">Current Quantity:</label>
                                  <input type="text" class="form-control" id="currentQty{{ $emb3->b_item_id }}" name="currentQty" value="' .$curqty. '" disabled>
                              </div>
                          </div>
                      </div>


                     <div class="row mt-3">
                       <div class="col-md-3 offset-md-9">
                             <div class="form-group" >
                                 <label for="previousQty" class="font-weight-bold">Previous bill Quantity:</label>
                                 <input type="text" class="form-control" id="previousQty{{ $emb3->b_item_id }}" name="previousQty" value="' .$previousexecqty. '"  disabled>
                             </div>
                         </div>
                     </div>



                     <div class="row mt-3">
                     <div class="col-md-3 offset-md-3">
                         <div class="form-group">
                             <label for="tndqty" class="font-weight-bold">Tender Quantity Of Item:</label>
                             <input type="text" class="form-control" id="tndqty{{ $emb3->b_item_id }}" name="tndqty" value="' .$tndqty. '" disabled>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="form-group">
                             <label for="percentage" class="font-weight-bold">Percentage Utilised:</label>
                             <input type="text" class="form-control" id="percentage{{ $emb3->b_item_id }}" name="percentage" value="' .$percentage. '" disabled>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="form-group">
                             <label for="totalQty" class="font-weight-bold">Total Uptodate Quantity:</label>
                             <input type="text" class="form-control" id="totalQty{{ $emb3->b_item_id }}" name="totalQty" value="' .$execqty. '" disabled>
                         </div>
                     </div>
                 </div>

                        <div class="row mt-3"  >
                        <div class="col-md-3 offset-md-3">
                            <div class="form-group">
                              <label for="tndcost" class="font-weight-bold">Tender Cost Of Item:</label>
                              <input type="text" class="form-control" id="tndcost{{ $emb3->b_item_id }}" name="tndcost" value="' .$tndcostitem. '" disabled>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="form-group">
                              <label for="costdifference" class="font-weight-bold">Excess/Saving:</label>
                              <input type="text" class="form-control" id="costdifference{{ $emb3->b_item_id }}" name="costdifference" value="' .$costdifference. '" disabled>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="form-group">
                              <label for="totalcost" class="font-weight-bold">Total Uptodate Amount:</label>
                              <input type="text" class="form-control" id="totalcost{{ $emb3->b_item_id }}" name="totalcost" value="' .$totlcostitem. '" disabled>
                            </div>
                          </div>
                        </div>';



                     $billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
                  $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->paginate(5);

                  $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();


                  $bitemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->get();



                  //workdetails
    $biltemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

    $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

    $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

  $convert=new CommonHelper();

      $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
      $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
      '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE') . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($biltemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->curr_grossamt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '</div></div>';


    DB::commit();

        // Implement your logic to delete the row based on $steelid
        // Return a JSON response to indicate success or failure
        // Example success response: return response()->json(['success' => true]);

        // If the delete operation fails, you can return an error response.
        // Example error response: return response()->json(['success' => false, 'message' => 'Delete operation failed']);
        return response()->json(['html' => $html ,  'billdata' => $billdata,
        'billitemdata' => $billitemdata,
        'lasttbillid' => $lasttbillid, 'bitemId' =>  $bitemId , 'bitemdata' => $bitemdata, 'workdetail' => $workdetail
     ]);
     
     
      } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in Delete Measurement  ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }



    //edit rc steel bill
    public function editrcbill($rcmbrid)
    {
        try{
            
       //dd($rcmbrid);

       $billmember=DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->first();

       $rccmember=DB::table('rcc_mbr')->select('rcc_memb' , 'rcc_mbr_id')->get();
//dd($rccmember);
  $bitemid=DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->value('b_item_id');


  //work details
  $billtemdata=DB::table('bil_item')->where('b_item_id', $bitemid)->first();

  $tbillid=DB::table('bil_item')->where('b_item_id', $bitemid)->value('t_bill_id');

  $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

  $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');

$convert=new CommonHelper();

    $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
    $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
    '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
    '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
    '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
    '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
    '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE')
 . '&nbsp;&nbsp;&nbsp;' .
    '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
    '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
    '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
     '</div></div>';




       return response()->json(['billmember' => $billmember , 'rccmember' => $rccmember , 'bitemid' => $bitemid ,'workdetail' => $workdetail]);
       
        }catch(\Exception $e)
        {
            Log::error('An error Occurr during Edit RCC member box open' . $e->getMessage());

            return response()->json(['error' => 'An error Occurr during Edit RCC member box open' . $e->getMessage()] , 500);
        }
    }


    public function submiteditrcbill(Request $request , $rcmbrid)
    {
        DB::beginTransaction();
        
        try{
            
             $rccmember=$request->input('rcc_member');
             $membersrno=$request->input('member_sr_no');
              $memberparticular = $request->input('member_particulars');
             $noofmembers = $request->input('no_of_members');
             //dd($billrcdata);


             DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->update([
                'member_sr_no' => $membersrno,
                'rcc_member' => $rccmember,
                'member_particulars' => $memberparticular,
                'no_of_members' => $noofmembers,
            ]);

             $steeldata = DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->get();

             $foundData = null; // Initialize a variable to store the found data

             $barlength=null;

             foreach($steeldata as $row)
             {
                $rowData = (array) $row; // Convert the row object to an associative array

                // Loop through the specified columns
                $columnsToCheck = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25', 'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40'];
                foreach ($columnsToCheck as $column) {
                    if (isset($rowData[$column]) && !empty($rowData[$column])) {
                        $foundData = $rowData[$column];

                    }
                }

                // If data is found in any of the specified columns, $foundData will hold that data
                if ($foundData !== null) {
                     // Exit the outer loop if data is found
                     $barlength = $noofmembers * $row->no_of_bars * $foundData;

                     //dd($barlength);
                }


                DB::table('stlmeas')->where('steelid' , $row->steelid)->update(['bar_length' => $barlength]);



             }
             //dd($foundData);
         //dd($steeldata);
         //dd($barlength);

             $html = '';
             $bitemId=DB::table('bill_rcc_mbr')
             ->where('rc_mbr_id' , $rcmbrid)->value('b_item_id');

             $tbillid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_bill_id');
             $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');

             $stldata = DB::table('stlmeas')
             ->where('b_item_id', $bitemId)
             ->get();
          //dd($stldata);
            $bill_rc_data = DB::table('bill_rcc_mbr')->get();

           // dd($stldata , $bill_rc_data);

            $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
              'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

              foreach ($stldata as &$data) {
                if (is_object($data)) {
                    foreach ($ldiamColumns as $ldiamColumn) {
                        if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                            $temp = $data->$ldiamColumn;
                            $data->$ldiamColumn = $data->bar_length;
                            $data->bar_length = $temp;
                           // dd($data->bar_length , $data->$ldiamColumn);
                            break; // Stop checking other ldiam columns if we found a match
                        }
                    }
                }
            }


            $sums = array_fill_keys($ldiamColumns, 0);

            foreach ($stldata as $row) {
                foreach ($ldiamColumns as $ldiamColumn) {
                    $sums[$ldiamColumn] += $row->$ldiamColumn;
                }
            }//dd($stldata);
        //dd($sums);

        $bill_member = DB::table('bill_rcc_mbr')
        ->whereExists(function ($query) use ($bitemId) {
            $query->select(DB::raw(1))
                  ->from('stlmeas')
                  ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                  ->where('bill_rcc_mbr.b_item_id', $bitemId);
        })
        ->get();



        //$bill_memberdata=DB::table('rcc_mbr')->get();
        //dd($bill_member);
        // Generate the HTML content

        $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();
        //d($rc_mbr_ids);






        //dd($stldata);
            // Check if there is data for this rc_mbr_id
            // if ($stldata->isEmpty()) {
            //     continue; // Skip if there's no data
            // }


            foreach ($bill_member as $index => $member) {
                $html .= '<div class="container-fluid">';
                $html .= '
                <div class="container-fluid">
            <div class="row">
                <div class="col-md-1">
                    <div class="form-group">
                        <label for="sr_no">Sr No</label>
                        <input type="text" class="form-control" id="sr_no" name="sr_no" value="' . $member->member_sr_no . '" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="rcc_member">RCC Member</label>
                        <select class="form-control" id="rcc_member" name="rcc_member" disabled>
                            <option value="' . $member->rc_mbr_id . '">' . $member->rcc_member . '</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="member_particular">Member Particular</label>
                        <input type="text" class="form-control" id="member_particular" name="member_particular" value="' . $member->member_particulars . '" disabled >
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                         <label for="no_of_members">No Of Members</label>
                         <input type="text" class="form-control" id="no_of_members" name="no_of_members" value="' . $member->no_of_members . '" disabled>
                    </div>
               </div>
               <div class="col-md-1">
               <div class="form-group">
                  <button type="button" class="btn btn-primary btn-sm editrcmbr-button" data-rcbillid="' . $member->rc_mbr_id . '" id="editrccmbr ' .$bitemId.'" title="EDIT RCC MEMBER"><i class="fa fa-pencil" aria-hidden="true" ></i></button>
               </div>
          </div>
        </div>


        <div class="container-fluid">
              <div class="col-md-12">
                    <table class="table table-striped">

                        <thead>
                            <tr>
                            <th>Sr No</th>
                            <th>Bar Particulars</th>
                            <th>No of Bars</th>
                            <th>Length of Bars</th>
                            <th>6mm</th>
                            <th>8mm</th>
                            <th>10mm</th>
                            <th>12mm</th>
                            <th>16mm</th>
                            <th>20mm</th>
                            <th>25mm</th>
                            <th>28mm</th>
                            <th>32mm</th>
                            <th>36mm</th>
                            <th>40mm</th>
                            <th>Date</th>
                            <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>';

                        foreach ($stldata as $bar) {
                            if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                            //dd($bar);// Assuming the bar data is within a property like "bar_data"
                            $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                            $html .= '<tr>
                                        <td>'. $bar->bar_sr_no .'</td>
                                        <td>'. $bar->bar_particulars .'</td>
                                        <td>'. $bar->no_of_bars .'</td>
                                        <td>'. $bar->bar_length .'</td>
                                        <td>'. $bar->ldiam6 .'</td>
                                        <td>'. $bar->ldiam8 .'</td>
                                        <td>'. $bar->ldiam10 .'</td>
                                        <td>'. $bar->ldiam12 .'</td>
                                        <td>'. $bar->ldiam16 .'</td>
                                        <td>'. $bar->ldiam20 .'</td>
                                        <td>'. $bar->ldiam25 .'</td>
                                        <td>'. $bar->ldiam28 .'</td>
                                        <td>'. $bar->ldiam32 .'</td>
                                        <td>'. $bar->ldiam36 .'</td>
                                        <td>'. $bar->ldiam40 .'</td>
                                        <td>'. $formattedDateMeas .'</td>
                                        <td>
                                        <button type="button" class="btn btn-primary btn-sm edit-button" data-steelid="' . $bar->steelid . '" title="EDIT STEEL MEASUREMENT"> <i class="fa fa-pencil" style="color:white;"></i></button>
                                        <button type="button" class="btn btn-danger btn-sm delete-button" data-steelid="' . $bar->steelid . '" title="DELETE STEEL MEASUREMENT"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                    </td>
                                        </tr>';
                        }
                    }

                    $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
            </div>';

            // Add a row for the totals for the last member
            if ($index === count($bill_member) - 1) {
                $html .= '
                <div><h4>TOTAL LENGTH</h4></div>
               <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped">
                        <thead>
                            <tr>
                            <th></th>
                            <th colspan="13"></th>
                            <th>Length</th>
                            <th>6mm</th>
                            <th>8mm</th>
                            <th>10mm</th>
                            <th>12mm</th>
                            <th>16mm</th>
                            <th>20mm</th>
                            <th>25mm</th>
                            <th>28mm</th>
                            <th>32mm</th>
                            <th>36mm</th>
                            <th>40mm</th>
                            <th colspan="8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                                <th>Total</th>
                                <td>' . $sums['ldiam6'] . '</td>
                                <td>' . $sums['ldiam8'] . '</td>
                                <td>' . $sums['ldiam10'] . '</td>
                                <td>' . $sums['ldiam12'] . '</td>
                                <td>' . $sums['ldiam16'] . '</td>
                                <td>' . $sums['ldiam20'] . '</td>
                                <td>' . $sums['ldiam25'] . '</td>
                                <td>' . $sums['ldiam28'] . '</td>
                                <td>' . $sums['ldiam32'] . '</td>
                                <td>' . $sums['ldiam36'] . '</td>
                                <td>' . $sums['ldiam40'] . '</td>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>';
            }

            $html .= '</div>'; // Close the container
        }


                    // Check if this is the last member in the list

                    $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('item_id');

      if (in_array(substr($itemid, -6), ["003351", "003878"]))
      {
           $sec_type="HCRM/CRS Bar";
      }
   else{
           $sec_type="TMT Bar";
       }

       $selectedlength = [];
       $size=null;
       $sr_no = 0; // Initialize the serial number
       $totalweight = 0; // Initialize the total weight

       $html .= '<div><h4>TOTAL WEIGHT</h4></div>
       <div class="container-fluid">
       <div class="row">
           <div class="col-md-12">
                 <table class="table table-striped" style="width: 100%;">
                   <thead>
                       <tr>
                           <th>Sr No</th>
                           <th>Particulars</th>
                           <th>Formula</th>
                           <th>Weight</th>
                       </tr>
                   </thead>
                   <tbody>';


                   $distinctStlDate = DB::table('stlmeas')
                   ->select('date_meas') // Add other columns as needed
                   ->where('b_item_id', $bitemId)
                   ->groupBy('date_meas')
                   ->orderBy('date_meas', 'asc') // Optional: Order by date in descending order
                   ->get();

                   DB::table('embs')->where('b_item_id', $bitemId)->delete();



                   $Size=null;
                  //dd($sums);
                   foreach($distinctStlDate as $date)
                  {
       // //dd($date);
       $barlenghtl6=0;
                   $barlenghtl8=0;
                   $barlenghtl10=0;
                   $barlenghtl12=0;
                   $barlenghtl16=0;
                   $barlenghtl20=0;
                   $barlenghtl25=0;
                   $barlenghtl28=0;
                   $barlenghtl32=0;
                   $barlenghtl36=0;
                   $barlenghtl40=0;
                   $barlenghtl45=0;
                                       $steelmeasdata=DB::table('stlmeas')->where('b_item_id', $bitemId)->where('date_meas', $date->date_meas)->get();

                                     //dd($steelmeasdata);

                                       foreach ($steelmeasdata as $row) {
       //dd($row);
                                         $measurement=DB::table('stlmeas')->select('ldiam6','ldiam8' , 'ldiam10' , 'ldiam12' , 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40', 'ldiam45')->where('steelid' , $row->steelid)->first();

                                         $keyValuePairs = (object)[];

                                         foreach ($measurement as $column => $value) {
                                             if (!is_null($value)) {
                                                 $keyValuePairs->$column = $value;
                                             }
                                         }
                                         //dd(key($keyValuePairs));
                                       //   foreach ($row as $key => $value) {
                                       //     }
                                           //dd($row);
                                           switch (key($keyValuePairs)) {
                                               case 'ldiam6':
                                                   $Size = "6 mm dia";
                                                   $barlenghtl6 += $row->bar_length;
                                                   break;
                                               case 'ldiam8':
                                                   $Size = "8 mm dia";
                                                   $barlenghtl8 += $row->bar_length;
                                                   break;
                                               case 'ldiam10':
                                                   $Size = "10 mm dia";
                                                   $barlenghtl10 += $row->bar_length;
                                                   break;
                                               case 'ldiam12':
                                                   $Size = "12 mm dia";
                                                   $barlenghtl12 += $row->bar_length;
                                                   break;
                                               case 'ldiam16':
                                                   $Size = "16 mm dia";
                                                   $barlenghtl16 += $row->bar_length;
                                                   break;
                                               case 'ldiam20':
                                                   $Size = "20 mm dia";
                                                   $barlenghtl20 += $row->bar_length;
                                                   break;
                                               case 'ldiam25':
                                                   $Size = "25 mm dia";
                                                   $barlenghtl25 += $row->bar_length;
                                                   break;
                                               case 'ldiam28':
                                                   $Size = "28 mm dia";
                                                   $barlenghtl28 += $row->bar_length;
                                                   break;
                                               case 'ldiam32':
                                                   $Size = "32 mm dia";
                                                   $barlenghtl32 += $row->bar_length;
                                                   break;
                                               case 'ldiam36':
                                                   $Size = "36 mm dia";
                                                   $barlenghtl36 += $row->bar_length;
                                                   break;
                                               case 'ldiam40':
                                                   $Size = "40 mm dia";
                                                   $barlenghtl40 += $row->bar_length;
                                                   break;
                                               case 'ldiam45':
                                                   $Size = "45 mm dia";
                                                   $barlenghtl45 += $row->bar_length;
                                                   break;
                                           }
                                       }//dd($stldata);



                                       $excelimportclass = new ExcelImport();


                                       if($barlenghtl6 > 0)
                                       {
       
                                          $size="6 mm dia";
                                           
                                          $sr_no++;
                                          //function is created 
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl6 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html
           //dd($tmtdata);           
                                                    
                                       }
       
       
       
       
       
                                   
                                  
                                       if($barlenghtl8 > 0)
                                       {
                                               $size="8 mm dia";
       
                                               $sr_no++;
                                               //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl8 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
           $html .= $tmtdata['html']; // Accessing html
                              
                                                    
       
                                       }
                                    
                                       if($barlenghtl10 > 0)
                                       {
                                               $size="10 mm dia";
                                              
                                               $sr_no++;
                                               //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl10 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html
                                                    
       
                                       }
                                       if($barlenghtl12 > 0)
                                       {
                                               $size="12 mm dia";
       
                                               $sr_no++;
                                               //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl12 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html
       
                                       }
                                       if($barlenghtl16 > 0)
                                       {
                                               $size="16 mm dia";
       
                                               $sr_no++;
                                                //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl16 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html                                                                          
       
                                       }
       
                                      
                                     
                                       if($barlenghtl20 > 0)
                                       {
                                               $size="20 mm dia";
       
                                               $sr_no++;
                                               //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl20 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html
                       
                                       }
       
                                       if($barlenghtl25 > 0)
                                       {
                                               $size="25 mm dia";
       
                                               $sr_no++;
                                                 //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl25 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html
                                                                         
                                       }
                                      
                                     
                                       if($barlenghtl28 > 0)
                                       {
                                               $size="28 mm dia";
       
                                               $sr_no++;
       
       
       
                                               //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl28 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html
                                                        
                       
                                       }
                                     
                                      
                                       if($barlenghtl32 > 0)
                                       {
                                               $size="32 mm dia";
       
                                               $sr_no++;
                                                   //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl32 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html
                                                        
                       
                                       }
                                     
                                      
                                      
                                       if($barlenghtl36 > 0)
                                       {
                                               $size="36 mm dia";
       
                                               $sr_no++;
                                                  //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl36 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html
                       
                                       }
       
       
                                       if($barlenghtl40 > 0)
                                       {
                                               $size="40 mm dia";
       
                                               $sr_no++;
                                               //function call for the total weight and emb table in that insert steel data
                                       $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl40 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                       $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                       $html .= $tmtdata['html']; // Accessing html
                                                                        
                                       }
                                      // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];
 




                                   }

              $html .= '<tr style="background-color: #333; color: #fff;">
       <td></td>
       <td><strong>Total Weight:</strong></td>
       <td></td>
       <td><strong>' . $totalweight . ' M.T</strong></td>
     </tr>';

     $html .= '</tbody>
     </table>
 </div>
</div>
</div>';





       $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');
        // dd($bitemId);
       $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

       $previousTBillId = DB::table('bills')
       ->where('work_id' , $workid)
       ->where('t_bill_id', '<', $tbillid) // Add your condition here
       ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
       ->limit(1) // Limit the result to 1 row
       ->value('t_bill_id');
           //dd($previousTBillId);

           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

           $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');


           $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
           //dd($previousexecqty);

           if (is_null($previousexecqty)) {
               $previousexecqty = 0;
           }

$curqty = number_format(round($totalweight, $Qtydec), 3, '.', '');


$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
           //dd($execqty);

           $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                $curamt=$curqty*$billrt;

           $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

               $bitemamt=$curamt+$previousamt;

           DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

               'exec_qty' => $execqty,
               'cur_qty' => $curqty,
               'prv_bill_qty' => $previousexecqty,
               'cur_amt' => $curamt,
               'b_item_amt' => $bitemamt,
           ]);


           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
           $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
        //   $tndqty=round($tnditem->tnd_qty , 3);
        $tndqty=$tnditem->tnd_qty;
        
        
         $amountconvert=new CommonHelper();
                

           $tndcostitem=$tnditem->t_item_amt;
           //dd($tndqty);
           $percentage=round(($execqty / $tndqty)*100 , 2);
           //dd($percentage);
           $totlcostitem=round($billrt*$execqty , 2);

           $costdifference= round($tndcostitem-$totlcostitem , 2);

                 $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);


           $parta = 0; // Initialize the sum for matched conditions
                 $partb = 0; // Initialize the sum for unmatched conditions

                 $cparta = 0; // Initialize the sum for matched conditions
                 $cpartb = 0; // Initialize the sum for unmatched conditions

                 $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);

                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          )
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');

                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions


                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }



                 $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

                 $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
                 //dd($billgrossamt);
                 $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
                 //dd($beloaboperc);
                 $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');

                 $bill_amt=0;
                $abeffect = $parta * ($beloaboperc / 100);
                $cabeffect = $cparta * ($beloaboperc / 100);

                               if ($beloAbo === 'Above') {


                                  $bill_amt = round(($parta + $abeffect), 2);
                                  $cbill_amt = round(($cparta + $cabeffect), 2);

                              } elseif ($beloAbo === 'Below') {

                                  $bill_amt = round(($parta - $abeffect), 2);
                                  $cbill_amt = round(($cparta - $cabeffect), 2);

                              }

                               // Determine whether to add a minus sign
                               if ($beloAbo === 'Below') {
                                   $abeffect = -$abeffect;
                                   $cabeffect = -$cabeffect;
                                   $beloaboperc = -$beloaboperc;
                                  }
                                  //dd($abeffect);
                                 //$part_a_ab=($parta * $beloaboperc / 100);
                                 //dd($partb);





                                 $Gstbase = round($bill_amt, 2);
                                 $cGstbase = round($cbill_amt, 2);
                                        //dd($Gstbase);

                                        $Gstamt= round($Gstbase*(18 / 100), 2);
                                        $cGstamt= round($cGstbase*(18 / 100), 2);
                                         //dd($Gstamt);

                                         $part_A_gstamt=$Gstbase + $Gstamt;
                                         $cpart_A_gstamt=$cGstbase + $cGstamt;


                                         $billamtgt = $partb + $part_A_gstamt;
                                         $cbillamtgt = $cpartb + $cpart_A_gstamt;

                           $integer_part = floor($billamtgt);  // Extract the integer part
                           $cinteger_part = floor($cbillamtgt);


                           $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                           $cdecimal_part = $cbillamtgt - $cinteger_part;
                           //dd($decimal_part);

                           $billamtro = round($decimal_part, 2);
                           $cbillamtro = round($cdecimal_part, 2);
                           //dd($rounded_decimal_part);

                      //     // Round the total bill amount
                      //     $billamtro = round($billamtgt);
                      //     //dd($rounded_billamtgt);

                      //    // Calculate the difference
                      //     //$billamtro = $rounded_billamtgt - $billamtgt;
                      //     dd($billamtro);
                          //$billamtro=0.37;
                          if ($billamtro > 0.50) {
                              // Calculate the absolute difference
                              $abs_diff = abs($billamtro);
                              $billamtro = 1 - $abs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $billamtro = -$billamtro;
                              //dd($billamtro);
                          }

                          if ($cbillamtro > 0.50) {
                              // Calculate the absolute difference
                              $cabs_diff = abs($cbillamtro);
                              $cbillamtro = 1 - $cabs_diff;
                              //dd($billamtro);
                          }
                          else {
                              // If it is, add a minus sign to the difference
                              $cbillamtro = -$cbillamtro;
                              //dd($billamtro);
                          }
                           //dd($billamtro);

                           $net_amt= $billamtgt + $billamtro;
                           $cnet_amt= $cbillamtgt + $cbillamtro;
                           //dd($net_amt);

                            // Determine whether to add a minus sign


                           DB::table('bills')->where('t_bill_id' , $tbillid)->update([

                              'part_a_amt' => $parta,
                              'part_a_gstamt' => $part_A_gstamt,
                              'part_b_amt' => $partb,
                              'gst_amt' => $Gstamt,
                              'gst_base' => $Gstbase,
                              'gross_amt' => $billgrossamt,
                              'a_b_effect' => $abeffect,
                              'bill_amt' => $bill_amt,
                              'bill_amt_gt' => $billamtgt,
                              'bill_amt_ro' => $billamtro,
                              'net_amt' => $net_amt,

                              'c_part_a_amt' => $cparta,
                              'c_part_a_gstamt' => $cpart_A_gstamt,
                              'c_part_b_amt' => $cpartb,
                              'curr_grossamt' => $cbillgrossamt,
                              'c_billamt' =>  $cbill_amt,
                              'c_gstamt' => $cGstamt,
                              'c_gstbase' => $cGstbase,
                              'c_abeffect' => $cabeffect,
                              'c_billamtgt' => $cbillamtgt,
                              'c_billamtro' => $cbillamtro,
                              'c_netamt' => $cnet_amt
                           ]);






             $billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
                  $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->paginate(5);

                  $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();

                  $itemdata=DB::table('bil_item')->where('b_item_id' , $bitemId)->get();

                  $html .= '

                  <div class="row mt-3">
                       <div class="col-md-3 offset-md-9">
                           <div class="form-group">
                               <label for="currentQty" class="font-weight-bold">Current Quantity:</label>
                               <input type="text" class="form-control" id="currentQty{{ $emb3->b_item_id }}" name="currentQty" value="' .$curqty. '" disabled>
                           </div>
                       </div>
                   </div>


                  <div class="row mt-3">
                    <div class="col-md-3 offset-md-9">
                          <div class="form-group" >
                              <label for="previousQty" class="font-weight-bold">Previous bill Quantity:</label>
                              <input type="text" class="form-control" id="previousQty{{ $emb3->b_item_id }}" name="previousQty" value="' .$previousexecqty. '"  disabled>
                          </div>
                      </div>
                  </div>



                  <div class="row mt-3">
                  <div class="col-md-3 offset-md-3">
                      <div class="form-group">
                          <label for="tndqty" class="font-weight-bold">Tender Quantity Of Item:</label>
                          <input type="text" class="form-control" id="tndqty{{ $emb3->b_item_id }}" name="tndqty" value="' .$tndqty. '" disabled>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="form-group">
                          <label for="percentage" class="font-weight-bold">Percentage Utilised:</label>
                          <input type="text" class="form-control" id="percentage{{ $emb3->b_item_id }}" name="percentage" value="' .$percentage. '" disabled>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="form-group">
                          <label for="totalQty" class="font-weight-bold">Total Uptodate Quantity:</label>
                          <input type="text" class="form-control" id="totalQty{{ $emb3->b_item_id }}" name="totalQty" value="' .$execqty. '" disabled>
                      </div>
                  </div>
              </div>

                     <div class="row mt-3"  >
                     <div class="col-md-3 offset-md-3">
                         <div class="form-group">
                           <label for="tndcost" class="font-weight-bold">Tender Cost Of Item:</label>
                           <input type="text" class="form-control" id="tndcost{{ $emb3->b_item_id }}" name="tndcost" value="' .$tndcostitem. '" disabled>
                         </div>
                       </div>
                       <div class="col-md-3">
                         <div class="form-group">
                           <label for="costdifference" class="font-weight-bold">Excess/Saving:</label>
                           <input type="text" class="form-control" id="costdifference{{ $emb3->b_item_id }}" name="costdifference" value="' .$costdifference. '" disabled>
                         </div>
                       </div>
                       <div class="col-md-3">
                         <div class="form-group">
                           <label for="totalcost" class="font-weight-bold">Total Uptodate Amount:</label>
                           <input type="text" class="form-control" id="totalcost{{ $emb3->b_item_id }}" name="totalcost" value="' .$totlcostitem. '" disabled>
                         </div>
                       </div>
                     </div>';



                      //workdetails
    $billtemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();

    $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

    $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');
    
    $convert=new CommonHelper();


      $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
      $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
      '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE')
 . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '</div></div>';


      DB::commit();
                    return response()->json(['html' => $html , 'billdata' => $billdata,
                    'billitemdata' => $billitemdata,
                    'lasttbillid' => $lasttbillid, 'bitemId' => $bitemId , 'itemdata' => $itemdata , 'workdetail' => $workdetail]);
                    
                    
                    
        }catch(\Exception $e)
                {
                    DB::rollback();
                    Log::error('An error Occurr during Edit RCC member box open' . $e->getMessage());
        
                    return response()->json(['error' => 'An error Occurr during Update Rcc Member' . $e->getMessage()] , 500);
                }
           }


  //upload images documents and videos controller
  public function uploadimagesdoc(Request $request)
  {
      
      try{

   $tbillid=$request->input('tbillid');
   //dd($tbillid);
   
      $mbstatusSo=DB::table('bills')->where('t_bill_Id',$tbillid)->value('mbstatus_so');
   // dd($mbstatusSo);
   if ($mbstatusSo <= 5) {
    $UpdatembstatusSO=DB::table('bills')
     ->where('t_bill_Id',$tbillid)->update(['mbstatus_so'=>5]);
     // dd($UpdatembstatusSO);
        }
   $previousPaths = DB::table('bills')->where('t_bill_id', $tbillid)->first();

 //    dd($previousPaths->photo1);
 //  dd($previousPaths->photo1,$previousPaths->photo2,$previousPaths->photo3,$previousPaths->photo4,$previousPaths->photo5,$previousPaths->doc1 , $previousPaths->doc2 , $previousPaths->vdo);
  if (!$previousPaths)
             {
                 return response()->json(['error' => 'No record found for the given t_bill_id'], 404);
             }

 // Construct paths array with null handling
 $paths = [
     'photo1' => $previousPaths->photo1 ? asset('Uploads/Photos/' . $previousPaths->photo1) : null,
     'photo2' => $previousPaths->photo2 ? asset('Uploads/Photos/' . $previousPaths->photo2) : null,
     'photo3' => $previousPaths->photo3 ? asset('Uploads/Photos/' . $previousPaths->photo3) : null,
     'photo4' => $previousPaths->photo4 ? asset('Uploads/Photos/' . $previousPaths->photo4) : null,
     'photo5' => $previousPaths->photo5 ? asset('Uploads/Photos/' . $previousPaths->photo5) : null,
   // Add other photo paths as needed
 ];

 $documentPaths = [];

 for ($i = 1; $i <= 10; $i++)
 {
     $documentFieldName = 'doc' . $i;
     $documentPaths[$documentFieldName] = $previousPaths->$documentFieldName
         ? asset('Uploads/Documents/' . $previousPaths->$documentFieldName)
         : null;
 }

     // Video path
     $videoPath = $previousPaths->vdo ? asset('Uploads/Video/' . $previousPaths->vdo) : null;


     // dd($documentPaths);
     return response()->json(['paths' => $paths,'documentPaths' => $documentPaths,'videoPath'=>$videoPath]);
 
        }catch(\Exception $e)
        {
            Log::error('An error Occurr during Upload Document box open' . $e->getMessage());
        
            return response()->json(['error' => 'An error Occurr during Upload Document box open' . $e->getMessage()] , 500);
        }

  }
  //upload here all documents and img,vdo
  public function uploadimgdocvdo(Request $request)
  {
      
        //   DB::beginTransaction();

    try{


     //  dd($request->all());
      $tbillid = $request->input('t_bill_Id');
      
            $workid=DB::table('bills')->where('t_bill_id',$tbillid)->value('work_id');
    //   dd($workid,$tbillid);

      $photo1 = $request->file('photo1');
      $photo2 = $request->file('photo2');
      $photo3 = $request->file('photo3');
      $photo4 = $request->file('photo4');
      $photo5 = $request->file('photo5');
     //  dd($photo1,$photo2,$photo3,$photo4,$photo5);
     


     $document1 = $request->file('documents1');
     $document2 = $request->file('documents2');
     $document3 = $request->file('documents3');
     $document4 = $request->file('documents4');
     $document5 = $request->file('documents5');
     $document6 = $request->file('documents6');
     $document7 = $request->file('documents7');
     $document8 = $request->file('documents8');
     $document9 = $request->file('documents9');
     $document10 = $request->file('documents10');
     $video = $request->file('video');
     // dd($video);
     // dd($document1,$document2,$document3,$document4,$document5,$document6,$document7,$document8,$document9,$document10);


      // Handle file upload
      if ($request->hasFile('photo1'))
      {
          $originalName1 = $photo1->getClientOriginalName();
          $extension1 = $photo1->getClientOriginalExtension();
          $pakageimage1Name = time() . uniqid() . $originalName1;

          // Move the uploaded file to the "public/uploads/Photos" directory with a unique name
          $photo1->move(public_path('Uploads/Photos'), $pakageimage1Name);

          // Retrieve the previous paths from the database
          $previousPaths = DB::table('bills')->where('t_bill_id', $tbillid)->first();

          // Update the database with the file name
          $UpdatedPhoto = DB::table('bills')->where('t_bill_id', $tbillid)->update([
              'photo1' => $pakageimage1Name,
          ]);
         }

          if ($request->hasFile('photo2'))
          {
             $originalName2 = $photo2->getClientOriginalName();
             $extension2 = $photo2->getClientOriginalExtension();
             $pakageimage2Name = time() . uniqid() . $originalName2;

             // Move the uploaded file to the "public/uploads/Photos" directory with a unique name
             $photo2->move(public_path('Uploads/Photos'), $pakageimage2Name);

             // Update the database with the file name for photo2
             $UpdatedPhoto = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'photo2' => $pakageimage2Name,
             ]);
         }

         if ($request->hasFile('photo3'))
         {
             $originalName3 = $photo3->getClientOriginalName();
             $extension3 = $photo3->getClientOriginalExtension();
             $pakageimage3Name = time() . uniqid() . $originalName3;

             // Move the uploaded file to the "public/uploads/Photos" directory with a unique name
             $photo3->move(public_path('Uploads/Photos'), $pakageimage3Name);

             // Update the database with the file name for photo2
             $UpdatedPhoto = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'photo3' => $pakageimage3Name,
             ]);
         }

         if ($request->hasFile('photo4'))
         {
             $originalName4 = $photo4->getClientOriginalName();
             $extension4 = $photo4->getClientOriginalExtension();
             $pakageimage4Name = time() . uniqid() . $originalName4;

             // Move the uploaded file to the "public/uploads/Photos" directory with a unique name
             $photo4->move(public_path('Uploads/Photos'), $pakageimage4Name);

             // Update the database with the file name for photo2
             $UpdatedPhoto = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'photo4' => $pakageimage4Name,
             ]);
         }
         if ($request->hasFile('photo5'))
         {
             $originalName5 = $photo5->getClientOriginalName();
             $extension5 = $photo5->getClientOriginalExtension();
             $pakageimage5Name = time() . uniqid() . $originalName5;

             // Move the uploaded file to the "public/uploads/Photos" directory with a unique name
             $photo5->move(public_path('Uploads/Photos'), $pakageimage5Name);

             // Update the database with the file name for photo2
             $UpdatedPhoto = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'photo5' => $pakageimage5Name,
             ]);
         }

         if ($request->hasFile('documents1'))
         {
             $document1 = $request->file('documents1');
             $originalName1 = $document1->getClientOriginalName();
             $extension1 = $document1->getClientOriginalExtension();
             $packageDocument1Name = time() . uniqid() . $originalName1;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document1->move(public_path('Uploads/Documents'), $packageDocument1Name);

             // Update the database with the file name for Document 1
             $UpdatedDocument1 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc1' => $packageDocument1Name,
             ]);
         }

         // Handle Document 2
         if ($request->hasFile('documents2'))
         {
             $document2 = $request->file('documents2');
             $originalName2 = $document2->getClientOriginalName();
             $extension2 = $document2->getClientOriginalExtension();
             $packageDocument2Name = time() . uniqid() . $originalName2;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document2->move(public_path('Uploads/Documents'), $packageDocument2Name);

             // Update the database with the file name for Document 2
             $UpdatedDocument2 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc2' => $packageDocument2Name,
             ]);
         }

         // Handle Document 3
         if ($request->hasFile('documents3'))
         {
             $document3 = $request->file('documents3');
             $originalName3 = $document3->getClientOriginalName();
             $extension3 = $document3->getClientOriginalExtension();
             $packageDocument3Name = time() . uniqid() . $originalName3;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document3->move(public_path('Uploads/Documents'), $packageDocument3Name);

             // Update the database with the file name for Document 3
             $UpdatedDocument3 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc3' => $packageDocument3Name,
             ]);
         }

         if ($request->hasFile('documents4'))
          {
             $document4 = $request->file('documents4');
             $originalName4 = $document4->getClientOriginalName();
             $extension4 = $document4->getClientOriginalExtension();
             $packageDocument4Name = time() . uniqid() . $originalName4;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document4->move(public_path('Uploads/Documents'), $packageDocument4Name);

             // Update the database with the file name for Document 3
             $UpdatedDocument4 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc4' => $packageDocument4Name,
             ]);
         }

         if ($request->hasFile('documents5'))
          {
             $document5 = $request->file('documents5');
             $originalName5 = $document5->getClientOriginalName();
             $extension5 = $document5->getClientOriginalExtension();
             $packageDocument5Name = time() . uniqid() . $originalName5;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document5->move(public_path('Uploads/Documents'), $packageDocument5Name);

             // Update the database with the file name for Document 3
             $UpdatedDocument5 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc5' => $packageDocument5Name,
             ]);
         }

         if ($request->hasFile('documents6'))
         {
             $document6 = $request->file('documents6');
             $originalName6 = $document6->getClientOriginalName();
             $extension6 = $document6->getClientOriginalExtension();
             $packageDocument6Name = time() . uniqid() . $originalName6;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document6->move(public_path('Uploads/Documents'), $packageDocument6Name);

             // Update the database with the file name for Document 3
             $UpdatedDocument6 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc6' => $packageDocument6Name,
             ]);
         }

         if ($request->hasFile('documents7'))
         {
             $document7 = $request->file('documents7');
             $originalName7 = $document7->getClientOriginalName();
             $extension7 = $document7->getClientOriginalExtension();
             $packageDocument7Name = time() . uniqid() . $originalName7;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document7->move(public_path('Uploads/Documents'), $packageDocument7Name);

             // Update the database with the file name for Document 3
             $UpdatedDocument7 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc7' => $packageDocument7Name,
             ]);
         }

         if ($request->hasFile('documents8'))
         {
             $document8 = $request->file('documents8');
             $originalName8 = $document8->getClientOriginalName();
             $extension8 = $document8->getClientOriginalExtension();
             $packageDocument8Name = time() . uniqid() . $originalName8;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document8->move(public_path('Uploads/Documents'), $packageDocument8Name);

             // Update the database with the file name for Document 3
             $UpdatedDocument8 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc8' => $packageDocument8Name,
             ]);
         }

         if ($request->hasFile('documents9'))
         {
             $document9 = $request->file('documents9');
             $originalName9 = $document9->getClientOriginalName();
             $extension9 = $document9->getClientOriginalExtension();
             $packageDocument9Name = time() . uniqid() . $originalName9;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document9->move(public_path('Uploads/Documents'), $packageDocument9Name);

             // Update the database with the file name for Document 3
             $UpdatedDocument9 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc9' => $packageDocument9Name,
             ]);
         }

         if ($request->hasFile('documents10'))
          {
             $document10 = $request->file('documents10');
             $originalName10 = $document10->getClientOriginalName();
             $extension10 = $document10->getClientOriginalExtension();
             $packageDocument10Name = time() . uniqid() . $originalName10;

             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $document10->move(public_path('Uploads/Documents'), $packageDocument10Name);

             // Update the database with the file name for Document 3
             $UpdatedDocument10 = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'doc10' => $packageDocument10Name,
             ]);
         }


         if ($request->hasFile('video'))
         {
             $video = $request->file('video');
             // dd($video);
             $originalvideo = $video->getClientOriginalName();
             $extension10 = $video->getClientOriginalExtension();
             $packagevideo = time() . uniqid() . $originalvideo;
             // Move the uploaded document to the "public/uploads/Documents" directory with a unique name
             $video->move(public_path('Uploads/Video'), $packagevideo);
             // Update the database with the file name for the video
             $updatedVideo = DB::table('bills')->where('t_bill_id', $tbillid)->update([
                 'vdo' => $packagevideo,
             ]);
         }
         
         // Success message
    Session::flash('success', 'Files uploaded successfully!');
         // dd($tbillid);
        //   return redirect('/editbill/' . $tbillid);
                 return redirect()->route('billlist', ['workid' => $workid]);

 }catch(\Exception $e)
        {
            // DB::rollback();
           Log::error('An error Occurr during Upload documents' . $e->getMessage());
           
           // Return a JSON response with the error details for the frontend
           return redirect()->back()->with('error', 'An error occurred during upload documents: ' . $e->getMessage());
        }       
  }




public function getPaginatedData(Request $request) 
{

      // Retrieve the session variables set previously
     //$tbillid = session('tbillid');
     
    $tbillid = $request->input('rabillid');
    //dd($tBillid);
    //$tbillid = $request->input('rabillid');
    $workid = $request->input('workid');
    $perPage = $request->input('per_page', 5); // You can set a default per page value
    $embsection3 = DB::table('bil_item')->where('t_bill_id' , $tbillid)->orderby('t_item_no', 'asc')->paginate($perPage);

    $gotembbutton=0;
    $normalmeasdata=DB::table('embs')->where('t_bill_id' , $tbillid)->get();
    $stlmeas=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->get();
    if(count($normalmeasdata) > 0 || count($stlmeas) > 0)
    {

        $gotembbutton=1;
       // dd($gotembbutton);
    }

    $billdata=DB::table('bills')->where('t_bill_Id' ,  $tbillid)->first();
    //dd($tbillid);
    $total=$billdata->c_part_a_amt + $billdata->c_part_b_amt;
    //dd($total);
   // dd($gotembbutton);
  //dd($embsection3);
    return response()->json( ['embsection3' => $embsection3, 'billdata' => $billdata, 'tbillid' => $tbillid , 'total' => $total , 'gotembbutton' => $gotembbutton ,  'links' => $embsection3->links('pagination::bootstrap-4')->toHtml()] );// Pagination links
}
//refresh pagination
// public function pagination(Request $request)
//     {
//         $tbillid = $request->input('rabillid'); // Access the 'rabillid' parameter from the GET request

//         //dd($rabillid);
//         // Now you can use $rabillid in your logic for pagination or data retrieval

//         $Work_Id = DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');

//         // Store the $workId in a session variable
//     //session(['workId' => $workId]);

//         // Fetch workmasters information based on work_id
//         $embsection1 = DB::table('workmasters')
//         //     ->leftjoin('workmasters', 'embs.Work_Id', '=', 'workmasters.workid')
//            ->leftjoin('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
//            ->leftjoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
//             ->leftjoin('jemasters', 'jemasters.subdiv_id', '=', 'workmasters.Sub_Div_Id')
//            ->where('workmasters.Work_Id', '=', $Work_Id)
//            ->select('workmasters.Work_Id', 'workmasters.Sub_Div', 'workmasters.Agency_Nm', 'workmasters.Work_Nm', 'workmasters.F_H_Code', 'divisions.div', 'jemasters.name')
//            ->first();
//    //dd($embsection1);
//        // Fetch embsection1a data
//        $embsection1a = DB::table('fundhdms')
//            ->select('fundhdms.Fund_Hd_M')
//            ->leftJoin('workmasters', function ($join) {
//                $join->on(DB::raw('LEFT(workmasters.F_H_Code, 4)'), '=', DB::raw('LEFT(fundhdms.F_H_Code, 4)'));
//            })
//            ->where('workmasters.Work_Id', $Work_Id)
//            ->first();

//        // Fetch embsection2 data
//        $embsection2 = DB::table('bills')
//            ->leftjoin('embs', 'embs.t_bill_id', '=', 'bills.t_bill_id')
//            ->join('workmasters', 'bills.work_id', '=', 'workmasters.Work_Id')
//            ->where('workmasters.Work_Id', '=', $Work_Id)
//            ->where('bills.t_bill_Id', '=' , $tbillid)
//            ->select('bills.*')
//            ->first();



//            $newmeasdtfrformat = $embsection2->meas_dt_from ?? null;
//            $newmeasdtfr = date('d-m-Y', strtotime($newmeasdtfrformat));
//         $newmessuptoformat=$embsection2->meas_dt_upto ?? null;
//         $newmessupto = date('d-m-Y', strtotime($newmessuptoformat));
//       $formatpreviousbilldt=$embsection2->previousbilldt ?? null;
//       $previousbilldt = date('d-m-Y', strtotime($formatpreviousbilldt));
//    //dd($embsection2);
//        // Fetch billNos based on work_id
//        $billNos = DB::table('bills')
//            ->where('work_id', $Work_Id)
//            ->orderBy('t_bill_No', 'desc')
//            ->pluck('t_bill_No', 't_bill_id');

//     //    // Fetch embsection3 data based on work_id
//     //    $embsection3 = DB::table('bil_item')
//     //        ->leftjoin('bills', 'bills.t_bill_Id', '=', 'bil_item.t_bill_Id')
//     //        ->leftjoin('tnd_item', 'tnd_item.t_item_id', '=', 'bil_item.t_item_id')
//     //        ->leftjoin('embs', 'embs.b_item_id', '=', 'bil_item.b_item_id')
//     //        ->where('bills.work_id', '=', $workId)
//     //        ->orderBy('b_item_id', 'desc')
//     //        ->select('bil_item.*')
//     //        ->get();

//      // Get the last t_bill_Id
// $lastTBillId = DB::table('bills')
//     ->where('work_id', '=', $Work_Id)
//     ->orderBy('t_bill_Id', 'desc')
//     ->value('t_bill_Id');
//  //dd($lastTBillId);
// // Get all records related to the last t_bill_Id
// $embsection3 = DB::table('bil_item')
//     ->leftJoin('bills', 'bills.t_bill_Id', '=', 'bil_item.t_bill_Id')
//     ->leftJoin('tnditems', 'tnditems.t_item_id', '=', 'bil_item.t_item_id')
//     //->leftjoin('embs', 'embs.b_item_id', '=', 'bil_item.b_item_id')
//     ->where('bil_item.t_bill_Id', '=', $tbillid)
//     ->where('bills.t_bill_Id', '=', $tbillid)
//     ->orderBy('bil_item.t_bill_Id', 'desc')
//     ->select('bil_item.*')
//     ->paginate(5); // Paginate the results with 5 items per page
//     //dd($embsection3);
//     $paginationHtml = view('listemb', compact('embsection1', 'embsection1a', 'embsection2', 'embsection3', 'billNos' , 'newmeasdtfr' , 'newmessupto' , 'previousbilldt'))->render();
//     return redirect()->route('emb.workmasterdata', ['id' => $Work_Id]);

//    }

//    protected $listeners = ['resetPagination'];

//    public function resetPagination()
//    {
//        // Logic to reset pagination to the first page
//        $this->gotoPage(1); // Assuming 'gotoPage' is the method to navigate to a specific page
//    }


   public function progressvalue(Request $request)
   {
$workid=$request->workId;
//dd($workid);

    $latestbillid=DB::table('bills')->where('work_id' , $workid)->orderby('t_bill_id' , 'desc')->value('t_bill_id');

    $normalmeas=DB::table('embs')->where('t_bill_id' , $latestbillid)->get();

    $stlmeas=DB::table('stlmeas')->where('t_bill_id' , $latestbillid)->get();
//dd($normalmeas);
    if (!$normalmeas->isEmpty() || !$stlmeas->isEmpty()) 
    {
        // Perform update in your table here
        // For example:
        DB::table('bills')
            ->where('t_bill_id', $latestbillid)
            ->update(['mb_status' => 2]);
    }


   }

}

// Get the newly created bill information
    // $newBill = DB::table('bills')
    // ->where('t_bill_id', $newBillId)
    // ->where('t_bill_no', $newBillNo)
    // ->first();
    // $embsection2 = DB::table('bills')
    // ->where('bills.t_bill_id', '=',  $newBillId)
    // ->select('bills.*')
    // ->first();
