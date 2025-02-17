<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Emb;
use App\Models\Workmaster;
use App\Imports\ExcelImport;
use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\MBStatusUpdatedMail;
use App\Helpers\CommonHelper;
use App\Mail\RevertMBNotification;



//All measurement checking by Agencies
class AgencyCheckController extends Controller
{

    //Handle the agency count functionality.
   public function AgencyCnt(Request $request)
   {
       
        try{
            
            
        // dd($request);
         // Retrieve the bill ID, work ID, and bill date from the request
        $tbillid=$request->input('t_bill_Id');
        // dd($tbillid);

        $WorkId=$request->input("workid");
        // dd($WorkId);

        $billdate=$request->input("Bill_Dt");
        // dd($billdate);


                //dd($tBillNo,$WorkId,$tbillid,$billDate);
                 // Retrieve bill item IDs associated with the bill
                $bitemid = DB::table('bil_item')
                ->where('t_bill_id', '=', $tbillid)
                ->get('b_item_id');

                 // Loop through each bill item ID
            foreach ($bitemid as $items) {
                $bitemId = $items->b_item_id;
                $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->get()->value('item_id');
                
                //Exception  for item id
                if (!$itemid) {
                    throw new Exception('Item ID not found for b_item_id: ' . $bitemId);
                }

                if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017",
                        "002023", "002024", "003351", "003352", "003878"]))
                {
                    //dd("Steel Data");
                } else {
                    //dd("Normal data ");
                }
            }

             // Retrieve all bill items associated with the bill
            $bitemsnm = DB::table('bil_item')
                ->where('t_bill_id', '=', $tbillid)
                ->get();

                 // Check if records already exist in the recordms table
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
   // Retrieve distinct measurement dates from embs and stlmeas tables
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

        // Merged Date  values Of both tables....
        $combinedCollection = $stlmeasd->merge($embsd);
        $mergeddts = $combinedCollection->all();
        
        
         //Exception check for dates
        if (empty($mergeddts)) {
            throw new Exception('No measurement dates found for the given t_bill_id');
        }

        
        
        
        
        //dd($mergeddts);
        //dd($mergeddts[]->measurment_dt);
        //dd($mergeddts[0]->date_meas);
        $obdata = [];

        // Loop through merged dates to format and collect them
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
        
        //$dateArray1 =0;
        
                //Exception check for dates
          if (empty($dateArray1)) {
            throw new Exception('No measurement dates found for the given Dates');
        }


        // Sort the array in ascending order
        sort($dateArray1);
        //dd($dateArray1);

         // Retrieve distinct dates from embs and stlmeas tables for the given work ID
        $distinctdts = DB::table('embs')
            ->Join('stlmeas', 'embs.Work_Id', '=', 'stlmeas.Work_Id')
            ->select(
                DB::raw('DISTINCT DATE_FORMAT(embs.measurment_dt, "%Y-%m-%d") as formatted_measurment_dt'),
                DB::raw('DATE_FORMAT(stlmeas.date_meas, "%Y-%m-%d") as formatted_date_meas')
            )
            ->where('embs.Work_Id', '=', $WorkId)

            ->get();
        //dd($distinctdts);

        // Initialize an array to store combined dates
            $combinedDates = [];


        foreach($dateArray1 as $dtarr){

              // Get the last record entry ID for the given bill ID
            $lastrecordEntryId = DB::table('recordms')
                ->select('Record_Entry_Id')
                ->where('t_bill_id', '=', $tbillid)
                ->orderBy('Record_Entry_Id', 'desc')
                ->first();

          // Generate a new record entry ID
            if ($lastrecordEntryId) {
                $lastrecordid = $lastrecordEntryId->Record_Entry_Id;
                $lastFourDigits = substr($lastrecordid, -4);
                $incrementedLastFourDigits = str_pad(intval($lastFourDigits) + 1, 4, '0', STR_PAD_LEFT);
                $newrecordentryid = $tbillid . $incrementedLastFourDigits;
            }

            else {
                $newrecordentryid = $tbillid . '0001';
            }

            // Get the last record entry number for the given bill ID
            $Record_Entry_No = DB::table('recordms') ->select('Record_Entry_No')
            ->where('t_bill_id', '=', $tbillid)
            ->orderBy('Record_Entry_No', 'desc')
            ->value('Record_Entry_No');
            
            //dd($tbillid);
           //dd($dtarr);
           
           // Get normal database entries for the given bill ID and measurement date
            $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $dtarr)->get();
            
            //dd($NormalDb);
            // Generate a new record entry number
            $lastFourDigits = substr($Record_Entry_No, -1);
            $incrementedLastFourDigits = str_pad(intval($lastFourDigits) + 1, 4, '0', STR_PAD_LEFT);
            
            
            // dd($incrementedLastFourDigits);
            $FinalRecordEntryNo = str_pad(intval($Record_Entry_No) + 1, 4, '0', STR_PAD_LEFT);
            //dd($dateArray);
            
            
           // Get normal and steel database entries for the given bill ID and measurement date
           $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $dtarr)->get();
           //dd($NormalDb);

           $StillDb = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $dtarr)->get();
           
           
           //Check normal data or steel data
            if ($NormalDb->isEmpty() && $StillDb->isEmpty()) {
            throw new Exception('No records found for date: ' . $dtarr);
             }
             
             
           //dd($StillDb);
           // $countcombinarray=count($StillDb);
         // Combine normal and steel database entries
           $combinarray = $NormalDb->concat($StillDb);
           //dd($combinarray);


           //Count of combine data...
           $countcombinarray=count($combinarray);
           //dd($countcombinarray);
            $Stldyechkcount1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $dtarr)->where('dye_check',"=",1)->get();
            $Stldyechkcount=count($Stldyechkcount1);
             //dd($Stldyechkcount);
             
             
           // Get the count of normal dye checks for the given date
            $EmbdyeChkCount = DB::table('embs')
            ->where('t_bill_id', $tbillid)
            ->where('measurment_dt', $dtarr)
            ->where('dye_check', "=", 1)
            ->count();
             //dd($EmbdyeChkCount);

             // Get the total count of dye checks (normal and steel)
            $Count_Chked_Emb_Stl= $EmbdyeChkCount + $Stldyechkcount;
           //dd($Count_Chked_Emb_Stl , $countcombinarray);

             // Check if the total count of dye checks matches the count of combined entries
            if ($Count_Chked_Emb_Stl === $countcombinarray) {
                  // Insert a new record with dye check status as 1
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
          // Insert a new record with dye check status as 0
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
        // Get work details for the given work ID
            $workDetails = DB::table('workmasters')
                ->select('Work_Nm', 'Sub_Div', 'Agency_Nm', 'Work_Id', 'Wo_Dt','Period','WO_No','Stip_Comp_Dt')
                ->where('Work_Id', '=', $WorkId)
                ->first();

          $workdata=DB::table('workmasters')->where('Work_Id' , $WorkId)->first();
          $fund_Hd = DB::table('fundhdms')->where('F_H_id' , $workdata->F_H_id)->first();
        
        
            // $fund_Hd = DB::table('workmasters')
            //     ->select('fundhdms.Fund_HD_M')
            //     ->join('fundhdms', function ($join) use ($WorkId) {
            //         $join->on(DB::raw("LEFT(workmasters.F_H_Code, 4)"), '=', DB::raw("LEFT(fundhdms.F_H_CODE, 4)"))
            //             ->where('workmasters.Work_Id', '=', $WorkId);
            //     })
            //     ->first();
// Retrieve all records for the given Work_Id from the 'recordms' table
            $recinfo=  DB::table('recordms')
                    ->where('Work_Id', '=', $WorkId)
                    ->get();
                    //dd($recinfo);

    // Get division name by joining 'workmasters', 'subdivms', and 'divisions' tables                
            $divName = DB::table('workmasters')
                ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
                ->where('workmasters.Work_Id', '=', $WorkId)
                ->value('divisions.div');

        // Get all section engineers from the 'designations' table
            $sectionEngineer = DB::table('designations')->get();

            // Get work details for the given Work_Id from the 'workmasters' table
            $Work_Dtl = DB::table('workmasters')
                ->select('Work_Nm', 'Sub_Div', 'WO_No', 'Period', 'Stip_Comp_Dt')
                ->where('Work_Id', '=', $WorkId)
                ->first();

                // Get division name by joining 'workmasters', 'subdivms', and 'divisions' tables (duplicate of $divName)
            $divNm = DB::table('workmasters')
                ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
                ->where('workmasters.Work_Id', '=', $WorkId)
                ->value('divisions.div');

                // Get combined item number, description, quantity, and unit from 'bil_item' table for the given bill ID
            $titemno = DB::table('bil_item')
                ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc', 'exec_qty', 'item_unit')
                ->where('t_bill_id', '=', $tbillid)
                ->get();

                // Get the first entry for the given Work_Id from the 'embs' table
            $embdtls = DB::table('embs')
                ->where('Work_Id', '=', $WorkId)
                ->first();

                // Get detailed information by joining 'embs', 'bil_item', and 'recordms' tables
            $Item1Data = DB::table('embs')
                ->leftJoin('bil_item', 'embs.b_item_id', '=', 'bil_item.b_item_id')
                ->leftJoin('recordms', 'embs.t_bill_id', '=', 'recordms.t_bill_id')
                ->where('embs.t_bill_id', $tbillid)
                ->select('bil_item.t_item_no', 'bil_item.item_desc', 'bil_item.exec_qty',
                    'bil_item.item_unit', 'bil_item.ratecode', 'bil_item.bill_rt', 'embs.*')
                ->get();

                // Get all records from 'embs' table, ordered by measurement date, and join with 'bil_item' and 'recordms' tables
            $RecordData = DB::table('embs')
                ->leftJoin('bil_item', 'embs.b_item_id', '=', 'bil_item.b_item_id')
                ->leftJoin('recordms', 'embs.t_bill_id', '=', 'recordms.t_bill_id')
                ->where('embs.t_bill_id', $tbillid)
                ->select('bil_item.*', 'embs.*')
                ->orderby('measurment_dt', 'asc')
                ->get();
                //dd($RecordData);
               //dd($RecordData->Record_Entry_No);

     // Get item details for the given bill ID from the 'bil_item' table
            $titemnoRecords = DB::table('bil_item')
                ->select('t_item_no', 'item_desc', 'exec_qty', 'ratecode', 'bill_rt')
                ->where('t_bill_id', '=', $tbillid)
                ->get();

                // Get all records for the given bill ID from the 'recordms' table
            $Recordwise = DB::table('recordms')
            ->where('t_bill_id', '=', $tbillid)
            ->get();

            $html ='';

            // Retrieve all bill item data for the given bill ID from the 'bil_item' table
            $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
            $html .='<div class="container-fluid">';
            $html .='<div class="table-responsive">';
            $html .= '<table class="table table-bordered" >';
            
            foreach($billitemdata as $itemdata)
        {
            $bitemId=$itemdata->b_item_id;
            //dd($bitemId);
             // Get normal measurement data for the given item ID from the 'embs' table
            $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->get();
            // Get steel measurement data for the given item ID from the 'stlmeas' table
            $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->get();
            //meas data check
            if (!$measnormaldata->isEmpty() || !$meassteeldata->isEmpty()) {

                $html .= '<tr>';
                $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; background-color:lightpink;">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th style="border: 1px solid black; padding: 8px;  width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th>';
                $html .= '<th style="border: 1px solid black; padding: 8px;  width: 90%; text-align: justify;"> ' . $itemdata->exs_nm . '</th>';
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '</table>';
                $html .= '</tr>';


            // Get item ID for the given item ID from the 'bil_item' table
            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
            //dd($itemid);
             // Check if the last 6 digits of the item ID match specific values
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
            {
                  // Get steel data for the given bill ID and item ID from the 'stlmeas' table
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->get();
                 
                 // Get all records from the 'bill_rcc_mbr' table
                $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                // dd($stldata , $bill_rc_data);

                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

  // Update steel data by swapping values of specific columns if they do not match
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

 // Initialize sums for each column
            $sums = array_fill_keys($ldiamColumns, 0);

             // Calculate sums for each column
            foreach ($stldata as $row) {
                 foreach ($ldiamColumns as $ldiamColumn) {
                    $sums[$ldiamColumn] += $row->$ldiamColumn;
                 }
            }//dd($stldata);

 // Get bill member data where the record exists in the 'stlmeas' table
            $bill_member = DB::table('bill_rcc_mbr')
            ->whereExists(function ($query) use ($bitemId) {
            $query->select(DB::raw(1))
            ->from('stlmeas')
            ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
            ->where('bill_rcc_mbr.b_item_id', $bitemId);
            })
            ->get();

       // Get rc_mbr_ids for the given item ID from the 'bill_rcc_mbr' table
            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();


            foreach ($bill_member as $index => $member) {
                //dd($member);
                    $rcmbrid=$member->rc_mbr_id;
                     // Fetch data from 'stlmeas' table for the current rc_mbr_id
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->get();
                //dd($memberdata);

                 // Check if memberdata is not empty
            if ( !$memberdata->isEmpty()) {
            $html .= '<tr>';
            $html .= '<table style="border-collapse: collapse; width: 100%;  background-color: lightblue;"><thead>';
            $html .= '<th colspan="1" style="border: 1px solid black; padding: 8px;">Sr No :' . $member->member_sr_no . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px; ">RCC Member :' . $member->rcc_member . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px; ">Member Particular :' . $member->member_particulars . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px;">No Of Members :' . $member->no_of_members . '</th>';
            $html .= '</thead></table>';
            $html .= '</tr>';

            // Loop through stldata
            foreach ($stldata as $bar) {

                // Check if rc_mbr_id matches
                if ($bar->rc_mbr_id == $member->rc_mbr_id) {

                //dd($bar);// Assuming the bar data is within a property like "bar_data"
                $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                $html .= '<tr>
                <table style="border-collapse: collapse; width: 100%; border: 1px solid black;">
                <thead>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 13%; min-width: 13%;">Bar Particulars</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">6mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">8mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">10mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">12mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">16mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">20mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 9%; min-width: 9%;">25mm</th>
                <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 9%; min-width: 9%;">28mm</th>
                </thead>

                <tbody>

                 <td style="border: 1px solid black; padding: 8px; width: 5%;  min-width: 5%; text-align:left;">'. $bar->bar_sr_no .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 13%; min-width: 13%; text-align:left text-align:right;;">'. $bar->bar_particulars.'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text-align:right;">'. $bar->no_of_bars .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text-align:right;">'. $bar->bar_length .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text-align:right;">'. $bar->ldiam6 .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text-align:right;">'. $bar->ldiam8 .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text-align:right;">'. $bar->ldiam10 .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text-align:right;">'. $bar->ldiam12 .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text-align:right;">'. $bar->ldiam16 .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%; text-align:right;">'. $bar->ldiam20 .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text-align:right;">'. $bar->ldiam25 .'</td>
                 <td style="border: 1px solid black; padding: 8px; width: 9%; min-width: 9%; text-align:right;">'. $bar->ldiam28 .'</td>
                </tbody></table></tr>';
                    }
                }
            }
            }
        }

        // Fetch normal data from 'embs' table based on t_bill_id and b_item_id
        $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->get();

            foreach($normaldata as $nordata)
            {
                        $formula= $nordata->formula;
                            $html .= '<tr>';
                            $html .= '<table style="border-collapse: collapse; width: 100%;"><tbody>';
                            $html .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 5%;">' . $nordata->sr_no . '</td>';
                            $html .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 53%; word-wrap: break-word; max-width: 200px;">' . $nordata->parti . '</td>';
                        if($formula)
                        {
                            $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 32%; text-align:right;">' . $nordata->formula . '</td>';
                        }
                        else
                        {
                            // Check if formula exists
                            $html .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right;">' . $nordata->number . '</td>';
                            $html .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right;">' . $nordata->length . '</td>';
                            $html .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right;">' . $nordata->breadth . '</td>';
                            $html .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right;">' . $nordata->height . '</td>';
                        }
                        $html .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 10%; text-align:right;">' . $nordata->qty . '</td>';

            }
             $html .= '<tr>';
            $html .= '<tr>

            <td colspan="6" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right"><strong>Total</strong></td>
            <td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right"><strong>'.$itemdata->cur_qty.'</strong></td>
            </tr>';
            $html .='</tbody></table>';
            $html .= '</tr>';
        }
        }
        $html .= '</table>';
        $html .='</div>';
                $html .='</div>';



// Query to get the most frequently repeated 'ee_chk_dt' for the given Work_Id and t_Bill_Id
       $maxMeasdt = DB::table('embs')
        ->select('measurment_dt', DB::raw('COUNT(measurment_dt) as count'))
        ->where('Work_Id', '=', $WorkId)
        ->where('t_Bill_Id', '=', $tbillid)
        ->groupBy('measurment_dt')
        ->orderBy('measurment_dt', 'desc')
        ->first();
        // dd($maxRepeatedDate);

// Query to get the most frequently repeated 'dyE_chk_dt' for the given Work_Id and t_Bill_Id
        $maxRepeatedDateDYE = DB::table('embs')
        ->select('dyE_chk_dt', DB::raw('COUNT(dyE_chk_dt) as count'))
        ->where('Work_Id', '=', $WorkId)
        ->where('t_Bill_Id', '=', $tbillid)
        ->groupBy('dyE_chk_dt')
        ->orderBy('dyE_chk_dt', 'desc')
        ->first();
        // dd($maxRepeatedDateDYE);

// Assign the most frequently repeated 'ee_chk_dt' and 'dyE_chk_dt' to variables
        $minagcychkdt=$maxMeasdt->measurment_dt;
        $default_agecy_dye_dt=$maxRepeatedDateDYE->dyE_chk_dt;

        $returnHTML = $html;
        // /dd($workDetails);

// Query to get 'jeid' from 'workmasters' table for the given Work_Id
        $DBsectionEng=DB::table('workmasters')
            ->select('jeid')
            ->where('Work_Id',$WorkId)
            ->get();
        //   dd($DBsectionEng);
            $DBSectionEngNames = [];

        // Loop through each 'jeid' and get the corresponding 'name' from 'jemasters' table
    foreach ($DBsectionEng as $item)
    {
        $sectionEngName = DB::table('jemasters')
            ->select('name')
            ->where('jeid', $item->jeid)
            ->first();
        //dd($sectionEngName);
        if ($sectionEngName) {
            $DBSectionEngNames[] = $sectionEngName->name;
        }
    }
                        // dd($DBSectionEngNames);
                        // Return the 'AgencyCheck' view with the necessary variables
        return view('AgencyCheck',compact('DBSectionEngNames','returnHTML','billdate','workDetails','default_agecy_dye_dt','fund_Hd', 'sectionEngineer', 'divName', 'Work_Dtl', 'Recordwise', 'divNm', 'bitemid', 'FinalRecordEntryNo', 'titemnoRecords',  'embdtls', 'Item1Data', 'RecordData', 'tbillid', 'titemno', 'itemid','minagcychkdt'));
        
        
          } catch (Exception $e) {
            
             Log::error('Error inserting records: ' . $e->getMessage() );

             return redirect()->route('emb.workmasterdata', ['id' => $tbillid])
             ->with('error', 'An error occurred while processing the request: ' . $e->getMessage());
        }
    }


