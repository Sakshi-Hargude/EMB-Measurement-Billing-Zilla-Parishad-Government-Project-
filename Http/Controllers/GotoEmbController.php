<?php
namespace App\Http\Controllers;
use App\Models\Emb;
use App\Models\Workmaster;
use DateTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\MBStatusUpdatedMail;
use App\Helpers\CommonHelper;
use App\Mail\RevertMBNotification;



//Check list of Junior engineer controller
class GotoEmbController extends Controller
{
    //public varaible declare
    public $dataid1;

    //function return to bills page
    public function FunReturnListBills(Request $request, $workid) {
   // This function handles the redirection to the bill list route with the given work ID

    // Retrieve the input values from the request (although they are not used in this function)
        $WorkId = $request->input('WorkId');

        $tBillNo = $request->input('t_bill_No');
        $billDate = $request->input('Bill_Dt');
        $tbillid = $request->input('t_bill_Id');
        // $workid=$WorkId;

         // Redirect to the route 'billlist' with the provided workid parameter
        return redirect()->route('billlist', ['workid' => $workid]);
    }

    // common data function...
    public function GotoEmbCntlr(Request $request) {

        // This function prepares common data and navigates to the EMB controller

         // Retrieve input values from the request
        $WorkId = $request->input('workid');
        $tBillNo = $request->input('t_bill_No');
        $billDate = $request->input('Bill_Dt');
        $tbillid = $request->input('t_bill_Id');


    // Store $billDate in a session variable
    $request->session()->put('billDate', $billDate);

    $commonheader=$this->commongotoembcontroller($WorkId , $tBillNo,$billDate,$tbillid,1);
    //dd($commonheader);
    return $commonheader;

    }

public function commongotoembcontroller($WorkId , $tBillNo,$billDate,$tbillid,$recnovalues)
{
     // This function prepares and handles data operations related to the EMB controller
        
      // Retrieve bill items associated with the given bill ID
        $bitemid = DB::table('bil_item')
            ->where('t_bill_id', '=', $tbillid)
            ->get('b_item_id');

             // Iterate through each bill item ID
        foreach ($bitemid as $items) {
            $bitemId = $items->b_item_id;
            $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->get()->value('item_id');

            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017",
                    "002023", "002024", "003351", "003352", "003878"]))
            {
                //dd("Steel Data");
            } else {
                //dd("Normal data ");
            }
        }

        // Retrieve all bill items for the given bill ID
        $bitemsnm = DB::table('bil_item')
            ->where('t_bill_id', '=', $tbillid)
            ->get();

        // Check if records exist in the 'recordms' table for the given bill ID
        $exists = DB::table('recordms')
            ->where('t_Bill_Id', $tbillid)
            ->get();

             // If records exist, delete them
        if ($exists) {
            DB::table('recordms')
                ->where('t_Bill_Id', $tbillid)
                ->where('Work_Id', '=', $WorkId)
                ->delete();

        }
        // dd("Record is deleted");

         // Retrieve distinct measurement dates from the 'embs' table for the given bill ID
            $embsd = DB::table('embs')
            ->select('measurment_dt')
            ->distinct()
            ->where('t_bill_id', '=', $tbillid)
            ->get();
            //dd($embsd);

              // Retrieve distinct measurement dates from the 'stlmeas' table for the given bill ID
            $stlmeasd = DB::table('stlmeas')
                ->select('date_meas')
                ->distinct()
                ->where('t_bill_id', '=', $tbillid)
                ->get();
    //dd($stlmeasd);

     // Merge and get unique measurement dates from both tables
    $combinedCollection = $stlmeasd->merge($embsd);
    $mergeddts = $combinedCollection->all();
    //dd($mergeddts);
    //dd($mergeddts[]->measurment_dt);
    //dd($mergeddts[0]->date_meas);
    $obdata = [];

       // Format and collect unique dates
    foreach ($mergeddts as $dateStr) {
        if(isset($dateStr->date_meas) && !empty($dateStr->date_meas)){
        $dates = Carbon::createFromFormat('Y-m-d',  $dateStr->date_meas)->format('Y-m-d');
            $dateArray[] = $dateStr->date_meas;
        // $commaSeparatedDates = implode(', ', $dateArray);
        }

        if (isset($dateStr->measurment_dt) && !empty($dateStr->measurment_dt)) {
            $dates = Carbon::createFromFormat('Y-m-d', $dateStr->measurment_dt)->format('Y-m-d');
            $dateArray[] = $dateStr->measurment_dt;
        }
    }
    // dd($dateArray);

    // Fetchng only unique date remove duplicate from both array....
    $dateArray1 =array_unique($dateArray);

    // Sort the array in ascending order
    sort($dateArray1);
    //dd($dateArray1);

        // Iterate through each unique date
    foreach($dateArray1 as $dtarr){

        $lastrecordEntryId = DB::table('recordms')
            ->select('Record_Entry_Id')
            ->where('t_bill_id', '=', $tbillid)
            ->orderBy('Record_Entry_Id', 'desc')
            ->first();
        if ($lastrecordEntryId) {
            $lastrecordid = $lastrecordEntryId->Record_Entry_Id;
            $lastFourDigits = substr($lastrecordid, -4);
            $incrementedLastFourDigits = str_pad(intval($lastFourDigits) + 1, 4, '0', STR_PAD_LEFT);
            $newrecordentryid = $tbillid . $incrementedLastFourDigits;
        }
        else {
            $newrecordentryid = $tbillid . '0001';
        }

          // Determine the new record entry number based on the last record entry number
        $Record_Entry_No = DB::table('recordms') ->select('Record_Entry_No')
        ->where('t_bill_id', '=', $tbillid)
        ->orderBy('Record_Entry_No', 'desc')
        ->value('Record_Entry_No');
        //dd($tbillid);

         // Retrieve data from 'embs' and 'stlmeas' tables for the given bill ID and measurement date
        $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $dtarr)->get();
        //dd($NormalDb);
        $lastFourDigits = substr($Record_Entry_No, -1);
        $incrementedLastFourDigits = str_pad(intval($lastFourDigits) + 1, 4, '0', STR_PAD_LEFT);
        // dd($incrementedLastFourDigits);
        $FinalRecordEntryNo = str_pad(intval($Record_Entry_No) + 1, 4, '0', STR_PAD_LEFT);
        //dd($dateArray);
       //Bill Item Table Related data="
       $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $dtarr)->get();
       //dd($NormalDb);

       $StillDb = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $dtarr)->get();
       //dd($StillDb);
       // $countcombinarray=count($StillDb);
           //dd($countcombinarray);
       //$combinarray = $NormalDb+$StillDb;

         // Combine data from both tables
       $combinarray = $NormalDb->concat($StillDb);
       //dd($combinarray);

       //Count of combine data...
       $countcombinarray=count($combinarray);
     
       // Count the dye-checked records in both tables for the given bill ID and measurement date
        $Stldyechkcount1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $dtarr)->where('dye_check',"=",1)->get();
        $Stldyechkcount=count($Stldyechkcount1);
         //dd($Stldyechkcount);

        $EmbdyeChkCount = DB::table('embs')
        ->where('t_bill_id', $tbillid)
        ->where('measurment_dt', $dtarr)
        ->where('dye_check', "=", 1)
        ->count();
         //dd($EmbdyeChkCount);

        $Count_Chked_Emb_Stl= $EmbdyeChkCount + $Stldyechkcount;

      // Insert records into 'recordms' table based on dye-check counts
        if ($Count_Chked_Emb_Stl === $countcombinarray) {
             //dd("IFFFFFFFFFFFFFFFFF;") ;
            DB::table('recordms')
                ->where('Work_Id', '=', $WorkId)
                // ->where('t_Bill_Id', '=', $tbillid)
                ->insert([
                    'Work_Id' => $WorkId,
                    'Record_Entry_Id' => $newrecordentryid,
                    't_Bill_Id' => $tbillid,
                    'Record_Entry_No' => $FinalRecordEntryNo,
                    'Rec_date' => $dtarr,
                    'Dye_Check' => 1,
                    'Dye_Check_Dt' => $dtarr,
                    'JE_Check' => 0,
                    'JE_Check_Dt' => $dtarr,
                    'ee_check' => 0,
                    'ee_chk_dt' => null
                ]);
        }

        else{
        //    dd("Elseeeeeeeeeeeee");
            DB::table('recordms')
                ->where('Work_Id', '=', $WorkId)
                // ->where( 't_Bill_Id' ,'=', $tbillid)
                ->insert([
                    'Work_Id' => $WorkId,
                    'Record_Entry_Id' => $newrecordentryid,
                    't_Bill_Id' => $tbillid,
                    'Record_Entry_No' => $FinalRecordEntryNo,
                    'Rec_date' => $dtarr,
                    'Dye_Check'=>0,
                    'Dye_Check_Dt'=>$dtarr,
                    'JE_Check'=>0,
                    'JE_Check_Dt'=>$dtarr,
                    'ee_check'=>0,
                    'ee_chk_dt'=>null
                ]);
        }
        // dd("Inserted successfilly");
    }

    //workdetails  from workmaster
        $workDetails = DB::table('workmasters')
            ->select('Work_Nm', 'Sub_Div', 'Agency_Nm', 'Work_Id', 'Wo_Dt', 'Period', 'Stip_Comp_Dt','WO_No','jeid', 'Tender_Id')
            ->where('Work_Id', '=', $WorkId)
            // ->where('t_bill_Id', '=', $tbillid)
            ->first();

         $workdata=DB::table('workmasters')->where('Work_Id' , $WorkId)->first();

         //fund head data
            $fund_Hd = DB::table('fundhdms')->where('F_H_id' , $workdata->F_H_id)->first();
            
  
        // $fund_Hd = DB::table('workmasters')
        //     ->select('fundhdms.Fund_HD_M')
        //     ->join('fundhdms', function ($join) use ($WorkId) {
        //         $join->on(DB::raw("LEFT(workmasters.F_H_Code, 4)"), '=', DB::raw("LEFT(fundhdms.F_H_CODE, 4)"))
        //             ->where('workmasters.Work_Id', '=', $WorkId);
        //     })
        //     ->first();

      //record data information
        $recinfo=  DB::table('recordms')
                ->where('Work_Id', '=', $WorkId)
                ->get();
                //dd($recinfo);

                //division name given work id related
        $divName = DB::table('workmasters')
            ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
            ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
            ->where('workmasters.Work_Id', '=', $WorkId)
            ->value('divisions.div');


        //sectional engineer
        $sectionEngineer = DB::table('jemasters')->select('name')->where('jeid',$workDetails->jeid)->get();
        //dd($sectionEngineer);

        //division  data
        $divNm = DB::table('workmasters')
            ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
            ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
            ->where('workmasters.Work_Id', '=', $WorkId)
            ->value('divisions.div');

        $titemno = DB::table('bil_item')
            ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc', 'exec_qty', 'item_unit')
            ->where('t_bill_id', '=', $tbillid)
            ->get();

        $embdtls = DB::table('embs')
            ->where('Work_Id', '=', $WorkId)
            ->first();

        $Item1Data = DB::table('embs')
            ->leftJoin('bil_item', 'embs.b_item_id', '=', 'bil_item.b_item_id')
            ->leftJoin('recordms', 'embs.t_bill_id', '=', 'recordms.t_bill_id')
            ->where('embs.t_bill_id', $tbillid)
            ->select('bil_item.t_item_no', 'bil_item.item_desc', 'bil_item.exec_qty',
                'bil_item.item_unit', 'bil_item.ratecode', 'bil_item.bill_rt', 'embs.*')
            ->get();

        $RecordData = DB::table('embs')
            ->leftJoin('bil_item', 'embs.b_item_id', '=', 'bil_item.b_item_id')
            ->leftJoin('recordms', 'embs.t_bill_id', '=', 'recordms.t_bill_id')
            ->where('embs.t_bill_id', $tbillid)
            ->select('bil_item.*', 'embs.*')
            ->orderby('measurment_dt', 'asc')
            ->get();
            //dd($RecordData);


        //   dd($workDetails->	jeid);

        $titemnoRecords = DB::table('bil_item')
            ->select('t_item_no', 'item_desc', 'exec_qty', 'ratecode', 'bill_rt')
            ->where('t_bill_id', '=', $tbillid)
            ->get();

        $Recordwise = DB::table('recordms')
        ->where('t_bill_id', '=', $tbillid)
        ->get();


        $billsdata = DB::table('bills')
        ->where('t_bill_Id', '=', $tbillid)
        ->first();
          // Redirect to the 'JuniorEngineerEMB' view with the given WorkId
        return view('JuniorEngineerEMB', compact('WorkId','workDetails', 'fund_Hd', 
        'sectionEngineer', 'divName', 'Recordwise', 'divNm', 'bitemid', 'FinalRecordEntryNo', 'titemnoRecords',
         'billDate', 'embdtls', 'Item1Data', 'RecordData', 'tbillid', 'titemno', 'itemid','recnovalues' , 'billsdata'));

}

    // Recordentry wise data ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// JE Recordentrywise function............
// public function FunRecordData(Request $request)
// {
//     $tbillid = $request->input('tbillid_valuer');
//     //dd($tbillidv);
//     $itemidv =$request->input('itemid_valuer');

//     $WorkIdvv =$request->input('WorkId_valuer');
//     //dd($WorkIdvv,$itemidv,$tbillidv);

//     $Rec_E_No=$request->input('Record_Entry_Nor');

//     $html ='';

//     $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();


    // $recdate = DB::table('recordms')
    // ->select('Rec_date')
    // ->where('t_bill_id', $tbillid)
    // ->where('Record_Entry_No', $Rec_E_No)
    // ->value('Rec_date');

//     $RecDate = date("d/m/Y", strtotime($recdate));

//         foreach($billitemdata as $itemdata)
//         {
//         $bitemId=$itemdata->b_item_id;
//         //dd($bitemId);
//         $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $recdate)->get();
//         $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $recdate)->get();
//         //meas data check
//         if (!$measnormaldata->isEmpty() || !$meassteeldata->isEmpty()) {

//             $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><tr><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 90%; text-align: justify;"> ' . $itemdata->item_desc . '</th></tr></thead></table>';

//             $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
//             //dd($itemid);
//             if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
//             {
//                 $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->get();

//                 $bill_rc_data = DB::table('bill_rcc_mbr')->get();

//                 $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
//                 'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


//                 foreach ($stldata as &$data) {
//                     if (is_object($data)) {
//                         foreach ($ldiamColumns as $ldiamColumn) {
//                             if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

//                             $temp = $data->$ldiamColumn;
//                             $data->$ldiamColumn = $data->bar_length;
//                             $data->bar_length = $temp;
//                             break; // Stop checking other ldiam columns if we found a match
//                             }
//                         }
//                     }
//                 }

//                 $sums = array_fill_keys($ldiamColumns, 0);

//                 foreach ($stldata as $row) {
//                     foreach ($ldiamColumns as $ldiamColumn) {
//                         $sums[$ldiamColumn] += $row->$ldiamColumn;
//                     }
//                 }//dd($stldata);

//                 $bill_member = DB::table('bill_rcc_mbr')
//                     ->whereExists(function ($query) use ($bitemId) {
//                     $query->select(DB::raw(1))
//                     ->from('stlmeas')
//                     ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
//                     ->where('bill_rcc_mbr.b_item_id', $bitemId);
//                     })
//                     ->get();


//                 $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

//                 $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
//                 <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 13%; min-width: 13%;">Bar Particulars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
//                 <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">6mm</th>
//                 <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">8mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">10mm</th>
//                 <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">12mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">16mm</th>
//                 <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">20mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 9%; min-width: 9%;">25mm</th>
//                 <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 9%; min-width: 9%;">28mm</th></thead>';

//                 foreach ($bill_member as $index => $member) {
//                         //dd($member);
//                     $rcmbrid=$member->rc_mbr_id;
//                     $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $recdate)->get();
//                             //dd($memberdata);

//                     if ( !$memberdata->isEmpty()) {

//                         $html .= '<table style="border-collapse: collapse; width: 100%;"><thead><th colspan="1" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Sr No :' . $member->member_sr_no . '</th><th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">RCC Member :' . $member->rcc_member . '</th><th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Member Particular :' . $member->member_particulars . '</th><th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">No Of Members :' . $member->no_of_members . '</th></thead></table>';

//                          $dyeRevert=DB::table('bills')
//                         ->where('t_bill_Id',$tbillid)
//                         ->value('dye_revert');
//                         // dd($dyeRevert);


//                         foreach ($stldata as $bar)
//                         {
//                     if($dyeRevert == 1 &&  $bar->dye_check == 0)
//                     {
//                             if ($bar->rc_mbr_id == $member->rc_mbr_id)
//                             {
//                     //dd($bar);// Assuming the bar data is within a property like "bar_data"
//                     $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
//                     $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; background-color: #cc3333;"><td style="border: 1px solid black; padding: 8px; width: 5%;  min-width: 5%; text:align:right;">'. $bar->bar_sr_no .'</td>
//                     <td style="border: 1px solid black; padding: 8px; width: 13%; min-width: 13%; text:align:right;">'. $bar->bar_particulars.'</td><td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text:align:right;">'. $bar->no_of_bars .'</td>
//                     <td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text:align:right;">'. $bar->bar_length .'</td> <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam6 .'</td>
//                     <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam8 .'</td> <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam10 .'</td>
//                     <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam12 .'</td><td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam16 .'</td>
//                     <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam20 .'</td> <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text:align:right;">'. $bar->ldiam25 .'</td>
//                     <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text:align:right;">'. $bar->ldiam28 .'</td></table>';
//                             }
//                     }

//                     else
//                         {

//                             if ($bar->rc_mbr_id == $member->rc_mbr_id)
//                             {
//                             //dd($bar);// Assuming the bar data is within a property like "bar_data"
//                             $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
//                             $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><td style="border: 1px solid black; padding: 8px; width: 5%;  min-width: 5%; text:align:right;">'. $bar->bar_sr_no .'</td>
//                             <td style="border: 1px solid black; padding: 8px; width: 13%; min-width: 13%; text:align:right;">'. $bar->bar_particulars.'</td><td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text:align:right;">'. $bar->no_of_bars .'</td>
//                             <td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text:align:right;">'. $bar->bar_length .'</td> <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam6 .'</td>
//                             <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam8 .'</td> <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam10 .'</td>
//                             <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam12 .'</td><td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam16 .'</td>
//                             <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam20 .'</td> <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text:align:right;">'. $bar->ldiam25 .'</td>
//                             <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text:align:right;">'. $bar->ldiam28 .'</td></table>';
//                             }
//                         }

//                         }
//                     }
//                 }
//             }
//             else{

//                 $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();

//             if($meassteeldata->isEmpty()){
//                 $html .= '<table class="table table-bordered table-striped" style="border-right: 1px solid black; width:100%;"><thead><th style="width: 5%; border-color: black;">Sr. No</th>
//                 <th style="width: 30%; border-color: black;">Particulars</th><th style="width: 7%; border-color: black;">Number</th><th style="width: 7%; border-color: black;">Height</th>
//                 <th style="width: 7%; border-color: black;">Length</th><th style="width: 7%; border-color: black;">Breadth</th><th style="width: 7%; border-color: black;">Quantity</th>
//                 </thead></table>';
//             }

//             $dyerevert=DB::table('bills')
//             ->where('t_bill_id',$tbillid)
//             ->value('dye_revert');
//             // dd($dyerevert);

//             foreach($normaldata as $nordata)
//             {
//                 if($dyerevert == 1 &&  $nordata->dye_check == 0)
//                 {
//                     $formula= $nordata->formula;
//                     $html .= '<table class="table table-bordered table-striped" style="border-right: 1px solid black; width:100%; "><tbody "><td style="border: 1px solid black; padding: 8px; width: 5%; background-color: #cc3333;" class=" text-align:right;">' . $nordata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 200px; text-align:left; background-color: #cc3333;">' . $nordata->parti . '</td>';
//                     if($formula)
//                     {
//                         $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right;background-color: #cc3333;">' . $nordata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #cc3333;">' . $nordata->qty . '</td>';
//                     }
//                     else
//                     {
//                         // $html .= '< style="color: red; font-size: 20px; text-color: red; font-weight:bold;">';
//                         $html .= '<td style="border: 1px solid black; padding: 8px;  width: 7%; text-align:right;background-color: #cc3333;">' . $nordata->number . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #cc3333;">' . $nordata->length . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #cc3333;">' . $nordata->breadth . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #cc3333;">' . $nordata->height . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #cc3333;">' . $nordata->qty . '</td>';

//                     }
//                     $html .='</tbody></table>';
//                 }

//                 else
//                 {
//                 $formula= $nordata->formula;
//                 $html .= '<table class="table table-bordered table-striped" style="border-right: 1px solid black; width:100%;"><tbody><td style="border: 1px solid black; padding: 8px; width: 5%;" class=" text-align:right;">' . $nordata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 200px; text-align:left;">' . $nordata->parti . '</td>';
//                 if($formula)
//                     {
//                     $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right;">' . $nordata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->qty . '</td>';
//                     }
//                 else
//                     {
//                     // $html .= '< style="color: red; font-size: 20px; text-color: red; font-weight:bold;">';
//                     $html .= '<td style="border: 1px solid black; padding: 8px;  width: 7%; text-align:right;">' . $nordata->number . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->length . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->breadth . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->height . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->qty . '</td>';

//                     }
//                 $html .='</tbody></table>';
//                 }

//             }
//         }
//         }
//          $TMTdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();
//     // dd($TMTdata);
//     foreach($TMTdata as $tmtdata){
//         if($tmtdata->b_item_id == $itemdata->b_item_id){
//             $html .= '<table class="table table-bordered table-striped" style="border-right: 1px solid black; width:100%;"><tbody><td style="border: 1px solid black; padding: 8px; width: 5%;" class=" text-align:right;"></td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 200px; text-align:left;">' . $tmtdata->parti . '</td>';

//             $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right;">' . $tmtdata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $tmtdata->qty . '</td>';
//             $html .='</tbody></table>';
//             }
//         }
//         }


//     return response()->json(['html' => $html,'RecDate'=>$RecDate]);
// }