// Submit the data of  Agency checked 
    public function FunctionSubmitAgency(Request $request)
    {
        
      try {
              
              
        //dd($request);
        // Retrieve input values from the request
        $WorkId=$request->input('WorkId');
        $tbillid=$request->input('tbillid');
        $Agency_Chk_Dt=$request->input('date');
        //  dd($WorkId,$tbillid);

          // Update the 'bills' table with the given Work_Id and t_Bill_Id
                 $billupdate = DB::table('bills')
                    ->where('Work_Id', '=', $WorkId)
                    ->where('t_Bill_Id', '=', $tbillid)
                    ->update([
                        'Work_Id' => $WorkId,
                        'Agency_Check'=>1,
                        'agency_Check_Date' => $Agency_Chk_Dt]);
                        
                       // dd($billupdate);
                        // Check if the bill update was successful
                        //   if(!$billupdate)
                        // {
                        //     throw new \Exception('Bill update failed');
                        // }
                    // dd("Done...");
                    // AgencyCnt($WorkId,$tbillid,$Agency_Chk_Dt);

                    // Store the WorkId in a variable
                    $workid=$WorkId;

                     // Update the 'mb_status' in the 'bills' table for the given t_bill_id
                    $mbstatus = DB::table('bills')
                                ->where('t_bill_id', $tbillid)
                                ->update(['mb_status' => 4]);
                              
                        // Check if the update was successful
                   if ($mbstatus) {
                         //Email notification for MB status

                        // Define the new status
                        $newStatus = 4;


                                        //Work information
                        $workdata=DB::table('workmasters')->where('Work_Id', $workid)->first();

                        // Fetch the Agency  details related to the given work_id
                        $from = DB::table('jemasters')->where('jeid', $workdata->jeid)->first();
 //dd($from);
                        $Dyeid=DB::table('workmasters')->where('Work_Id', $workid)->value('DYE_id');
                        // Fetch the JE details related to the given work_id
                        $DyeDetails = DB::table('dyemasters')->where('dye_id', $workdata->DYE_id)->first();

                       
                        if ($DyeDetails) {
                
                            $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
                            //change format of item no  and bill type
                            $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
                            $billType = CommonHelper::getBillType($tbilldata->final_bill);
                            //dd($jeDetails);
                            // Send the notification email to the JE
                            Mail::to($DyeDetails->email)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $from , $DyeDetails));
                        } else {
                            // Handle the case where no JE details are found
                            // You can log the error or throw an exception
                        }


                    }

                    // Redirect to the 'billlist' route with the given workid
                    return redirect()->route('billlist', ['workid' => $workid]);
                    
        } catch (\Exception $e) {
                    Log::error('Error in FunctionSubmitAgency: ' . $e->getMessage());

                    return redirect()->route('emb.workmasterdata', ['id' => $tbillid])
                    ->with('error', 'An error occurred while processing the request: ' . $e->getMessage());
                }


   }


   //Revert measurement data from Agency
    public function RevertdataAgency(Request $request)
    {

       $revert= DB::table('bills')
        ->where('t_bill_id', $request->tbillid)
        ->update(['mb_status' => 2,'mbstatus_so' => 0,'Agency_revert' => 1]);

 DB::table('revort_reason')->updateOrInsert(
            // Condition to check for t_bill_id
            ['t_bill_id' => $request->tbillid],
            // Data to update or insert
            [
                'agcy_res' => $request->reason,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );


 //revert mail notification send
        if($revert)
        {

        $revertstatus=1;

         //Work information
         $workdata=DB::table('workmasters')->where('Work_Id', $request->WorkId)->first();

         // Fetch the Agency  details related to the given work_id
         $from = DB::table('agencies')->where('id', $workdata->Agency_Id)->first();
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
      
      
     return redirect()->route('billlist', ['workid' => $request->WorkId]);
    }
    
     public function fetchReasons(Request $request)
    {
        $t_bill_id = $request->input('t_bill_id');

        // Fetch the reasons related to the given t_bill_id from the revort_reason table
        $revertReasons = DB::table('revort_reason')
            ->where('t_bill_id', $t_bill_id)
            ->first(); // Get only the first matching record

        // Check if reasons are available
        if ($revertReasons) {
            // Prepare the response for Agency, Deputy, and Executive
            $agency_reason = !empty($revertReasons->agcy_res) ? $revertReasons->agcy_res : "No agency response available.";
            $deputy_reason = !empty($revertReasons->dep_res) ? $revertReasons->dep_res : "No deputy response available.";
            $executive_reason = !empty($revertReasons->ee_res) ? $revertReasons->ee_res : "No executive response available.";
        } else {
            // If no reasons are found, return default messages
            $agency_reason = "No reasons available for this bill.";
            $deputy_reason = "No reasons available for this bill.";
            $executive_reason = "No reasons available for this bill.";
        }

        // Return the response as JSON for each reason type
        return response()->json([
            'agency_reason' => $agency_reason,
            'deputy_reason' => $deputy_reason,
            'executive_reason' => $executive_reason
        ]);
    }

    
}