// JE Recordentrywise function............
public function FunRecordData(Request $request)
{
    try
    {
         // Retrieve input values from the request
    $tbillid = $request->input('tbillid_valuer');
    //dd($tbillidv);
    $itemidv =$request->input('itemid_valuer');

    $WorkIdvv =$request->input('WorkId_valuer');
    //dd($WorkIdvv,$itemidv,$tbillidv);

    $Rec_E_No=$request->input('Record_Entry_Nor');

    $html ='';

     // Fetch bill item data based on the bill ID
    $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();

    // Fetch the record date based on bill ID and record entry number
    $recdate = DB::table('recordms')
    ->select('Rec_date')
    ->where('t_bill_id', $tbillid)
    ->where('Record_Entry_No', $Rec_E_No)
    ->value('Rec_date');

      // Format the fetched record date
    $RecDate = date("d/m/Y", strtotime($recdate));

    // Iterate through each bill item data
        foreach($billitemdata as $itemdata)
        {
        $bitemId=$itemdata->b_item_id;

       // Fetch measurement data based on item ID and record date
        $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $recdate)->get();
        $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $recdate)->get();

       // Check if there is any measurement data available
        if (!$measnormaldata->isEmpty() || !$meassteeldata->isEmpty()) {

             // Generate HTML for item information
            $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><tr><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 90%; text-align: justify;"> ' . $itemdata->exs_nm . '</th></tr></thead></table>';

                // Check for specific item IDs to determine if it is steel measurement data
            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
            //dd($itemid);
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
            {
                 // Fetch steel measurement data and update specific columns
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->get();

                $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                 // List of diameter columns
                $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
                'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

                 // Swap bar length and diameter values if conditions met
                foreach ($stldata as &$data) {
                    if (is_object($data)) {
                        foreach ($ldiamColumns as $ldiamColumn) {
                            if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                            $temp = $data->$ldiamColumn;
                            $data->$ldiamColumn = $data->bar_length;
                            $data->bar_length = $temp;
                            break; // Stop checking other ldiam columns if we found a match
                            }
                        }
                    }
                }

                // Calculate sums of all diameter columns
                $sums = array_fill_keys($ldiamColumns, 0);

                foreach ($stldata as $row) {
                    foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                    }
                }//dd($stldata);

                 // Fetch bill RCC member data
                $bill_member = DB::table('bill_rcc_mbr')
                    ->whereExists(function ($query) use ($bitemId) {
                    $query->select(DB::raw(1))
                    ->from('stlmeas')
                    ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                    ->where('bill_rcc_mbr.b_item_id', $bitemId);
                    })
                    ->get();


                $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

                 // Generate HTML for RCC member data
                $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 13%; min-width: 13%;">Bar Particulars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">6mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">8mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">10mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">12mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">16mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">20mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 9%; min-width: 9%;">25mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 9%; min-width: 9%;">28mm</th></thead>';

                // Generate HTML for each RCC member
                foreach ($bill_member as $index => $member) {
                        //dd($member);
                    $rcmbrid=$member->rc_mbr_id;
                    $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $recdate)->get();
                            //dd($memberdata);

                            // Check if member data is not empty
                    if ( !$memberdata->isEmpty()) {

                          // Construct table header with member details
                        $html .= '<table style="border-collapse: collapse; width: 100%;"><thead><th colspan="1" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Sr No :' . $member->member_sr_no . '</th><th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">RCC Member :' . $member->rcc_member . '</th><th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Member Particular :' . $member->member_particulars . '</th><th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">No Of Members :' . $member->no_of_members . '</th></thead></table>';

                         $dyeRevert=DB::table('bills')
                        ->where('t_bill_Id',$tbillid)
                        ->value('dye_revert');
                        // dd($dyeRevert);


                        foreach ($stldata as $bar)
                        {
                             // Fetch dye_revert value from bills table
                    if($dyeRevert == 1 &&  $bar->dye_check == 0)
                    {
                            if ($bar->rc_mbr_id == $member->rc_mbr_id)
                            {
                     // Format date_meas and construct table row with specific background color
                    $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; background-color: #FFD699;"><td style="border: 1px solid black; padding: 8px; width: 5%;  min-width: 5%; text:align:right;">'. $bar->bar_sr_no .'</td>
                    <td style="border: 1px solid black; padding: 8px; width: 13%; min-width: 13%; text:align:right;">'. $bar->bar_particulars.'</td><td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text:align:right;">'. $bar->no_of_bars .'</td>
                    <td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text:align:right;">'. $bar->bar_length .'</td> <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam6 .'</td>
                    <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam8 .'</td> <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam10 .'</td>
                    <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam12 .'</td><td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam16 .'</td>
                    <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam20 .'</td> <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text:align:right;">'. $bar->ldiam25 .'</td>
                    <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text:align:right;">'. $bar->ldiam28 .'</td></table>';
                            }
                    }

                    else
                        {

                            if ($bar->rc_mbr_id == $member->rc_mbr_id)
                            {
                            //dd($bar);// Assuming the bar data is within a property like "bar_data"
                            $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                            $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><td style="border: 1px solid black; padding: 8px; width: 5%;  min-width: 5%; text:align:right;">'. $bar->bar_sr_no .'</td>
                            <td style="border: 1px solid black; padding: 8px; width: 13%; min-width: 13%; text:align:right;">'. $bar->bar_particulars.'</td><td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text:align:right;">'. $bar->no_of_bars .'</td>
                            <td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text:align:right;">'. $bar->bar_length .'</td> <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam6 .'</td>
                            <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam8 .'</td> <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam10 .'</td>
                            <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam12 .'</td><td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam16 .'</td>
                            <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text:align:right;">'. $bar->ldiam20 .'</td> <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text:align:right;">'. $bar->ldiam25 .'</td>
                            <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text:align:right;">'. $bar->ldiam28 .'</td></table>';
                            }
                        }

                        }
                    }
                }
                  // Fetch and iterate TMT data
                $TMTdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $recdate)->get();
                    //  dd($TMTdata);
                    if ($TMTdata->isNotEmpty()) {
                        foreach ($TMTdata as $tmtdata) {
                            if (strpos($tmtdata->parti, 'TMT') === 0) {
                                // dd($tmtdata);
                                $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; "><tbody><td style="border: 1px solid black; padding: 8px; width: 5%; text-align:left;">' . $tmtdata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 40px; text:align:left;">' . $tmtdata->parti . '</td>';

                                $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right;">' . $tmtdata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $tmtdata->qty . '</td>';

                            }
                        }
                    }
                }

            else{

                 // Fetch data from 'embs' table based on provided criteria
                $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();

                  // If there is no data in 'meassteeldata', create a default table header
            if($meassteeldata->isEmpty()){
                $html .= '<table class="table table-bordered table-striped" style="border-right: 1px solid black; width:100%;"><thead><th style="width: 5%; border-color: black;">Sr. No</th>
                <th style="width: 30%; border-color: black;">Particulars</th><th style="width: 7%; border-color: black;">Number</th><th style="width: 7%; border-color: black;">Length</th>
                <th style="width: 7%; border-color: black;">Breadth</th><th style="width: 7%; border-color: black;">Height</th><th style="width: 7%; border-color: black;">Quantity</th>
                </thead></table>';
            }

            // Fetch the 'dye_revert' value from the 'bills' table
            $dyerevert=DB::table('bills')
            ->where('t_bill_id',$tbillid)
            ->value('dye_revert');
           // dd($dyerevert);

             // Loop through the fetched 'normaldata' records
            foreach($normaldata as $nordata)
            {
                // Check if 'dye_revert' is 1 and 'dye_check' is 0
                if($dyerevert == 1 &&  $nordata->dye_check == 0)
                {
                    $formula= $nordata->formula;
                    $html .= '<table class="table table-bordered table-striped" style="border-right: 1px solid black; width:100%; "><tbody "><td style="border: 1px solid black; padding: 8px; width: 5%; background-color: #FFD699;" class=" text-align:right;">' . $nordata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 200px; text-align:left; background-color: #FFD699;">' . $nordata->parti . '</td>';
                    if($formula)
                    {
                        $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right;background-color: #FFD699;">' . $nordata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #FFD699;">' . $nordata->qty . '</td>';
                    }
                    else
                    {
                        // $html .= '< style="color: red; font-size: 20px; text-color: red; font-weight:bold;">';
                        $html .= '<td style="border: 1px solid black; padding: 8px;  width: 7%; text-align:right;background-color: #FFD699;">' . $nordata->number . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #FFD699;">' . $nordata->length . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #FFD699;">' . $nordata->breadth . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #FFD699;">' . $nordata->height . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;background-color: #FFD699;">' . $nordata->qty . '</td>';

                    }
                    $html .='</tbody></table>';
                }

                else
                {
                $formula= $nordata->formula;
                     // If no formula is provided, display detailed dimensions
                $html .= '<table class="table table-bordered table-striped" style="border-right: 1px solid black; width:100%;"><tbody><td style="border: 1px solid black; padding: 8px; width: 5%;" class=" text-align:right;">' . $nordata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 200px; text-align:left;">' . $nordata->parti . '</td>';
                if($formula)
                    {
                    $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right;">' . $nordata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->qty . '</td>';
                    }
                else
                    {
                        // If no formula is provided, display detailed dimensions
                    // $html .= '< style="color: red; font-size: 20px; text-color: red; font-weight:bold;">';
                    $html .= '<td style="border: 1px solid black; padding: 8px;  width: 7%; text-align:right;">' . $nordata->number . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->length . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->breadth . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->height . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $nordata->qty . '</td>';

                    }
                $html .='</tbody></table>';
                }

            }
        }
        }

      }

            // $TMTdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $recdate)->get();
            // //  dd($TMTdata);
            // // foreach($TMTdata as $tmtdata){
            //     if ($TMTdata->isNotEmpty()) {
            //         foreach ($TMTdata as $tmtdata) {
            //             if (strpos($tmtdata->parti, 'TMT') === 0) {
            //                 // dd($tmtdata);
            //                 $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; "><tbody><td style="border: 1px solid black; padding: 8px; width: 5%; text-align:left;">' . $tmtdata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 40px; text:align:left;">' . $tmtdata->parti . '</td>';

            //                 $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right;">' . $tmtdata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $tmtdata->qty . '</td>';

            //             }
            //         }
            //     }
            // Return the HTML content and date as a JSON response
    return response()->json(['html' => $html,'RecDate'=>$RecDate]);
}
catch (\Exception $e) 
{
    Log::error('Error in SubmitAllEE: ' . $e->getMessage());
    // Return a JSON response with an error message
    // dd($e->getMessage());
    return response()->json(['error' => 'An error occurred when Section Engineer Record entry are changed: ' . $e->getMessage()], 500);
}

}



    // JE Itemwise function++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
public function fundata(Request $request)
{
    try
    {
    $html='';// Initialize the variable to hold HTML content


        // Get input values from the request
        $tbillid = $request->input('tbillid_value');
        // dd($tbillid);
        $recno=$request->input('recordEntryNo');
        //dd($recno);
        $itemid =$request->input('itemid_value');
        //dd($itemid);
        $WorkIdv =$request->input('WorkId_value');
        $Item1Data=0;
        $itemno=$request->input('combined_value');
        // Separate the numeric part and the last character
        $itemNo = preg_replace('/[^0-9]/', '', $itemno); // Extract all digits
        $lastCharacter = substr($itemno, -1);
        $subno=0;
        if (ctype_alpha($lastCharacter)) {
              // If the last character is a letter, set it as subno
            $subno=$lastCharacter;
            // dd($subno);
        }
        $bitemid=0;

        if($subno)
        {
             // Get b_item_id based on item number and sub number
            $bitemid=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('t_item_no' , $itemNo)
            ->where('sub_no', $subno)->value('b_item_id');
            //dd($bitemid);
        }
        else
        {   // Get b_item_id based on item number only
            $bitemid=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('t_item_no' , $itemNo)
            ->value('b_item_id');
        }

          // Retrieve item_id based on b_item_id
        $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('item_id');
        
         // Check if item_id matches any of the specified values
            if (
            in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016",
                                        "002017", "002023", "002024", "003351", "003352", "003878"])
                                        )
            {

                 // Retrieve data related to the specified bill and item
            $stldata = DB::table('stlmeas')
            ->join('bill_rcc_mbr', 'bill_rcc_mbr.rc_mbr_id', '=', 'stlmeas.rc_mbr_id')
            ->where('bill_rcc_mbr.t_bill_id', $tbillid)
            ->where('bill_rcc_mbr.b_item_id', $bitemid)
            ->get();

        //dd($rccmem);

         // Retrieve all records from the bill_rcc_mbr table
                $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                // dd($stldata , $bill_rc_data);

                 // Define column names for diameter measurements
                $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
                'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


                 // Swap bar_length with the value of the first non-null diameter column
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

                 // Initialize sums for diameter columns
                $sums = array_fill_keys($ldiamColumns, 0);

                 // Calculate the total for each diameter column
                foreach ($stldata as $row) {
                     foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                     }
                }//dd($stldata);

                // Retrieve bill members related to the specified item
                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemid) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemid);
                })
                ->get();

                  // Retrieve record ids for the bill members
            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemid)->pluck('rc_mbr_id')->toArray();

            $html='';

            // Build HTML content for each bill member
        foreach ($bill_member as $index => $member) {
             //dd($member);
                    // $rcmbrid=$member->rc_mbr_id;
                        // $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $redtValues)->get();
                //dd($memberdata);
                $memberdata = DB::table('stlmeas')
                ->join('bill_rcc_mbr', 'bill_rcc_mbr.rc_mbr_id', '=', 'stlmeas.rc_mbr_id')
                ->where('bill_rcc_mbr.t_bill_id', $tbillid)
                // ->where('t_item_no', '=', $itemno)
                ->get();
            //dd($memberdata);
                if ( !$memberdata->isEmpty()) {
                      // Create a table header for the bar measurements
                $html .= '<table style="border-collapse: collapse; width: 100%;"><thead>';
                $html .= '<th colspan="1" style="border: 1px solid black; padding: 8px; background-color:lightblue;">Sr No:' . $member->member_sr_no . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">RCC Member :' . $member->rcc_member . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Member Particular :' . $member->member_particulars . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">No Of Members :' . $member->no_of_members . '</th>';
                $html .= '</thead></table>';


                $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 25%; min-width: 25%;">Bar Particulars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 7%;">6mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">8mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 7%;">10mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">12mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 7%;">16mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">20mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">25mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">28mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 20%; min-width: 20%;">Record Entry No</th>
                    </thead>';


                     // Add rows for each bar measurement
                foreach ($stldata as $bar) {
                        if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                            // dd($bar->date_meas);
                        $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));

                        $Record_Entry_No = DB::table('recordms')

                        ->where('t_bill_id', $tbillid)
                        ->where('Rec_date', $bar->date_meas)
                        ->value('Record_Entry_No');

                       $html .= '<tr>';
                        $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
                        $html .= '<tbody>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%;  min-width: 5%; text-align:left;">'. $bar->bar_sr_no .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 25%; min-width: 25%; text-align:left;">'. $bar->bar_particulars.'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text-align:right;">'. $bar->no_of_bars .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text-align:right;">'. $bar->bar_length .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam6 .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam8 .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam10 .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam12 .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam16 .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam20 .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam25 .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam28 .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 20%; min-width: 20%; text-align:right;">'. $Record_Entry_No .'</td>';
                        $html .= '<tr>';


                            }

                        }
                    }
                    }
            $html .= '
                      // Add a footer row with totals
                    <tr>
                        <th colspan="3" style="border: 1px solid black; padding: 8px; background-color: white; text-align:right; width: 10%; min-width: 10%;"></th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: yellow; text-align:right; width: 10%; min-width: 10%;">Total Length:</th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: yellow; text-align:right; width: 5%; min-width: 5%;">' . $sums['ldiam6'] . '</th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: yellow; text-align:right; width: 5%; min-width: 5%;">' . $sums['ldiam8'] . '</th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: yellow; text-align:right; width: 5%; min-width: 5%;">' . $sums['ldiam10'] . '</th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: yellow; text-align:right; width: 5%; min-width: 5%;">' . $sums['ldiam12'] . '</th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: yellow; text-align:right; width: 5%; min-width: 5%;">' . $sums['ldiam16'] . '</th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: yellow; text-align:right; width: 5%; min-width: 5%;">' . $sums['ldiam20'] . '</th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: yellow; text-align:right; width: 5%; min-width: 5%;">' . $sums['ldiam25'] . '</th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: yellow; text-align:right; width: 5%; min-width: 5%;">' . $sums['ldiam28'] . '</th>
                        <th colspan="1" style="border: 1px solid black; padding: 8px; background-color: white; text-align:right; width: 10%; min-width: 10%;"></th>
                    </tr>
                    </tbody></table>';

        $embssumarry=DB::table('embs')->where('b_item_id' , $bitemid)->where('t_bill_id' , $tbillid)->get();

       //For TMT Woding Itemwise
         foreach($embssumarry as $nordata)
            {
                //dd($nordata);
                $formula= $nordata->formula;
                $html .= '<table style="border-collapse: collapse; width: 100%;"><tbody><td style="border: 1px solid black; padding: 8px; width: 5%;">' . $nordata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 39%; word-wrap: break-word; max-width: 200px; text-align:left;">' . $nordata->parti . '</td>';
                if($formula)
                {
                    $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 15%; text-align:right;">' . $nordata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 6%; text-align:right;">' . $nordata->qty . '</td>';
                }

                $html .='</tbody></table>';
            }
        //dd($embssumarry);
        // dd($html);
        }

            else
    {
         // Fetch data from 'embs' and 'recordms' tables where conditions match
        $Item1Data = DB::table('embs')
        ->join('recordms', 'embs.t_bill_id', '=', 'recordms.t_bill_id')
        ->where('embs.b_item_id', $bitemid)
        ->where('embs.t_bill_id', $tbillid)
        ->whereColumn('recordms.Rec_date', '=', 'embs.measurment_dt')
        ->select('embs.*', 'recordms.Record_Entry_No')
        ->get();
        // dd($Item1Data);
    }

     // Fetch the 'dye_revert' value from the 'bills' table
          $dyeRevert=DB::table('bills')
       ->where('t_bill_id', $tbillid)
       ->value('dye_revert');
    //    dd($dyeRevert);

    //dd($subno);

          // Fetch item information based on bill ID and item number
        $iteminfo = DB::table('bil_item')
        ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'exs_nm','exec_qty','item_unit','cur_amt','bill_rt','ratecode','cur_qty')
        ->where('t_bill_id', '=', $tbillid)
        ->where('t_item_no','=',$itemno)
        ->get();
    // dd($iteminfo);

   //dd($html);

        // return response()->json(['Item1Data'=>$Item1Data,'iteminfo'=>$iteminfo,'dyeRevert' =>$dyeRevert,'html'=>$html]);
if($html !==''){
    return response()->json(['Item1Data'=>$Item1Data,'iteminfo'=>$iteminfo,'html'=>$html,'dyeRevert'=>$dyeRevert]);

}
else{
    return response()->json(['Item1Data'=>$Item1Data,'iteminfo'=>$iteminfo,'dyeRevert'=>$dyeRevert]);
}
    }
    
        catch (Exception $e) 
{
    Log::error('Error in SubmitAllEE: ' . $e->getMessage());
    // Return a JSON response with an error message
    return response()->json(['error' => 'An error occurred when Section Engineer Item Wise data are changed: ' . $e->getMessage()], 500);
}

}

// Function to handle the submission of Junior Engineer data
    public function SubmitJE(Request $request)
    {
         // Retrieve the bill ID from the request
        $tbillid = $request->input('tbillid');
        // dd($tbillid);
         // Fetch the work ID associated with the provided bill ID from the 'bills' table
                $workId=DB::table('bills')
        ->where('t_bill_Id',$tbillid)
        ->value('work_id');
        // dd($workId);

 // Update the 'mb_status' field to 3 for the given bill ID in the 'bills' table
        $UpdatembstatusSO= DB::table('bills')
        ->where('t_bill_id', $tbillid)
        ->update(['dye_revert' => 0 , 'Agency_revert' => 0 , 'EE_revert' => 0]);
                
        //dd("OKkkkkkkkk");
        
        // Update the 'mbstatus_so' field to 7 for the given bill ID and work ID
       DB::table('bills')
        ->where('t_bill_Id',$tbillid)->where('work_id',$workId)
        ->update(['mbstatus_so'=>1]);
        // dd($UpdatembstatusSO);
        
        
          // Check if the update was successful
          if ($UpdatembstatusSO) {
                 

          //Email notification for MB status

          // Define the new status
          $newStatus = 3;


          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $workId)->first();

          // Fetch the Agency  details related to the given work_id
          $agencyDetails = DB::table('agencies')->where('id', $workdata->Agency_Id)->first();

          
            // Fetch the Agency  details related to the given work_id
            $fromdetails = DB::table('jemasters')->where('jeid', $workdata->jeid)->first();

          if ($agencyDetails) {
  
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);
              // Send the notification email to the JE
              Mail::to($agencyDetails->Agency_Mail)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $fromdetails , $agencyDetails));
          } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }

        }

    // Return a JSON response indicating successful handling of the AJAX request
        return response()->json(['message' => 'AJAX request handled successfully']);
    }

      //  Deuty Check1 common Function......................
    public function DeputyCheck1Fun(Request $request)
    {
       // Retrieve input values from the request
        $WorkId = $request->input('workid');

        $BillDt = $request->input('Bill_Dt');
        //  dd($BillDt);
        $tbillid = $request->input('t_bill_Id');
        //dd($tbillid);
        $tBillNo = $request->input('t_bill_No');
        //dd($tBillNo);
        $billDate = $request->input('Bill_Dt');

        $recnovalues=$request->input('recnovalues');

        // Fetch work details from 'workmasters' table based on WorkId
        $workDetails1 = DB::table('workmasters')
        ->select('Work_Id','Work_Nm', 'Sub_Div', 'Agency_Nm', 'Wo_Dt','Period','WO_No','Stip_Comp_Dt', 'Tender_Id')
        ->where('Work_Id', '=', $WorkId)
        ->first();
        //dd($workDetails1);
        
            // Fetch fund header details from 'fundhdms' table based on F_H_id
         $workdata=DB::table('workmasters')->where('Work_Id' , $WorkId)->first();
        $fund_Hd1 = DB::table('fundhdms')->where('F_H_id' , $workdata->F_H_id)->first();

        // $fund_Hd1 = DB::table('workmasters')
        // ->select('fundhdms.Fund_HD_M')
        // ->join('fundhdms', function ($join) use ($WorkId) {
        //     $join->on(DB::raw("LEFT(workmasters.F_H_Code, 4)"), '=', DB::raw("LEFT(fundhdms.F_H_CODE, 4)"))
        //         ->where('workmasters.Work_Id', '=', $WorkId);
        // })
        // ->first();
        //  dd($fund_Hd1);

         // Fetch distinct measurement dates from 'embs' and 'stlmeas' tables for the given bill ID
        $embsd = DB::table('embs')
        ->select('measurment_dt')
        ->distinct()
        ->where('t_bill_id', '=', $tbillid)
        ->get();
        //dd($embsd);

        $stlmeasd = DB::table('stlmeas')
            ->select('date_meas')
            ->distinct()
            ->where('t_bill_id', '=', $tbillid)
            ->get();
        //dd($stlmeasd);

        // $fund_Hd1 = DB::table('workmasters')
        // ->select('fundhdms.Fund_HD_M')
        // ->join('fundhdms', function ($join) use ($WorkId) {
        //     $join->on(DB::raw("LEFT(workmasters.F_H_Code, 4)"), '=', DB::raw("LEFT(fundhdms.F_H_CODE, 4)"))
        //         ->where('workmasters.Work_Id', '=', $WorkId);
        // })
        // ->first();

       // Check if records exist in 'recordms' table for the given bill ID and delete them if they do
            $exists = DB::table('recordms')
            ->where('t_Bill_Id', $tbillid)
            ->get();

        if ($exists) {
            DB::table('recordms')
                ->where('t_Bill_Id', $tbillid)
                ->where('Work_Id', '=', $WorkId)
                ->delete();

        }

          // Merge distinct measurement dates from both tables and remove duplicates
        $combinedCollection = $stlmeasd->merge($embsd);
        $mergeddts = $combinedCollection->all();
        //dd($mergeddts);
        //dd($mergeddts[]->measurment_dt);
        //dd($mergeddts[0]->date_meas);
        $obdata = [];

        foreach ($mergeddts as $dateStr) {
             // Add distinct dates to an array
            if(isset($dateStr->date_meas) && !empty($dateStr->date_meas)){
            $dates = Carbon::createFromFormat('Y-m-d',  $dateStr->date_meas)->format('Y-m-d');
                $dateArray[] = $dateStr->date_meas;
            // $commaSeparatedDates = implode(', ', $dateArray);
            }

            if (isset($dateStr->measurment_dt) && !empty($dateStr->measurment_dt)) {
                $dates = Carbon::createFromFormat('Y-m-d', $dateStr->measurment_dt)->format('Y-m-d');
                $dateArray[] = $dateStr->measurment_dt;
            }
        }
        //  dd($dateArray);

     // Remove duplicate dates and sort them in ascending order
        $dateArray1 =array_unique($dateArray);

        // Sort the array in ascending order
        sort($dateArray1);
        //dd($dateArray1);

         // Process each date to generate new record entry IDs and handle data insertion
        foreach($dateArray1 as $dtarr){

            $lastrecordEntryId = DB::table('recordms')
                ->select('Record_Entry_Id')
                ->where('t_bill_id', '=', $tbillid)
                ->orderBy('Record_Entry_Id', 'desc')
                ->first();


            if ($lastrecordEntryId) {
                $lastrecordid = $lastrecordEntryId->Record_Entry_Id;
                $lastFourDigits = substr($lastrecordid, -4);
                $incrementedLastFourDigits = str_pad(intval($lastFourDigits) + 1, 4, '0', STR_PAD_LEFT);
                $newrecordentryid = $tbillid . $incrementedLastFourDigits;
            }

            else {
                $newrecordentryid = $tbillid . '0001';
            }

            $Record_Entry_No = DB::table('recordms') ->select('Record_Entry_No')
            ->where('t_bill_id', '=', $tbillid)
            ->orderBy('Record_Entry_No', 'desc')
            ->value('Record_Entry_No');
            //dd($tbillid);

            // Fetch the last record entry number
            $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $dtarr)->get();
            //dd($NormalDb);
            $lastFourDigits = substr($Record_Entry_No, -1);
            $incrementedLastFourDigits = str_pad(intval($lastFourDigits) + 1, 4, '0', STR_PAD_LEFT);
            // dd($incrementedLastFourDigits);
            $FinalRecordEntryNo = str_pad(intval($Record_Entry_No) + 1, 4, '0', STR_PAD_LEFT);
            //dd($dateArray);
            //Bill Item Table Related data="
            $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $dtarr)->get();
            //dd($NormalDb);

            $StillDb = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $dtarr)->get();
            //dd($StillDb);
            // $countcombinarray=count($StillDb);
                //dd($countcombinarray);

            // Combine data from both tables
            $combinarray = $NormalDb->concat($StillDb);
            //dd($combinarray);

             // Count the combined data
            $countcombinarray=count($combinarray);
            //dd($countcombinarray);

             // Check the count of records with dye_check set to 1
            $Stldyechkcount1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $dtarr)->where('dye_check',"=",1)->get();
            
            
            $Stldyechkcount=count($Stldyechkcount1);
            //dd($Stldyechkcount);

              // Check the count of records with dye_check set to 1 in 'embs'
            $EmbdyeChkCount = DB::table('embs')
            ->where('t_bill_id', $tbillid)
            ->where('measurment_dt', $dtarr)
            ->where('dye_check', "=", 1)
            ->count();
            //dd($EmbdyeChkCount);

             // Calculate total count of records with dye_check set to 1 from both tables
            $Count_Chked_Emb_Stl= $EmbdyeChkCount + $Stldyechkcount;
            //dd($Count_Chked_Emb_Stl , $countcombinarray);
              // Insert data into 'recordms' based on the comparison of checked counts
            if ($Count_Chked_Emb_Stl === $countcombinarray) {
                //dd("IFFFFFFFFFFFFFFFFF;") ;
                DB::table('recordms')
                    ->where('Work_Id', '=', $WorkId)
                    // ->where('t_Bill_Id', '=', $tbillid)
                    ->insert([
                        'Work_Id' => $WorkId,
                        'Record_Entry_Id' => $newrecordentryid,
                        't_Bill_Id' => $tbillid,
                        'Record_Entry_No' => $FinalRecordEntryNo,
                        'Rec_date' => $dtarr,
                        'Dye_Check' => 1,
                        'Dye_Check_Dt' => $dtarr,
                        'JE_Check' => 0,
                        'JE_Check_Dt' => $dtarr,
                        'ee_check' => 0,
                        'ee_chk_dt' => null
                    ]);
            }

            else{
            //    dd("Elseeeeeeeeeeeee");
                DB::table('recordms')
                    ->where('Work_Id', '=', $WorkId)
                    // ->where( 't_Bill_Id' ,'=', $tbillid)
                    ->insert([
                        'Work_Id' => $WorkId,
                        'Record_Entry_Id' => $newrecordentryid,
                        't_Bill_Id' => $tbillid,
                        'Record_Entry_No' => $FinalRecordEntryNo,
                        'Rec_date' => $dtarr,
                        'Dye_Check'=>0,
                        'Dye_Check_Dt'=>$dtarr,
                        'JE_Check'=>0,
                        'JE_Check_Dt'=>$dtarr,
                        'ee_check'=>0,
                        'ee_chk_dt'=>null
                    ]);
            }
            // dd("Inserted successfilly");
        }

         // Fetch bill items related to the given bill ID
        $bitemsnm = DB::table('bil_item')
            ->where('t_bill_id', '=', $tbillid)
            ->get();

              // Fetch records from 'recordms' table for the given bill ID
        $exists = DB::table('recordms')
            ->where('t_Bill_Id', $tbillid)
            ->get();


          // Fetch distinct measurement dates from 'embs' table
            $embsd = DB::table('embs')
            ->select('measurment_dt')
            ->distinct()
            ->where('t_bill_id', '=', $tbillid)
            ->get();
        //    dd($embsd);

        // Fetch the maximum measurement date from 'embs' table for the given bill ID
        $embsmaxdt = DB::table('embs')
        ->where('t_bill_id', '=', $tbillid)
        ->max('measurment_dt');
        // dd($embsmaxdt);

        // Fetch distinct measurement dates from 'stlmeas' table
            $stlmeasd = DB::table('stlmeas')
            ->select('date_meas')
            ->distinct()
            ->where('t_bill_id', '=', $tbillid)
            ->get();
            //dd($stlmeasd);

      // Fetch records from 'recordms' table for the given WorkId
        $recinfo=  DB::table('recordms')
                ->where('Work_Id', '=', $WorkId)
                ->get();
                //dd($recinfo);

         // Fetch division name based on WorkId from 'workmasters', 'subdivms', and 'divisions' tables
        $divNm = DB::table('workmasters')
            ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
            ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
            ->where('workmasters.Work_Id', '=', $WorkId)
            ->value('divisions.div');

        // Fetch item details from 'bil_item' table
        $titemno = DB::table('bil_item')
            ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc', 'exec_qty', 'item_unit','cur_amt')
            ->where('t_bill_id', '=', $tbillid)
            ->get();
        //dd($titemno);

            // Fetch detailed item records from 'bil_item' table
        $titemnoRecords = DB::table('bil_item')
            ->select('t_item_no', 'item_desc', 'exec_qty', 'ratecode', 'bill_rt','cur_amt')
            ->where('t_bill_id', '=', $tbillid)
            ->get();

          // Fetch record entry numbers from 'recordms' table for the given bill ID
        $Recordeno = DB::table('recordms')
        ->select('Record_Entry_No')
        ->where('t_bill_id', '=', $tbillid)
        ->get();
        //dd($Recordeno);w

       // Fetch section engineers' names based on SO_Id from 'workmasters' and 'jemasters' tables
        $DBsectionEng=DB::table('workmasters')
        ->select('SO_Id')
        ->where('Work_Id',$WorkId)
        ->get();
       //  dd($DBsectionEng);
        $DBSectionEngNames = [];

        foreach ($DBsectionEng as $item)
        {
            $sectionEngName = DB::table('jemasters')
                ->select('name')
                ->where('jeid', $item->SO_Id)
                ->first();

            if ($sectionEngName) {
                $DBSectionEngNames[] = $sectionEngName->name;
            }
        }

        //    ->update(['mb_status' => 3]);// dd($DBSectionEngNames);
        // DB::table('bills')

           // Fetch section engineers' names from 'designations' table
           $sectionEngineer = DB::table('designations')->get();

        return view('DeputyCheck',compact('WorkId','embsmaxdt','BillDt','DBSectionEngNames','workDetails1','fund_Hd1','divNm','Recordeno','titemnoRecords','titemno','tbillid','recnovalues'));
        //return redirect()->route('billlist', ['WorkId' => $WorkId]);
    }

    // Deputy Check Recordentrywise function.............
    public function DeputyCheck1RecordFun(Request $request)
    {
        try
        {
            // Initialize measurement count and get inputs from the request
        $measCount=0;
        $BillDt = $request->input('BillDt');

        $tbillid = $request->input('tbillid_valuer');
        // dd($BillDt);
        $WorkIdvv =$request->input('WorkId_valuer');

        $Rec_E_No=$request->input('Record_Entry_Nor');

        $SelectDtAll= $request->input('SelectDtAll');
        //dd($SelectDtAll);

        $SelectDtAllS= $request->input('SelectDtAllS');
        // dd($SelectDtAllS,$SelectDtAll);
        // dd($WorkIdvv,$tbillid,$Rec_E_No);
        $html ='';

        // Get bill item data from the database
        $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();

        $recdate = DB::table('recordms')
        ->select('Rec_date')
        ->where('t_bill_id', $tbillid)
        ->where('Record_Entry_No', $Rec_E_No)
        ->value('Rec_date');

         // Retrieve the record date based on the bill ID and record entry number
        $RecDate = date("d/m/Y", strtotime($recdate));


        // Loop through each bill item data
        foreach($billitemdata as $itemdata)
            {
                       $bitemId=$itemdata->b_item_id;
            
             // Fetch measurement data for emb and stl based on item ID and record date
            $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $recdate)->get();
            $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $recdate)->get();
            //meas data check
            $EmbdyeChkCount = DB::table('embs')->where('t_bill_id', $tbillid)->where('measurment_dt', $recdate) ->where('dye_check',  1)->count();
            //  dd($EmbdyeChkCount);
            $EmbdyeChkTotal = DB::table('embs')->where('t_bill_id', $tbillid)->where('dye_check',  1)->count();
           $EmbTotalRecord = DB::table('embs')
                ->where('t_bill_id', $tbillid)
                ->where(function($query) {
                    $query->where('parti', 'not like', 'TMT%')
                          ->orWhereNull('parti');
                })
                ->count();

             // dd($EmbdyeChkTotal,$EmbTotalRecord);


            $Stldyechkcount = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdate)->where('dye_check',"=",1)->count();
            // dd($Stldyechkcount);
            $Count_Chked_Emb_Stl= $EmbdyeChkCount+$Stldyechkcount;

              // Initialize measurement data count
            $measdatacount=$embCountWithoutTMT=$stlmeascount=0;

            $stlmeascount=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdate)->count();
            $embcount = DB::table('embs')->where('t_bill_id', $tbillid)->where('measurment_dt', $recdate)->get();

            $embCountWithoutTMT = 0;

             // Calculate the count of emb measurements excluding TMT
            if ($embcount->isNotEmpty()) {
                foreach ($embcount as $tmtdata) {
                    if (strpos($tmtdata->parti, 'TMT') !== 0) {
                        $embCountWithoutTMT++;
                    }
                }
            }

        // Total measurement data count
            // dd($stlmeascount);
            $measdatacount=$embCountWithoutTMT+$stlmeascount;
            //dd($measdatacount);

              // Generate HTML output based on measurement data
            if (!$measnormaldata->isEmpty() || !$meassteeldata->isEmpty()) {

                 // Table header for item details
                $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><tr><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 90%; text-align: justify;"> ' . $itemdata->exs_nm . '</th></tr></thead></table>';

                 // Get item ID for bill item
                $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
              
                   // Check if item ID ends with specific codes and handle accordingly
                if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
                {
                     // Fetch STL measurement data and RCC member data
                    $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->get();

                    $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                     // Define diameter columns
                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
                    'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


                     // Swap bar length with diameter columns if condition is met
                    foreach ($stldata as &$data) {
                        if (is_object($data)) {
                            foreach ($ldiamColumns as $ldiamColumn) {
                                if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                                $temp = $data->$ldiamColumn;
                                $data->$ldiamColumn = $data->bar_length;
                                $data->bar_length = $temp;
                                break; // Stop checking other ldiam columns if we found a match
                                }
                            }
                        }
                    }

                     // Initialize sums for diameter columns
                    $sums = array_fill_keys($ldiamColumns, 0);

                     // Calculate sums for diameter columns
                    foreach ($stldata as $row) {
                        foreach ($ldiamColumns as $ldiamColumn) {
                            $sums[$ldiamColumn] += $row->$ldiamColumn;
                        }
                    }//dd($stldata);

                       // Get RCC member data for the current bill item
                    $bill_member = DB::table('bill_rcc_mbr')
                        ->whereExists(function ($query) use ($bitemId) {
                        $query->select(DB::raw(1))
                            ->from('stlmeas')
                            ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                            ->where('bill_rcc_mbr.b_item_id', $bitemId);
                        })
                        ->get();


                    $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

                      // Generate HTML output for RCC members and their measurements
                    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 3%;  min-width: 3%;">Sr No</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 20%; min-width: 20%;">Bar Particulars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 6%; min-width: 6%;">No of Bars</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">Length of Bars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">6mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">8mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">10mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">12mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">16mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">20mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">25mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">28mm</th>
                      <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 3%; min-width: 3%; ">Check</th>   

                    </thead>';
                    foreach ($bill_member as $index => $member) {

                           // Get the ID of the current member
                        $rcmbrid=$member->rc_mbr_id;

                          // Fetch measurement data for the current member and date
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $recdate)->get();
                                //dd($memberdata);

                                 // Check if there is any measurement data for the member
                        if ( !$memberdata->isEmpty()) {

                               // Generate HTML table header with member details
                            $html .= '<table style="border-collapse: collapse; width: 100%;"><thead><th colspan="1" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Sr No :' . $member->member_sr_no . '</th><th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">RCC Member :' . $member->rcc_member . '</th><th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Member Particular :' . $member->member_particulars . '</th><th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">No Of Members :' . $member->no_of_members . '</th></thead></table>';

                            // Loop through measurement data for the current member
                            foreach ($stldata as $bar) {
                                if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                                    $measCount++;
                                $formattedDateMeas = date('d/m/Y', strtotime($bar->date_meas));

                               // Append measurement data to the HTML table
                                $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><td style="border: 1px solid black; padding: 8px; width: 3%;  min-width: 3%; text-align:right;">'. $bar->bar_sr_no .'</td>
                                <td style="border: 1px solid black; padding: 8px; width: 20%; min-width: 20%; text-align-left;">'. $bar->bar_particulars.'</td><td style="border: 1px solid black; padding: 8px; width: 6%; min-width: 6%; text-align:right;">'. $bar->no_of_bars .'</td>
                                <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text-align:right;">'. $bar->bar_length .'</td> <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam6 .'</td>
                                <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam8 .'</td> <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam10 .'</td>
                                <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam12 .'</td><td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam16 .'</td>
                                <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam20 .'</td> <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%;  text-align:right;">'. $bar->ldiam25 .'</td>
                                <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%; text-align:right;">'. $bar->ldiam28 .'</td>';

                                // Add a checkbox for dye check
                                    if( $bar->dye_check==1){
                                        $html .= '<td style="border: 1px solid black; padding: 30px; width: 3%; min-width: 3%;">
                                        <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Steel[' . $bar->steelid . ']."  onclick="CustomeCheckBoxSFun('.$measdatacount.');" checked>
                                        </td>';
                                    }
                                    else{
                                        $html .= '<td style="border: 1px solid black; padding: 30px; width: 3%; min-width: 3%;">
                                        <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Steel[' . $bar->steelid . ']."  onclick="CustomeCheckBoxSFun('.$measdatacount.');" >
                                        </td>';
                                    }

                                }
                            }
                        }
                    }
                    // Fetch TMT data for the given bill ID and measurement date
                    $TMTdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $recdate)->get();

                    // Check if there is any TMT data available
                    if ($TMTdata->isNotEmpty()) {
                        foreach ($TMTdata as $tmtdata) {
                                // Append TMT data to the HTML table
                            if (strpos($tmtdata->parti, 'TMT') === 0) {
                                // dd($tmtdata);
                                $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; "><tbody><td style="border: 1px solid black; padding: 8px; width: 5%; text-align:left;">' . $tmtdata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 40px; text:align:left;">' . $tmtdata->parti . '</td>';

                                $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right;">' . $tmtdata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right;">' . $tmtdata->qty . '</td>';

                            }
                        }
                    }
                }
                else{
                     // Fetch normal data if there is no TMT data
                    $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();
                    // $normalDataCount=count($normaldata);
                    // dd($normalDataCount);

                      // Check if there is no steel measurement data
                    if($meassteeldata->isEmpty()){
                        $html .= '<table class="table-bordered table-striped" style="border-right: 1px solid black; width:100%;"><thead><th style="width: 5%; border-color: black;">Sr. No</th>
                        <th style="width: 30%; border-color: black;">Particulars</th><th style="width: 7%; border-color: black;">Number</th><th style="width: 7%; border-color: black;">Length</th>
                        <th style="width: 7%; border-color: black;">Breadth</th><th style="width: 7%; border-color: black;">Height</th><th style="width: 7%; border-color: black;">Quantity</th><th style="width: 7%; border-color: black;">Check</th>
                        </thead>';
                    }

                     // Loop through normal data and append to the HTML table
                    foreach($normaldata as $nordata)
                    {
                        // if($nordata->b_item_id !== $itemdata->b_item_id){
                            $formula= $nordata->formula;
                    $html .= '<tbody><td style="border: 1px solid black; padding: 8px; width: 5%; text-align:left;">' . $nordata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 40px; text:align:left;">' . $nordata->parti . '</td>';
                        if($formula)
                        {
                            $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right">' . $nordata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right">' . $nordata->qty . '</td>';
                        }
                        else
                        {
                            $html .= '<td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right">' . $nordata->number . '</td><td style="border: 1px solid black; padding: 8px; width: 7%;text-align:right">' . $nordata->length . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right">' . $nordata->breadth . '</td><td style="border: 1px solid black; padding: 8px; width: 7%;text-align:right">' . $nordata->height . '</td><td style="border: 1px solid black; padding: 8px; width: 7%; text-align:right">' . $nordata->qty . '</td>';
                        }
                        if($nordata->dye_check==1)
                        {
                            $html .= '<td style="border: 1px solid black; padding: 35px; width: 4%;"><input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Item[' .$nordata->meas_id. ']" onclick="CustomeCheckBoxSFun(' . $measdatacount . ');" checked>
                                    </td>';
                        }
                        else{
                            $html .= '<td style="border: 1px solid black; padding: 35px; width: 4%;"><input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Item[' .$nordata->meas_id. ']" onclick="CustomeCheckBoxSFun(' . $measdatacount . ');"></td>';
                        }


                        // }
                }
                $html .='</tbody></table>';
            }
            }


            }

        // Return a JSON response with the generated data and HTML
            return response()->json(['measdatacount'=>$measdatacount,
            'measCount'=>$measCount,
            'html'=>$html,
            'RecDate'=>$RecDate,
            'Count_Chked_Emb_Stl'=>$Count_Chked_Emb_Stl,
            'EmbTotalRecord'=>$EmbTotalRecord,
            'EmbdyeChkTotal' => $EmbdyeChkTotal,
            ]);

    }

    catch (Exception $e) 
{
      // Log the error and return a JSON response with the error message
    Log::error('Error in SubmitAllEE: ' . $e->getMessage());
    // Return a JSON response with an error message
    return response()->json(['error' => 'An error occurred when Deputy Engineer Record Entry Wise data are changed: ' . $e->getMessage()], 500);
}

}


    // Deputy check1 Itemwise controller Function.........................................
    public function DeputyCheck1ItemFun(Request $request)
    {
        try
        {
            
         // Retrieve inputs from the request
        $tbillid = $request->input('tbillid_value');
        // dd($tbillid);
        $recno=$request->input('recordEntryNo');
        //dd($recno);
        $itemid =$request->input('itemid_value');
        //dd($itemid);
        $WorkIdv =$request->input('WorkId_value');

        $itemno=$request->input('combined_value');
         // Initialize variables
        $html='';
        $Item1Data=0;
        $stldata=0;
        // $deputychek=$this->CommonDeputyCheck1ItemFun($tbillid , $recno, $itemid , $WorkIdv ,$itemno);

        // Query to get item data based on bill ID and item number
        $titemno = DB::table('bil_item')
        ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc','exec_qty','item_unit','cur_amt','bill_rt','ratecode','cur_qty','cur_amt')
        ->where('t_bill_id', '=', $tbillid)
        ->where('t_item_no','=',$itemno)
        ->get();
        //dd($titemno);


        // Extract numeric part and sub-number from combined item number
        $itemno=$request->input('combined_value');
        // Separate the numeric part and the last character
        $itemNo = preg_replace('/[^0-9]/', '', $itemno); // Extract all digits
        $lastCharacter = substr($itemno, -1);
        $subno=0;
        if (ctype_alpha($lastCharacter)) {
            // If last character is a letter, set it as sub-number
            $subno=$lastCharacter;
            // dd($subno);
        }

          // Retrieve b_item_id based on bill ID, item number, and optional sub-number
        $bitemid=0;
        $html='';
        if($subno)
        {
            $bitemid=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('t_item_no' , $itemNo)
            ->where('sub_no', $subno)->value('b_item_id');
            //dd($bitemid);
        }
        else
        {
            $bitemid=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('t_item_no' , $itemNo)
            ->value('b_item_id');
        }


         // Retrieve item_id based on b_item_id
        $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('item_id');

        // Check if item_id ends with specific values to determine if stldata should be retrieved
        if (
        in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016",
                                    "002017", "002023", "002024", "003351", "003352", "003878"])
                                    )
        {

               // Retrieve stldata based on bill ID and b_item_id
        $stldata = DB::table('stlmeas')
        ->select('stlmeas.*')
        ->join('bill_rcc_mbr', 'stlmeas.rc_mbr_id', '=', 'bill_rcc_mbr.rc_mbr_id')
        ->where('bill_rcc_mbr.t_bill_id', $tbillid)
        ->where('bill_rcc_mbr.b_item_id', $bitemid)
        ->get();

        // dd($stldata,$tbillid,$bitemid);
        $bill_rc_data = DB::table('bill_rcc_mbr')->get();

        // dd($stldata , $bill_rc_data);

         // Initialize diameter columns
        $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
        'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


           // Swap bar_length with diameter columns if necessary
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

             // Calculate sums for diameter columns
        $sums = array_fill_keys($ldiamColumns, 0);

        foreach ($stldata as $row) {
            foreach ($ldiamColumns as $ldiamColumn) {
            $sums[$ldiamColumn] += $row->$ldiamColumn;
            }
        }//dd($stldata);

         // Retrieve bill member data based on b_item_id
        $bill_member = DB::table('bill_rcc_mbr')
        ->whereExists(function ($query) use ($bitemid) {
        $query->select(DB::raw(1))
        ->from('stlmeas')
        ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
        ->where('bill_rcc_mbr.b_item_id', $bitemid);
        })
        ->get();

          // Retrieve RC member IDs for further processing
        $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemid)->pluck('rc_mbr_id')->toArray();

         // Generate HTML table for each bill member
        foreach ($bill_member as $index => $member) {
        //dd($member);
        $memberdata = DB::table('stlmeas')
        ->join('bill_rcc_mbr', 'bill_rcc_mbr.rc_mbr_id', '=', 'stlmeas.rc_mbr_id')
        ->where('bill_rcc_mbr.t_bill_id', $tbillid)
        // ->where('t_item_no', '=', $itemno)
        ->get();
        //dd($memberdata);
        if ( !$memberdata->isEmpty()) {
        $html .= '<table style="border-collapse: collapse; width: 100%;"><thead><tr>';
        $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Sr No:' . $member->member_sr_no . '</th>';
        $html .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: lightblue;">RCC Member :' . $member->rcc_member . '</th>';
        $html .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Member Particular :' . $member->member_particulars . '</th>';
        $html .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: lightblue;">No Of Members :' . $member->no_of_members . '</th></tr>';


        $html .= '<tr><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
            <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 25%; min-width: 25%;">Bar Particulars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
            <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 7%;">6mm</th>
            <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">8mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 7%;">10mm</th>
            <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">12mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 7%;">16mm</th>
            <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">20mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">25mm</th>
            <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">28mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Record Entry No</th>
            <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 3%; min-width: 3%;">Check</th></thead></tr>';
             // Populate table rows with data
        foreach ($stldata as $bar) {
            if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                //   dd($bar);
                $formattedDateMeas = date('d/m/Y', strtotime($bar->date_meas));
                $dye_chk_date = date('d/m/Y', strtotime($bar->dyE_chk_dt));

                $Record_Entry_No = DB::table('recordms')
                ->where('t_bill_id', $tbillid)
                ->where('Rec_date', $bar->date_meas)
                ->value('Record_Entry_No');

                $html .= '<tr>';
                $html .= '<tbody>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:left; width: 5%;  min-width: 3%;">'. $bar->bar_sr_no .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:left; width: 25%; min-width: 25%;">'. $bar->bar_particulars.'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 10%; min-width: 10%;">'. $bar->no_of_bars .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 10%; min-width: 10%;">'. $bar->bar_length .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 5%; min-width: 5%;">'. $bar->ldiam6 .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 5%; min-width: 5%;">'. $bar->ldiam8 .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 5%; min-width: 5%;">'. $bar->ldiam10 .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 5%; min-width: 5%;">'. $bar->ldiam12 .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 5%; min-width: 5%;">'. $bar->ldiam16 .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 5%; min-width: 5%;">'. $bar->ldiam20 .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 5%; min-width: 5%;">'. $bar->ldiam25 .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 5%; min-width: 5%;">'. $bar->ldiam28 .'</td>';
                $html .='<td style="border: 1px solid black; padding: 8px; text-align:right; width: 10%; min-width: 10%;">'. $Record_Entry_No .'</td>';

                if($bar->dye_check==1){
                    $html .= '<td style="width: 3%; padding-left: 50px; border: 1px solid black;"><input id="checkbox" class="form-check-input form-check" type="checkbox" checked disabled ></td>';
                }
                else{
                    $html .= '<td style="width: 3%; padding-left: 50px; border: 1px solid black;"><input id="checkbox" class="form-check-input form-check" type="checkbox"  disabled ></td>';
                }
                // $html .='<td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%;">'.$dye_chk_date.'</td>';
                $html .= '</tr>';
            }
        }

    }
    }
    $html .= '
            <tr style="background-color:yellow;">
                <th colspan="3" style="background-color:white; padding: 8px;  width: 10%; min-width: 10%;"></th>
                <th colspan="1" style="border: 1px solid black; padding: 8px;  width: 10%; min-width: 10%;">Total Length:</th>
                <th colspan="1" style="border: 1px solid black; padding: 8px;  width: 5%; min-width: 5%; text-align:right;">' . $sums['ldiam6'] . '</th>
                <th colspan="1" style="border: 1px solid black; padding: 8px;  width: 5%; min-width: 5%; text-align:right;">' . $sums['ldiam8'] . '</th>
                <th colspan="1" style="border: 1px solid black; padding: 8px;  width: 5%; min-width: 5%; text-align:right;">' . $sums['ldiam10'] . '</th>
                <th colspan="1" style="border: 1px solid black; padding: 8px;  width: 5%; min-width: 5%; text-align:right;">' . $sums['ldiam12'] . '</th>
                <th colspan="1" style="border: 1px solid black; padding: 8px;  width: 5%; min-width: 5%; text-align:right;">' . $sums['ldiam16'] . '</th>
                <th colspan="1" style="border: 1px solid black; padding: 8px;  width: 5%; min-width: 5%; text-align:right;">' . $sums['ldiam20'] . '</th>
                <th colspan="1" style="border: 1px solid black; padding: 8px;  width: 5%; min-width: 5%; text-align:right;">' . $sums['ldiam25'] . '</th>
                <th colspan="1" style="border: 1px solid black; padding: 8px;  width: 5%; min-width: 5%; text-align:right;">' . $sums['ldiam28'] . '</th>
                <th colspan="2" style="background-color:white; width: 10%; min-width: 10%;"></th>
            </tr>';

        $html .='</tbody>';
        $html .='</table>';
        //dd($embssumarry);
        $embssumarry=DB::table('embs')->where('b_item_id' , $bitemid)->where('t_bill_id' , $tbillid)->get();

        //summary data of measurement
        foreach($embssumarry as $nordata)
            {
                // dd($nordata);
                $formula= $nordata->formula;
                $html .= '<table style="border-collapse: collapse; width: 100%;"><tbody><td style="border: 1px solid black; padding: 8px; width: 5%;">' . $nordata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 39%; word-wrap: break-word; max-width: 200px; text:align:left;">' . $nordata->parti . '</td>';
                if($formula)
                {
                    $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 15%; text:align:right;">' . $nordata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 6%; text:align:right;">' . $nordata->qty . '</td>';
                }
                else
                {
                    $html .= '<td style="border: 1px solid black; padding: 8px; width: 12%; text:align:right;">' . $nordata->number . '</td><td style="border: 1px solid black; padding: 8px; width: 11%; text:align:right;">' . $nordata->length . '</td><td style="border: 1px solid black; padding: 8px; text:align:right; width: 11%;">' . $nordata->breadth . '</td><td style="border: 1px solid black; padding: 8px; width: 11%; text:align:right;">' . $nordata->height . '</td><td style="border: 1px solid black; padding: 8px; width: 15%; text:align:right;">' . $nordata->qty . '</td>';
                }
                $html .='</tbody></table>';
            }
        }
         else
        {
            //bitem id related data
            $Item1Data = DB::table('embs')
            ->join('recordms', 'embs.t_bill_id', '=', 'recordms.t_bill_id')
            ->where('embs.b_item_id', $bitemid)
            ->where('embs.t_bill_id', $tbillid)
            ->whereColumn('recordms.Rec_date', '=', 'embs.measurment_dt')
            ->select('embs.*', 'recordms.Record_Entry_No')
            ->get();
            //  dd($Item1Data);
        }
        //dd($subno);
        //dd($itemno);
        $bitemid = DB::table('bil_item')
        ->where('t_bill_id', '=', $tbillid)
        ->get('b_item_id');
        // dd($bitemid);

        //item no data
        $titemno = DB::table('bil_item')
        ->where('t_item_no','=',$itemno)
        ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc','exec_qty','item_unit','bill_rt','ratecode','cur_qty','cur_amt')
        ->where('t_bill_id', '=', $tbillid)
        ->get();
     // dd($titemno);

     //tender data
        $TndData = DB::table('tnditems')
        ->select('tnd_qty','exs_nm','item_unit')
        ->where('t_item_no', '=', $itemno)
        ->first();
        $iteminfo = DB::table('bil_item')
        ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'exs_nm','exec_qty','item_unit','cur_amt','bill_rt','ratecode','cur_qty')
        ->where('t_bill_id', '=', $tbillid)
        ->where('t_item_no','=',$itemno)
        ->get();

       //return response json data
        return response()->json(['Item1Data'=>$Item1Data,'html'=>$html,'stldata'=>$stldata,'TndData'=>$TndData,'titemno'=>$titemno]);
    }
        catch (Exception $e) 
    {
        Log::error('Error in SubmitAllEE: ' . $e->getMessage());
        // Return a JSON response with an error message
        return response()->json(['error' => 'An error occurred when Deputy Engineer Record entry and Item Wise Data Load: ' . $e->getMessage()], 500);
    }
    
    }

      //Save Deputy1 function...
    public function SaveDeputy1(Request $request){
    // dd($request);

    // Retrieve input values from the request
    $BillDt = $request->input('BillDt');

    $WorkId = $request->input('WorkId');
    // dd($WorkId);

    $tbillid=$request->input('tbillid');
    // dd($tbillid);

    $titemnovalues=$request->input('titemnovalues');
  //dd($titemnovalues);

    $dateInput=$request->input('dateInput');
    // dd($dateInput);

    $recnovalues=$request->input('recnovalues');
    //dd($recnovalues);

    $je_check=$request->input('je_check');
    // dd($je_check);

    $steelid=$request->input('steelid');
    //dd($steelid);

    $customDateInputS=$request->input('customDateInputS');
    //dd($customDateInputS);

    $je_check_Steelkey1=$request->input('je_check_Steel');

    // Access the session variable set in the previous function
    $storedBillDate = $request->session()->get('billDate');
    //dd($storedBillDate);
    // $commonheader=$this->commongotoembcontroller($WorkId , $steelid,$storedBillDate,$tbillid,$recnovalues);
    //dd($commonheader);

     // Fetch work details
        $workDetails1 = DB::table('workmasters')
        ->select('Work_Nm', 'Sub_Div', 'Agency_Nm', 'Work_Id', 'Wo_Dt','Period','WO_No','Stip_Comp_Dt', 'Tender_Id')
        ->where('Work_Id', '=', $WorkId)
        ->first();

         // Fetch record entry numbers for the given bill ID
        $Recordeno = DB::table('recordms')
        ->select('Record_Entry_No')
        ->where('t_bill_id', '=', $tbillid)
        ->get();

        // Fetch division name by joining tables
        $divNm = DB::table('workmasters')
            ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
            ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
            ->where('workmasters.Work_Id', '=', $WorkId)
            ->value('divisions.div');

             // Fetch fund header details by joining tables
        $fund_Hd1 = DB::table('workmasters')
        ->select('fundhdms.Fund_HD_M')
        ->join('fundhdms', function ($join) use ($WorkId) {
            $join->on(DB::raw("LEFT(workmasters.F_H_Code, 4)"), '=', DB::raw("LEFT(fundhdms.F_H_CODE, 4)"))
                ->where('workmasters.Work_Id', '=', $WorkId);
        })
        ->first();

         // Fetch item details from the bill items table
        $titemno = DB::table('bil_item')
        ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc','exec_qty','item_unit','bill_rt','ratecode')
        ->where('t_bill_id', '=', $tbillid)
        ->get();
        //dd($titemno);

         // Get values for various checkboxes and buttons
        $je_check_Steel_Headingkey=$request->input('je_check_Steel_Heading');
        $countcombinarray=$request->input('countcombinarray');
        //dd($countcombinarray);
        $btnsave=$request->input('btnsave');
        $btnall=$request->input('btnall');
        $BtnRevert=$request->input('BtnRevert');

        // dd($BtnRevert);
    // If 'save' button is clicked
   if($btnsave==='save'){
       $recenid = DB::table('recordms')
       ->where('t_bill_id', $tbillid)
       ->where('Record_Entry_No', $recnovalues)
       ->value('Record_Entry_Id');
       // dd($recenid);

       $recdt= DB::table('recordms')
       ->where('t_bill_id', $tbillid)
       ->where('Record_Entry_No', $recnovalues)
       ->value('Rec_date');
       //dd($recdt);

       // Fetch measurement IDs and steel IDs based on record date
       $Emb_Measid = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $recdt)->pluck('meas_id')->toArray();
       //dd($Emb_Measid);
       $Stlmeas_Stlid = DB::table('stlmeas')
       ->where('t_bill_id', $tbillid)
       ->where('date_meas', $recdt)
       ->pluck('steelid')
       ->toArray();
       //dd($Stlmeas_Stlid);

       // Update dye_check status for steel measurements
       if($je_check_Steelkey1 === null  ){
       // dd($je_check_Steelkey1);
           foreach($Stlmeas_Stlid as $jecheck){
           //dd($jecheck);
               DB::table('stlmeas')
               ->where('steelid', $jecheck)
               ->update(['dye_check' => 0]);
           }
        }
       else{
           $je_check_Steelkey=array_keys($je_check_Steelkey1);
           $unchked_stl = array_diff($Stlmeas_Stlid , $je_check_Steelkey);

           // dd($Stlmeas_Stlid,$je_check_Steelkey);
           $unchked_stl = array_diff($Stlmeas_Stlid , $je_check_Steelkey);
           //dd($unchked_stl);
               foreach($unchked_stl as $jecheck){
                   // dd($jecheck);
                   DB::table('stlmeas')
                   ->where('steelid', $jecheck)

                   ->update(['dye_check' => 0]);
               }
        }
         // Update dye_check status for emb measurements
        $je_check_Itemkey1=$request->input('je_check_Item');
        if($je_check_Itemkey1 === null  ){
            // dd($je_check_Steelkey1);
            foreach($Emb_Measid as $jecheck){
                //dd($jecheck);
                DB::table('embs')
                ->where('meas_id', $jecheck)
                ->update(['dye_check' => 0]);
            // dd("Updated normal to 0");
            }//dd("Updated Steel to 0");
        }
       else{
           // If some emb measurements are checked, update only unchecked ones
       $je_check_Itemkey=array_keys($je_check_Itemkey1);
       //dd($je_check_Itemkey);
       $unchked_embs = array_diff($Emb_Measid , $je_check_Itemkey);
       // dd($unchked_embs);
           foreach($unchked_embs as $jecheck){
               //dd($jecheck);
               DB::table('embs')
               ->where('meas_id', $jecheck)
               ->update(['dye_check' => 0]);
           // dd("Updated normal to 0");
           }//dd("Updated Steel to 0");
       }

          // Retrieve data for combined measurement checks
       $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $recdt)->get();
       //dd($NormalDb);

       $StillDb = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdt)->get();
       //dd($StillDb);
       // $countcombinarray=count($StillDb);
        //dd($countcombinarray);
          // Combine and count the measurement data
       $combinarray = $NormalDb->concat($StillDb);
       //dd($combinarray);

       //Count of combine data...
       $countcombinarray=count($combinarray);
       //dd($countcombinarray);
       $Stldyechkcount1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdt)->where('dye_check',"=",1)->get();
       $Stldyechkcount=count($Stldyechkcount1);
       //dd($Stldyechkcount);

       $EmbdyeChkCount = DB::table('embs')
       ->where('t_bill_id', $tbillid)
       ->where('measurment_dt', $recdt)
       ->where('dye_check', "=", 1)
       ->count();
       //dd($EmbdyeChkCount);

       $Count_Chked_Emb_Stl= $EmbdyeChkCount + $Stldyechkcount;

        // Update Dye_Check status in recordms based on checked items
       if ($Count_Chked_Emb_Stl === $countcombinarray) {
       // dd($jecheck);
           DB::table('recordms')
           ->where('Record_Entry_Id', $recenid)
           ->update(['Dye_Check' => 1]);
           // dd("Updated normal to 0");
       }
       else{
       // dd($jecheck);
           DB::table('recordms')
           ->where('Record_Entry_Id', $recenid)
           ->update(['Dye_Check' => 0]);
       // dd("Updated normal to 0");
       }

       //Saving Checked CheckBoxes to table....
       if($recenid){
           //Updating Steel checkbox...
           // dd($je_check_Steelkey);
           $je_check_Steelkey=$request->input('je_check_Steel');
           if($je_check_Steelkey){
           $countje=count($je_check_Steelkey);
           //dd($countje);
               for ($i=0; $i<$countje; $i++) {
               $jecheckv = array_keys($je_check_Steelkey);
               $updateSQL = DB::table('stlmeas')
                   ->where('steelid', $jecheckv[$i])
                   ->update(['dye_check' => 1]);
               }
           }
           $je_check_Itemkey=$request->input('je_check_Item');
           //Udating EMB Data Checkbox...
           if($je_check_Itemkey){

               //dd($je_check_Item);
               $je_check_Item=array_keys($je_check_Itemkey);
               foreach($je_check_Item as $jecheck){
                   DB::table('embs')
                   ->where('meas_id', $jecheck)
                   ->update(['dyE_check' => 1]);
               }
               // dd("Normal Data Checkbox is Update");
           }
            // dd("CheckBox Saved successfully....");
       }

       //Saving Date to database =========================================================================================
       if($customDateInputS){
           // $customDateStlIdS=array_Keys($customDateInputS);
        //dd($customDateInputS);

           // $customDatevalues=array_values($customDateInputS);
           // //dd($customDatevalues);
           foreach ($customDateInputS as $key => $value) {
               //dd($key, $value);
               if($value){
                   DB::table('stlmeas')
                   ->where('steelid', $key)
                   ->update(['dyE_chk_dt' => $value]);
               }
           }
       }
       // Handling custom date inputs for 'embs' table
       $customDateInputN=$request->input('customDateInputN');
        //dd($customDateInputN);
           //dd($customDateInputN);
        if($customDateInputN){
             // Iterate through the custom date inputs
        foreach ($customDateInputN as $key => $value) {
            //dd($key, $value);
            if($value){
                  // Update 'dyE_chk_dt' field in 'embs' table where 'meas_id' matches
                DB::table('embs')
                ->where('meas_id', $key)
                ->update(['dyE_chk_dt' => $value]);
            }

           }
       }

       // Fetch record entry number for the given 'recenid'
       $recno = DB::table('recordms')
       ->where('Record_Entry_Id', $recenid)
       ->get('Record_Entry_No');
            // dd($recno);

           //Return view Page......
        //    $flag=1;
        //    // Modify the data and add the new variable
        //    $commonheader['flag'] = $flag;

          // Fetch record entry numbers for the given bill ID
           $Recordeno = DB::table('recordms')
           ->select('Record_Entry_No')
           ->where('t_bill_id', '=', $tbillid)
           ->get();

           // Fetch section engineers related to the given 'WorkId'
    $DBsectionEng=DB::table('workmasters')
        ->select('SO_Id')
        ->where('Work_Id',$WorkId)
        ->get();
        // dd($DBsectionEng);
        $DBSectionEngNames = [];

        // Retrieve names of section engineers
        foreach ($DBsectionEng as $item)
        {
            $sectionEngName = DB::table('jemasters')
                ->select('name')
                ->where('jeid', $item->SO_Id)
                ->first();

            if ($sectionEngName) {
                $DBSectionEngNames[] = $sectionEngName->name;
            }
        }
        // dd($DBSectionEngNames);

        // Fetch the maximum measurement date for the given bill ID
        $embsmaxdt = DB::table('embs')
        ->where('t_bill_id', '=', $tbillid)
        ->max('measurment_dt');
        
        // Return the view with the relevant data
        return view('DeputyCheck',compact('DBSectionEngNames','embsmaxdt','Recordeno','recnovalues','BillDt','WorkId','workDetails1','tbillid','titemno','fund_Hd1','divNm'));
    }

    // Handle the 'btnall' button click event
    if($btnall){
        // Fetch record entry ID and date
       $recenid = DB::table('recordms')
       ->where('t_bill_id', $tbillid)
       ->where('Record_Entry_No', $recnovalues)
       ->value('Record_Entry_Id');
       // dd($recenid);

       $recdt= DB::table('recordms')
       ->where('t_bill_id', $tbillid)
       ->where('Record_Entry_No', $recnovalues)
       ->value('Rec_date');
       //dd($recdt);

       // Fetch measurement IDs for 'embs' and 'stlmeas'
       $Emb_Measid = DB::table('embs')->where('t_bill_id' , $tbillid)->pluck('meas_id')->toArray();
       //dd($Emb_Measid);
       $Stlmeas_Stlid = DB::table('stlmeas')
       ->where('t_bill_id', $tbillid)
       ->pluck('steelid')
       ->toArray();
       //dd($Stlmeas_Stlid);

         // Fetch 'embs' and 'stlmeas' data
        $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->get();
        //dd($NormalDb);

         $embDatas=DB::table('embs')
        ->where('t_bill_id',$tbillid)
        ->select('meas_id','qty', 'measurment_dt')
        ->get();
        //dd($embData);

        // Update 'ee_chk_dt' and 'ee_chk_qty' fields in 'embs' table
        foreach($embDatas as $embdata){
            DB::table('embs')
        ->where('t_bill_id',$tbillid)
        ->where('meas_id',$embdata->meas_id)
        ->update(['ee_chk_dt'=>$embdata->measurment_dt , 'ee_chk_qty'=>$embdata->qty ]);
        }


        $StillDb1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->get();
        $StillDbcount=count($StillDb1);
       //dd($StillDb);
        // $countcombinarray=count($StillDb);
            //dd($countcombinarray);
        //$combinarray = $NormalDb+$StillDb;
        // $combinarray1 = $NormalDb->concat($StillDb);

       // Count non-combined 'embs' data
        $countnormalarray=0;//
       foreach ($NormalDb as $NormalDb1) {
        $bitemid = $NormalDb1->b_item_id;
        $itemid = DB::table('bil_item')->where('b_item_id', $bitemid)->value('item_id');
           // Check if item ID matches specific values
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])) {
                // Do something if the condition is met
                //dd($NormalDb);
            } else {
                      // Count items that do not match the condition
                $countnormalarray++;
                // Perform count for other cases
            }
        }

         // Update the 'Dye_Check' status in the 'recordms' table
        $countcombinarray=$StillDbcount+$countnormalarray;
           // dd($jecheck);
           DB::table('recordms')
           ->where('Record_Entry_Id', $recenid)
           ->update(['Dye_Check' => 1]);
           // dd("Updated normal to 0");

       //Saving Checked CheckBoxes to table....
       if($recenid){
           //Updating Steel checkbox...
           // dd($je_check_Steelkey);
           $je_check_Steelkey=$request->input('je_check_Steel');
           if($je_check_Steelkey){
           $countje=count($je_check_Steelkey);
           //dd($countje);
               for ($i=0; $i<$countje; $i++) {
               $jecheckv = array_keys($je_check_Steelkey);
               $updateSQL = DB::table('stlmeas')
                   ->where('steelid', $jecheckv[$i])
                   ->update(['dye_check' => 1]);
               }
           }
           //Udating EMB Data Checkbox...
           $je_check_Itemkey=$request->input('je_check_Item');
           if($je_check_Itemkey){
               //dd($je_check_Item);
               $je_check_Item=array_keys($je_check_Itemkey);
               foreach($je_check_Item as $jecheck){
                   DB::table('embs')
                   ->where('meas_id', $jecheck)
                   ->update(['dyE_check' => 1]);
               }
               // dd("Normal Data Checkbox is Update");
           }
       // dd("CheckBox Saved successfully....");
       }

       //Saving Date to database =========================================================================================
       if($customDateInputS){
           foreach ($customDateInputS as $key => $value) {
               //dd($key, $value);
               if($value){
                   DB::table('stlmeas')
                   ->where('steelid', $key)
                   ->update(['dyE_chk_dt' => $value]);
               }
           }
       }
        // Save custom dates for 'embs' table
       $customDateInputN=$request->input('customDateInputN');
       //dd($customDateInputN);
           //dd($customDateInputN);
           if($customDateInputN){
           foreach ($customDateInputN as $key => $value) {

               //dd($key, $value);
               if($value){
                   DB::table('embs')
                   ->where('meas_id', $key)
                   ->update(['dyE_chk_dt' => $value]);
               }

           }
       }

           //Count of combine data...

           // Fetch records from 'stlmeas' where 'dye_check' is 1 for the given bill ID and count them
           $Stldyechkcount1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('dye_check',"=",1)->get();
           $Stldyechkcount=count($Stldyechkcount1);
           //dd($Stldyechkcount);

           // Fetch and count the 'dye_check' records from 'embs' table for the given bill ID
           $EmbdyeChkCount = DB::table('embs')
           ->where('t_bill_id', $tbillid)
           ->where('dye_check', "=", 1)
           ->count();
           //dd($EmbdyeChkCount);

           // Total count of checked items (both steel and emb)
           $Count_Chked_Emb_Stl= $EmbdyeChkCount + $Stldyechkcount;
            // dd($Count_Chked_Emb_Stl , $countcombinarray);

            // Check if the total count of checked items matches the combined count
           if ($Count_Chked_Emb_Stl === $countcombinarray) {
           // dd($jecheck);
               DB::table('recordms')
               ->where('Record_Entry_Id', $recenid)
               ->update(['Dye_Check' => 1]);
               // dd("Updated normal to 0");

           //Saving Checked CheckBoxes to table....
           if($recenid){
               //Updating Steel checkbox...
               // dd($je_check_Steelkey);
               if($je_check_Steelkey){
               $countje=count($je_check_Steelkey);
               //dd($countje);
                   for ($i=0; $i<$countje; $i++) {
                   $jecheckv = array_keys($je_check_Steelkey);
                   $updateSQL = DB::table('stlmeas')
                       ->where('steelid', $jecheckv[$i])
                       ->update(['dye_check' => 1]);
                   }
               }
               //Udating EMB Data Checkbox...
               if($je_check_Itemkey){
                   //dd($je_check_Item);
                   $je_check_Item=array_keys($je_check_Itemkey);
                   foreach($je_check_Item as $jecheck){
                       DB::table('embs')
                       ->where('meas_id', $jecheck)
                       ->update(['dyE_check' => 1]);
                   }
                   // dd("Normal Data Checkbox is Update");
               }
           // dd("CheckBox Saved successfully....");
           }

           //Saving Date to database =========================================================================================
           if($customDateInputS){
               foreach ($customDateInputS as $key => $value) {
                   //dd($key, $value);
                   if($value){
                       DB::table('stlmeas')
                       ->where('steelid', $key)
                       ->update(['dyE_chk_dt' => $value]);
                   }
               }
           }

               //dd($customDateInputN);
               if($customDateInputN){
               foreach ($customDateInputN as $key => $value) {
                   //dd($key, $value);
                   if($value){
                       DB::table('embs')
                       ->where('meas_id', $key)
                       ->update(['dyE_chk_dt' => $value]);
                   }
               }
           }

              // Update the 'mb_status' field in the 'bills' table to 5 for the given bill ID
           $updateMbstatus=DB::table('bills')
                   ->where('t_bill_id', $tbillid)
                   ->update(['mb_status' => 5]);
           
             // Check if the update was successful
       if ($updateMbstatus) {

             //Email notification for MB status

          // Define the new status
          $newStatus = 5;


          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $WorkId)->first();

          // Fetch the EE  details related to the given work_id
          $eeDetails = DB::table('eemasters')->where('eeid', $workdata->EE_id)->first();
          //dd($eeDetails);
          
            // Fetch the DYE  details related to the given work_id
            $from = DB::table('dyemasters')->where('dye_id', $workdata->DYE_id)->first();

          if ($eeDetails) {
 
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);
              // Send the notification email to the JE
              Mail::to($eeDetails->email)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $from , $eeDetails));
          } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }

       }
       
       
         $workid=$WorkId;
         return redirect()->route('billlist', ['workid' => $workid]);
       }
       else {
        // Fetch section engineers and their names
        $DBsectionEng=DB::table('workmasters')
        ->select('SO_Id')
        ->where('Work_Id',$WorkId)
        ->get();
        // dd($DBsectionEng);
        $DBSectionEngNames = [];

        foreach ($DBsectionEng as $item)
        {
            $sectionEngName = DB::table('jemasters')
                ->select('name')
                ->where('jeid', $item->SO_Id)
                ->first();

            if ($sectionEngName) {
                $DBSectionEngNames[] = $sectionEngName->name;
            }
        }

        // Fetch maximum measurement date for the bill
        $embsmaxdt = DB::table('embs')
        ->where('t_bill_id', '=', $tbillid)
        ->max('measurment_dt');
        // dd($embsmaxdt);

         // Fetch division name based on work ID
        $divNm = DB::table('workmasters')
        ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
        ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
        ->where('workmasters.Work_Id', '=', $WorkId)
        ->value('divisions.div');


           echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

           echo "<script>
               document.addEventListener('DOMContentLoaded', function() {
                   Swal.fire({
                       icon: 'error',
                       title: 'Warning...',
                       text: 'All CheckBox should be checked and Measurement Checked Dates should be filled.'
                   });
               });
           </script>";
            // dd($recnovalues);
           return view('DeputyCheck',compact('embsmaxdt','recnovalues','titemno','BillDt','DBSectionEngNames','divNm','fund_Hd1','tbillid','WorkId','workDetails1','Recordeno',));
        //
        }

    }

    // Handle revert button click
    if($BtnRevert){
        //   DB::table('bills')
        //     ->where('t_bill_id', $tbillid)
        //     ->update(['mb_status' => 2]);
        //     //dd("Submteed");
         // Redirect to 'billlist' route with 'workid' as a parameter
         $workid=$WorkId;
         
         DB::table('revort_reason')->updateOrInsert(
            // Condition to check for t_bill_id
            ['t_bill_id' => $tbillid],
            // Data to update or insert
            [
                'dep_res' => $request->reason,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // $UpdateDyeRevert = DB::table('bills')
        // ->where('t_bill_id', $tbillid)
        // ->update(['dye_revert' => 1]);
        // dd($UpdateDyeRevert);
                 $revert = DB::table('bills')
            ->where('t_bill_id', $tbillid)
            ->update(['mb_status' => 2,'mbstatus_so' => 0,'dye_revert' => 1]);
            
            //revert mail notification send 
            if($revert)
            {
    
            $revertstatus=2;
    
             //Work information
             $workdata=DB::table('workmasters')->where('Work_Id', $request->WorkId)->first();
    
             // Fetch the Agency  details related to the given work_id
             $from = DB::table('dyemasters')->where('dye_id', $workdata->DYE_id)->first();
    //dd($from);
            
             // Fetch the JE details related to the given work_id
             $jeDetails = DB::table('jemasters')->where('jeid', $workdata->jeid)->first();
    
            
             if ($jeDetails) {
     
                 $tbilldata=DB::table('bills')->where('t_bill_Id' , $request->tbillid)->first();
                 //dd($jeDetails);
                 // Send the notification email to the JE
                 Mail::to($jeDetails->email)->queue(new RevertMBNotification($revertstatus, $workdata , $tbilldata , $from , $jeDetails));
             } else {
                 // Handle the case where no JE details are found
                 // You can log the error or throw an exception
             }
    
          }
            
            
            
         return redirect()->route('billlist', ['workid' => $workid]);
    }
}

}


?>
