<?php
namespace App\Http\Controllers;
use DateTime; 
use Exception;
use Carbon\Carbon;
use App\Models\Workmaster;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Mail;
use App\Mail\MBStatusUpdatedMail;
use App\Mail\RevertMBNotification;


//Measurement checked related function (executive engineer) 
class ExecutiveEngineerCnt extends Controller{

//* Handle incoming request to process executive data.
   public function funExecutiveData(Request $request) {
        
        // Extracting data from the request
        $WorkId = $request->input('workid'); // Retrieve 'workid' from the request input
        $tBillNo = $request->input('t_bill_No'); // Retrieve 't_bill_No' from the request input
        $billDate = $request->input('Bill_Dt'); // Retrieve 'Bill_Dt' (Bill Date) from the request input
        $tbillid = $request->input('t_bill_Id'); // Retrieve 't_bill_Id' from the request input

        // Store $billDate in a session variable
        $request->session()->put('billDate', $billDate);

        // Call a method 'commongotoembcontroller' with the extracted data
    // This method likely performs some common operations related to the executive data
        $commonheader=$this->commongotoembcontroller($WorkId , $tBillNo,$billDate,$tbillid,1);
        
         // Return the result from the 'commongotoembcontroller' method
        return $commonheader;

    }


    // * Handle processing of executive data.
    public function commongotoembcontroller($WorkId , $tBillNo,$billDate,$tbillid,$recnovalues)
    {
                // Retrieve all 'b_item_id' from 'bil_item' table where 't_bill_id' matches the given value
                $bitemid = DB::table('bil_item')
                ->where('t_bill_id', '=', $tbillid)
                ->get('b_item_id');

                // Iterate through each 'b_item_id'
            foreach ($bitemid as $items) {
                $bitemId = $items->b_item_id;

                  // Retrieve 'item_id' from 'bil_item' table where 'b_item_id' matches the current 'b_item_id'
                $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->get()->value('item_id');

                 // Check if 'item_id' ends with specific substrings to determine data type (e.g., Steel Data or Normal Data)
                if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017",
                        "002023", "002024", "003351", "003352", "003878"]))
                {
                    //dd("Steel Data");
                } else {
                    //dd("Normal data ");
                }
            }

            // Retrieve all rows from 'bil_item' table where 't_bill_id' matches the given value
            $bitemsnm = DB::table('bil_item')
                ->where('t_bill_id', '=', $tbillid)
                ->get();

                  // Check if there are existing records in 'recordms' table with the given 't_Bill_Id'
            $exists = DB::table('recordms')
                ->where('t_Bill_Id', $tbillid)
                ->get();

                 // If records exist, delete them from 'recordms' table where 't_Bill_Id' and 'Work_Id' match the given values
            if ($exists) {
                DB::table('recordms')
                    ->where('t_Bill_Id', $tbillid)
                    ->where('Work_Id', '=', $WorkId)
                    ->delete();

            }
            // dd("Record is deleted");

         // Retrieve distinct measurement dates from 'embs' table where 't_bill_id' matches the given value
            $embsd = DB::table('embs')
            ->select('measurment_dt')
            ->distinct()
            ->where('t_bill_id', '=', $tbillid)
            ->get();
        //dd($embsd);

         // Retrieve distinct measurement dates from 'stlmeas' table where 't_bill_id' matches the given value
        $stlmeasd = DB::table('stlmeas')
            ->select('date_meas')
            ->distinct()
            ->where('t_bill_id', '=', $tbillid)
            ->get();
        //dd($stlmeasd);

         // Merge the two collections of dates and retrieve unique dates
        $combinedCollection = $stlmeasd->merge($embsd);
        $mergeddts = $combinedCollection->all();
       
         // Initialize an empty array for storing dates
        $obdata = [];

         // Iterate through the merged dates to format and store unique dates
        foreach ($mergeddts as $dateStr) {
            if(isset($dateStr->date_meas) && !empty($dateStr->date_meas)){
            $dates = Carbon::createFromFormat('Y-m-d',  $dateStr->date_meas)->format('Y-m-d');

            // Remove duplicate dates and sort them in ascending order
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

         // Retrieve distinct formatted dates from 'embs' and 'stlmeas' tables where 'Work_Id' matches the given value
        $distinctdts = DB::table('embs')
            ->Join('stlmeas', 'embs.Work_Id', '=', 'stlmeas.Work_Id')
            ->select(
                DB::raw('DISTINCT DATE_FORMAT(embs.measurment_dt, "%Y-%m-%d") as formatted_measurment_dt'),
                DB::raw('DATE_FORMAT(stlmeas.date_meas, "%Y-%m-%d") as formatted_date_meas')
            )
            ->where('embs.Work_Id', '=', $WorkId)

            ->get();

         // Initialize an empty array for combined dates
            $combinedDates = [];

       // Iterate through the sorted unique dates
        foreach($dateArray1 as $dtarr){

             // Retrieve the last 'Record_Entry_Id' from 'recordms' table where 't_bill_id' matches the given value
            $lastrecordEntryId = DB::table('recordms')
                ->select('Record_Entry_Id')
                ->where('t_bill_id', '=', $tbillid)
                ->orderBy('Record_Entry_Id', 'desc')
                ->first();

               // Generate a new 'Record_Entry_Id' by incrementing the last four digits
            if ($lastrecordEntryId) {
                $lastrecordid = $lastrecordEntryId->Record_Entry_Id;
                $lastFourDigits = substr($lastrecordid, -4);
                $incrementedLastFourDigits = str_pad(intval($lastFourDigits) + 1, 4, '0', STR_PAD_LEFT);
                $newrecordentryid = $tbillid . $incrementedLastFourDigits;
            }

            else {
                $newrecordentryid = $tbillid . '0001';
            }

             // Retrieve the last 'Record_Entry_No' from 'recordms' table where 't_bill_id' matches the given value
            $Record_Entry_No = DB::table('recordms') ->select('Record_Entry_No')
            ->where('t_bill_id', '=', $tbillid)
            ->orderBy('Record_Entry_No', 'desc')
            ->value('Record_Entry_No');
            //dd($tbillid);

            // Retrieve data from 'embs' table where 't_bill_id' and 'measurment_dt' match the given values
            $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $dtarr)->get();

             // Increment the last digit of 'Record_Entry_No' to generate a new 'Record_Entry_No'
            $lastFourDigits = substr($Record_Entry_No, -1);
            $incrementedLastFourDigits = str_pad(intval($lastFourDigits) + 1, 4, '0', STR_PAD_LEFT);
            // dd($incrementedLastFourDigits);
            $FinalRecordEntryNo = str_pad(intval($Record_Entry_No) + 1, 4, '0', STR_PAD_LEFT);
            //dd($dateArray);
           //Bill Item Table Related data="
           $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $dtarr)->get();
           //dd($NormalDb);

             // Retrieve data from 'stlmeas' table where 't_bill_id' and 'date_meas' match the given values
           $StillDb = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $dtarr)->get();
           //dd($StillDb);
               
         // Concatenate the two data collections
           $combinarray = $NormalDb->concat($StillDb);
           //dd($combinarray);

            // Count the number of combined records
           $countcombinarray=count($combinarray);
           //dd($countcombinarray);
           
            $Stldyechkcount1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $dtarr)->where('dye_check',"=",1)->get();
            $Stldyechkcount=count($Stldyechkcount1);
             //dd($Stldyechkcount);

              // Retrieve and count records from 'stlmeas' table where 'dye_check' is 1
            $EmbdyeChkCount = DB::table('embs')
            ->where('t_bill_id', $tbillid)
            ->where('measurment_dt', $dtarr)
            ->where('dye_check', "=", 1)
            ->count();
             //dd($EmbdyeChkCount);

              // Calculate the total count of checked records
            $Count_Chked_Emb_Stl= $EmbdyeChkCount + $Stldyechkcount;
          
             // If all combined records are checked, insert a new record with 'Dye_Check' set to 1
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
             // If not all combined records are checked, insert a new record with 'Dye_Check' set to 0
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
                   // Retrieve work details from 'workmasters' table based on the given WorkId
                        $workDetails1 = DB::table('workmasters')
                        ->select('Work_Nm', 'Sub_Div', 'Agency_Nm', 'Work_Id', 'Wo_Dt','Period','WO_No','Stip_Comp_Dt', 'Tender_Id')
                        ->where('Work_Id', '=', $WorkId)
                        ->first();
                        //dd($workDetails1);

                        // Retrieve work data from 'workmasters' table based on the given WorkId
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

                        // Retrieve 'b_item_id' from 'bil_item' table where 't_bill_id' matches the given value
                        $bitemid = DB::table('bil_item')
                            ->where('t_bill_id', '=', $tbillid)
                            ->get('b_item_id');

                        // Iterate through each 'b_item_id'
                        foreach ($bitemid as $items) {
                            $bitemId = $items->b_item_id;
                            // Retrieve 'item_id' from 'bil_item' table where 'b_item_id' matches the current 'b_item_id'
                            $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->get()->value('item_id');

                             // Check if 'item_id' ends with specific substrings to determine data type (e.g., Steel Data or Normal Data)
                            if (
                                in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017",
                                    "002023", "002024", "003351", "003352", "003878"])
                            ) {
                                //dd("Steel Data");
                            } else {
                                //dd("Normal data ");
                            }
                        }

                        // Retrieve all rows from 'bil_item' table where 't_bill_id' matches the given value
                        $bitemsnm = DB::table('bil_item')
                            ->where('t_bill_id', '=', $tbillid)
                            ->get();

                            // Check if there are existing records in 'recordms' table with the given 't_Bill_Id'
                        $exists = DB::table('recordms')
                            ->where('t_Bill_Id', $tbillid)
                            ->get();
                        // dd("Record is deleted");

                        // Retrieve distinct measurement dates from 'embs' table where 't_bill_id' matches the given value
                        $embsd = DB::table('embs')
                            ->select('measurment_dt')
                            ->distinct()
                            ->where('t_bill_id', '=', $tbillid)
                            ->get();
                        //dd($embsd);

                        // Retrieve distinct measurement dates from 'stlmeas' table where 't_bill_id' matches the given value
                        $stlmeasd = DB::table('stlmeas')
                            ->select('date_meas')
                            ->distinct()
                            ->where('t_bill_id', '=', $tbillid)
                            ->get();
                        //dd($stlmeasd);


                      // Retrieve records from 'recordms' table where 'Work_Id' matches the given value
                        $recinfo=  DB::table('recordms')
                                ->where('Work_Id', '=', $WorkId)
                                ->get();
                                //dd($recinfo);

                                // Retrieve division name by joining 'workmasters', 'subdivms', and 'divisions' tables
                        $divName1 = DB::table('workmasters')
                            ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                            ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
                            ->where('workmasters.Work_Id', '=', $WorkId)
                            ->value('divisions.div');

                        $sectionEngineer = DB::table('designations')->get();

                        // Retrieve work details from 'workmasters' table based on the given WorkId
                        $Work_Dtl = DB::table('workmasters')
                            ->select('Work_Nm', 'Sub_Div', 'WO_No', 'Period', 'Stip_Comp_Dt')
                            ->where('Work_Id', '=', $WorkId)
                            ->first();

                            // Retrieve division name by joining 'workmasters', 'subdivms', and 'divisions' tables
                        $divNm = DB::table('workmasters')
                            ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                            ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
                            ->where('workmasters.Work_Id', '=', $WorkId)
                            ->value('divisions.div');

                            // Retrieve item numbers and other details from 'bil_item' table based on 't_bill_id'
                        $titemno = DB::table('bil_item')
                            ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc', 'exec_qty', 'item_unit')
                            ->where('t_bill_id', '=', $tbillid)
                            ->get();
                        //dd($titemno);

                        // Retrieve details from 'embs' table based on 'Work_Id'
                        $embdtls = DB::table('embs')
                            ->where('Work_Id', '=', $WorkId)
                            ->first();

                            // Retrieve item and record details by joining 'embs', 'bil_item', and 'recordms' tables
                        $Item1Data = DB::table('embs')
                            ->leftJoin('bil_item', 'embs.b_item_id', '=', 'bil_item.b_item_id')
                            ->leftJoin('recordms', 'embs.t_bill_id', '=', 'recordms.t_bill_id')
                            ->where('embs.t_bill_id', $tbillid)
                            ->select('bil_item.t_item_no', 'bil_item.item_desc', 'bil_item.exec_qty',
                                'bil_item.item_unit', 'bil_item.ratecode', 'bil_item.bill_rt', 'embs.*')
                            ->get();

                            // Retrieve and order record data by joining 'embs', 'bil_item', and 'recordms' tables
                        $RecordData = DB::table('embs')
                            ->leftJoin('bil_item', 'embs.b_item_id', '=', 'bil_item.b_item_id')
                            ->leftJoin('recordms', 'embs.t_bill_id', '=', 'recordms.t_bill_id')
                            ->where('embs.t_bill_id', $tbillid)
                            ->select('bil_item.*', 'embs.*')
                            ->orderby('measurment_dt', 'asc')
                            ->get();
                        //dd($RecordData);

                        // Retrieve item numbers and other details from 'bil_item' table based on 't_bill_id'
                        $titemnoRecords = DB::table('bil_item')
                            ->select('t_item_no', 'item_desc', 'exec_qty', 'ratecode', 'bill_rt')
                            ->where('t_bill_id', '=', $tbillid)
                            ->get();

                        // Retrieve record entry numbers from 'recordms' table based on 't_bill_id'
                        $Recordeno = DB::table('recordms')
                        ->select('Record_Entry_No')
                        ->where('t_bill_id', '=', $tbillid)
                        ->get();
                        //dd($Recordeno);

                        // Retrieve section engineer IDs from 'workmasters' table based on 'Work_Id'
                        $DBsectionEng=DB::table('workmasters')
                        ->select('jeid')
                        ->where('Work_Id',$WorkId)
                        ->get();


                    // Retrieve section engineer names based on the retrieved IDs
                        $DBSectionEngNames = [];

                        foreach ($DBsectionEng as $item)
                        {
                            $sectionEngName = DB::table('jemasters')
                                ->select('name')
                                ->where('jeid', $item->jeid)
                                ->first();
                            // dd($sectionEngName);
                            if ($sectionEngName) {
                                $DBSectionEngNames[] = $sectionEngName->name;
                            }
                        }
                        // Retrieve the minimum dye check date from 'embs' table based on 't_bill_id'
                            $max_dye_date = DB::table('embs')
                            ->where('t_bill_id' , $tbillid)
                            ->min('dyE_chk_dt');

                            // Retrieve checked data from 'embs' table based on various conditions   
                            $checkeddata = DB::table('embs')
                            ->select('meas_id' , 'ee_chk_qty')
                            ->where('t_bill_id' , $tbillid)
                            ->where('ee_check' , 1)
                            ->where('notforpayment' , 0)
                            ->get();

            // Return the view with the retrieved data
            return view('ExecutiveEngineerEMB',compact('DBSectionEngNames','max_dye_date','workDetails1','billDate','fund_Hd1','divName1','Recordeno','titemnoRecords','titemno','tbillid','recnovalues'));
            //return redirect()->route('billlist', ['WorkId' => $WorkId]);
    }


    //Record entrywise executive engineer data check
    public function RecordWiseExecutiveCheckFun(Request $request)
    {
         // Retrieve input data from the request
        $BillDt = $request->input('billDate');
      //dd($BillDt);
        $tbillid = $request->input('tbillid_valuer');
        // dd($request);
        $WorkIdvv =$request->input('workid_valuer');

        $Rec_E_No=$request->input('Record_Entry_Nor');

        $SelectDtAll= $request->input('SelectDtAll');
        //dd($SelectDtAll);

        $SelectDtAllS= $request->input('SelectDtAllS');
        // dd($SelectDtAllS,$SelectDtAll);
         // Initialize an empty HTML variable to store the generated HTML content
        $html ='';

        // Retrieve bill item data based on tbillid
        $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();

           // Retrieve record date based on tbillid and Record_Entry_No
        $recdate = DB::table('recordms')
        ->select('Rec_date')
        ->where('t_bill_id', $tbillid)
        ->where('Record_Entry_No', $Rec_E_No)
        ->value('Rec_date');

        // Format the date for display
        $RecDate = date("d/m/Y", strtotime($recdate));
        // dd($RecDate);


     // Loop through each bill item

        foreach($billitemdata as $itemdata)
            {
                $bitemId=$itemdata->b_item_id;

                  // Retrieve measurement data for the current bill item and record date from 'embs' and 'stlmeas' tables
                $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $recdate)->get();
                $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $recdate)->get();
                //meas data check
             // Count the number of entries with 'ee_check' equals 1 in 'embs' and 'stlmeas' tables
            $EmbdyeChkCount = DB::table('embs')->where('t_bill_id', $tbillid)->where('measurment_dt', $recdate) ->where('ee_check',  1)->count();
            $Stldyechkcount = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdate)->where('ee_check',"=",1)->count();
            // dd($Stldyechkcount);
            $Count_Chked_Emb_Stl= $EmbdyeChkCount+$Stldyechkcount;

                $stlmeascount=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdate)->count();
                    // Retrieve 'embs' entries for the current bill item and record date
                $embcount = DB::table('embs')->where('t_bill_id', $tbillid)->where('measurment_dt', $recdate)->get();

                $embCountWithoutTMT = 0;

                 // Count 'embs' entries without 'TMT' in 'parti' field
                if ($embcount->isNotEmpty()) {
                    foreach ($embcount as $tmtdata) {
                        if (strpos($tmtdata->parti, 'TMT') !== 0) {
                            $embCountWithoutTMT++;
                        }
                    }
                }

            //dd($embCountWithoutTMT);
              // Calculate total measurement data count for the current bill item and record date
                $measdatacount=$embCountWithoutTMT+$stlmeascount;
                //dd($measdatacount);

                // Check if 'embs' or 'stlmeas' data is present
            if (!$measnormaldata->isEmpty() || !$meassteeldata->isEmpty()) {

                // Generate HTML table for the current bill item
                //$html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><tr><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 90%; text-align: justify;"> ' . $itemdata->exs_nm . '</th></tr></thead></table>';
                    $html .= '<table class="" style="border-collapse: collapse; width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="padding: 8px; background-color: lightpink; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th>
                                        <th style="padding: 8px; background-color: lightpink; width: 90%; text-align: justify;">' . $itemdata->exs_nm . '</th>
                                    </tr>
                                </thead>
                            </table>';

                             // Check specific item IDs for additional processing
                $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
                //dd($itemid);
                if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
                {
                    $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->get();

                    // Retrieve bill_rcc_mbr data
                    $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                    // Define columns for 'ldiam' measurements
                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
                    'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

                 // Iterate through 'stldata' to modify 'ldiam' columns if necessary
                    foreach ($stldata as &$data) {
                        if (is_object($data)) {
                            foreach ($ldiamColumns as $ldiamColumn) {
                                if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                                $temp = $data->$ldiamColumn;
                                $data->$ldiamColumn = $data->bar_length;
                                $data->bar_length = $temp;
                                break;
                                }
                            }
                        }
                    }
                   // Calculate sums of 'ldiam' columns
                    $sums = array_fill_keys($ldiamColumns, 0);

                    foreach ($stldata as $row) {
                        foreach ($ldiamColumns as $ldiamColumn) {
                            $sums[$ldiamColumn] += $row->$ldiamColumn;
                        }
                    }//dd($stldata);

                     // Retrieve 'bill_rcc_mbr' data based on 'rc_mbr_id' existence in 'stlmeas'
                    $bill_member = DB::table('bill_rcc_mbr')
                        ->whereExists(function ($query) use ($bitemId) {
                        $query->select(DB::raw(1))
                            ->from('stlmeas')
                            ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                            ->where('bill_rcc_mbr.b_item_id', $bitemId);
                        })
                        ->get();

                   // Retrieve 'rc_mbr_id' values for the current 'b_item_id'
                    $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

                    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 3%;  min-width: 3%;">Sr No</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 25%; min-width: 25%;">Bar Particulars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 6%; min-width: 6%;">No of Bars</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">Length of Bars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">6mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">8mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">10mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">12mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">16mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">20mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">25mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">28mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 3%; min-width: 3%;">Check</th></thead>';

                     // Iterate over 'stldata' to generate the details table for each bar
                    foreach ($bill_member as $index => $member) {
                            // Dump and die debugging statement for $member, commented out
                        $rcmbrid=$member->rc_mbr_id;

                           // Fetching 'stlmeas' data for the current member based on 'rc_mbr_id' and 'recdate'
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $recdate)->get();
                                //dd($memberdata);

                         // Check if 'memberdata' is not empty
                        if ( !$memberdata->isEmpty()) {
                          // Append a table header with member details
                            $html .= '<table style="border-collapse: collapse; width: 100%;"><thead style="background-color: #E1EBEE;"><th colspan="1" style="border: 1px solid black; padding: 8px; background-color: #E1EBEE;">Sr No :' . $member->member_sr_no . '</th>
                            <th colspan="2" style="border: 1px solid black; padding: 8px;">RCC Member :' . $member->rcc_member . '</th>
                            <th colspan="2" style="border: 1px solid black; padding: 8px;">Member Particular :' . $member->member_particulars . '</th>
                            <th colspan="2" style="border: 1px solid black; padding: 8px;">No Of Members :' . $member->no_of_members . '</th></thead></table>';

                            foreach ($stldata as $bar) {
                                if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                                $formattedDateMeas = date('d/m/Y', strtotime($bar->date_meas));
                                $dye_chk_dt = date('d/m/Y', strtotime($bar->dyE_chk_dt));
                                // dd($dye_chk_dt);


                                $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><td style="border: 1px solid black; padding: 8px; width: 3%;  min-width: 3%;  text-align:right;">'. $bar->bar_sr_no .'</td>
                                        <td style="border: 1px solid black; padding: 8px; width: 25%; min-width: 25%; ">'. $bar->bar_particulars.'</td><td style="border: 1px solid black; padding: 8px; width: 6%; min-width: 6%; text-align:right;" >'. $bar->no_of_bars .'</td>
                                        <td style="border: 1px solid black; padding: 8px; width: 7%; min-width: 7%;  text-align:right;">'. $bar->bar_length .'</td> <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%;  text-align:right;">'. $bar->ldiam6 .'</td>
                                        <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%;  text-align:right;">'. $bar->ldiam8 .'</td> <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%;  text-align:right;">'. $bar->ldiam10 .'</td>
                                        <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%;  text-align:right;">'. $bar->ldiam12 .'</td><td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%;  text-align:right;">'. $bar->ldiam16 .'</td>
                                        <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%;  text-align:right;">'. $bar->ldiam20 .'</td> <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%;  text-align:right;">'. $bar->ldiam25 .'</td>
                                        <td style="border: 1px solid black; padding: 8px; width: 5%; min-width: 5%;  text-align:right;">'. $bar->ldiam28 .'</td>';
                                // Check if 'ee_check' is 1 and append a checked checkbox, else append an unchecked checkbox
                                    if( $bar->ee_check==1){
                                        $html .= '<td style="border: 1px solid black; padding: 30px; width: 3%; min-width: 3%;">
                                        <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Steel[' . $bar->steelid . ']."  onclick="CustomeCheckBoxSFun('.$measdatacount.');" checked>
                                        </td>';
                                    }
                                    else{
                                        $html .= '<td style="border: 1px solid black; padding: 30px; width: 3%; min-width: 3%;">
                                        <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Steel[' . $bar->steelid . ']."  onclick="CustomeCheckBoxSFun('.$measdatacount.');">
                                        </td>';
                                    }
                                   // dd($BillDt);
                                 

                                }
                            }
                        }
                    }
                }
                else{
                    // Fetch normal data if 'meassteeldata' is empty
                    $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();

                    if($meassteeldata->isEmpty()){
                        // Append the table header for normal data
                        $html .= '<table class="table-striped" style="border-right: 1px solid black; width:100%;"><thead><th style="border: 1px solid black; width: 5%; border-color: black;">Sr. No</th>
                        <th style="border: 1px solid black; width: 30%; border-color: black;">Particulars</th><th style="border: 1px solid black; width: 7%; border-color: black;">Number</th><th style="border: 1px solid black; width: 7%; border-color: black;">Length</th>
                        <th style="border: 1px solid black; width: 7%; border-color: black;">Breadth</th><th style="border: 1px solid black; width: 7%; border-color: black;">Height</th><th style="border: 1px solid black; width: 7%; border-color: black;">Quantity</th><th style="border: 1px solid black; width: 4%; border-color: black;">Check</th><th style="border: 1px solid black; width: 10%; border-color: black;">Checked Quantity</th>
                        </thead><tbody>';
                    }
                      // Iterate over 'normaldata' to generate the rows for each item
                    foreach($normaldata as $nordata)
                    {
                        $dye_chk_date = date('d/m/Y', strtotime($nordata->dyE_chk_dt));
                        $measidstring = "'$nordata->meas_id'";
                    $formula= $nordata->formula;
                      // Append a table row with item details
                    $html .= '<tr><td style="border: 1px solid black; padding: 8px; width: 5%;">' . $nordata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 30%; word-wrap: break-word; max-width: 30px;">' . $nordata->parti . '</td>';
                        if($formula)
                        {
                            $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 28%; text-align:right;">' . $nordata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 7%;  text-align:right;">' . $nordata->qty . '</td>';
                        }
                        else
                        {
                            $html .= '<td style="border: 1px solid black; padding: 8px; width: 7%;  text-align:right;">' . $nordata->number . '</td><td style="border: 1px solid black; padding: 8px; width: 7%;  text-align:right;">' . $nordata->length . '</td><td style="border: 1px solid black; padding: 8px; width: 7%;  text-align:right;">' . $nordata->breadth . '</td><td style="border: 1px solid black; padding: 8px; width: 7%;  text-align:right;">' . $nordata->height . '</td><td style="border: 1px solid black; padding: 8px; width: 7%;  text-align:right;">' . $nordata->qty . '</td>';
                        }

                        // $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 7%;">' . $dye_chk_date . '</td>';
                         // Check if 'ee_check' is 1 and append a checked checkbox, else append an unchecked checkbox
                        if($nordata->ee_check==1)
                        {
                            $html .= '<td style="border: 1px solid black; padding: 30px; width: 4%;"><input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Item[' .$nordata->meas_id. ']" onclick="CustomeCheckBoxSFun(' . $measdatacount . ');CheckIndicatorinput('.$measidstring.');" checked>
                                    </td>';
                        }
                        else{
                            $html .= '<td style="border: 1px solid black; padding: 30px; width: 4%;"><input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Item[' .$nordata->meas_id. ']" onclick="CustomeCheckBoxSFun(' . $measdatacount . ');CheckIndicatorinput('.$measidstring.');">';
                        }
                        // dd($dye_chk_date,$BillDt);
                         if($SelectDtAllS !=''){
                            //$html .= '<td style="width: 10%; border-color: black;"><input type="date" class="form-control customDtEmb" value="'. $SelectDtAllS.'"   name="customDateInputS['. $nordata->meas_id.']" onchange="CustomeDtFunN('. $nordata->meas_id.');" min='.$nordata->dyE_chk_dt.' max='.$BillDt.'></td>';
                        }
                        else{
                            //$html .= '<td style="width: 10%; border-color: black;"><input type="hidden" class="form-control customDtEmb"  value="' .$nordata->ee_chk_dt . '" name="customDateInputN['. $nordata->meas_id.']" onchange="CustomeDtFunN('. $nordata->meas_id.' );" min='.$nordata->dyE_chk_dt.' max='.$BillDt.'></td>';
                       // Append the checked quantity input field
                         $html .= '<td style="border: 1px solid black; width: 10%; border-color: black;">
                            <input type="number" class="form-control customDtEmb" value="' . $nordata->ee_chk_qty . '" 
                                   name="eeqty['.$nordata->meas_id.']" 
                                   id="eeqty_' . $nordata->meas_id . '" 
                                   oninput="CheckIndicatorinput('.$measidstring.'); limitMaxValue(this ,' . $nordata->qty . ');" 
                                   max="' . $nordata->qty . '"  step="any">
                        
                          </td>';
                        }
                    $html .='</tr>';
                }
                $html .='</tbody></table>';
            }
            }
            }

            // return response()->json(['countcombinarray'=>$countcombinarray,'BillDt'=>$BillDt,'combinarray'=> $combinarray,'html'=>$html,'RecDate'=>$RecDate,'Count_Chked_Emb_Stl'=>$Count_Chked_Emb_Stl]);
            return response()->json(['measdatacount'=>$measdatacount,'html'=>$html,'RecDate'=>$RecDate,'Count_Chked_Emb_Stl'=>$Count_Chked_Emb_Stl]);

    }

  //Itemwise  executive engineer checking 
    public function ItemwiseExecutiveCheckFun(Request $request)
    {
        // Retrieve and store input values from the request
        $tbillid = $request->input('tbillid_value');
        // dd($tbillid);
        $recno=$request->input('recordEntryNo');
        //dd($recno);
        $itemid =$request->input('itemid_value');
        //dd($itemid);
        $WorkIdv =$request->input('WorkId_value');

        $itemno=$request->input('combined_value');
        //dd($itemno);

         // Fetch item details from the 'bil_item' table based on the combined item number and bill ID
        $titemno = DB::table('bil_item')
        ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc','exec_qty','item_unit','cur_amt','bill_rt','ratecode','cur_qty')
        ->where('t_bill_id', '=', $tbillid)
        ->where('t_item_no','=',$itemno)
        ->get();
        //dd($titemno);

         // Separate the numeric part and the last character from the combined item number
        $itemno=$request->input('combined_value');
        // Separate the numeric part and the last character
        $itemNo = preg_replace('/[^0-9]/', '', $itemno); // Extract all digits
        $lastCharacter = substr($itemno, -1);
        $subno=0;
        if (ctype_alpha($lastCharacter)) {
            // $lastCharacter contains a character (letter)
            $subno=$lastCharacter;
            // dd($subno);
        }
        $bitemid=0;
        $html='';
        if($subno)
        {
             // Fetch 'b_item_id' if sub-number is present
            $bitemid=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('t_item_no' , $itemNo)
            ->where('sub_no', $subno)->value('b_item_id');
            //dd($bitemid);
        }
        else
        {
             // Fetch 'b_item_id' without sub-number
            $bitemid=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('t_item_no' , $itemNo)
            ->value('b_item_id');
        }

          // Fetch 'item_id' using 'b_item_id'
        $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('item_id');
        // dd($itemid);
        // Check if the last 6 characters of 'item_id' match specific values
            if (
            in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016",
                                        "002017", "002023", "002024", "003351", "003352", "003878"])
                                        )
            {

            // Fetch 'stlmeas' data based on 't_bill_id' and 'b_item_id'
            $stldata = DB::table('stlmeas')
            ->select('stlmeas.*')
            ->join('bill_rcc_mbr', 'stlmeas.rc_mbr_id', '=', 'bill_rcc_mbr.rc_mbr_id')
            ->where('bill_rcc_mbr.t_bill_id', $tbillid)
            ->where('bill_rcc_mbr.b_item_id', $bitemid)
            ->get();

             // Fetch 'bill_rcc_mbr' data
                $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                // dd($stldata , $bill_rc_data);
                // Define the columns for diameters
                $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
                'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


                 // Swap 'bar_length' with corresponding 'ldiam' column if they are not equal
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

                 // Initialize sums for each 'ldiam' column
                $sums = array_fill_keys($ldiamColumns, 0);

                foreach ($stldata as $row) {
                     foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                     }
                }//dd($stldata);

                 // Fetch 'bill_member' data where 'stlmeas' exists for the given 'b_item_id'
                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemid) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemid);
                })
                ->get();

                // Get 'rc_mbr_id's for the given 'b_item_id'
            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemid)->pluck('rc_mbr_id')->toArray();

        foreach ($bill_member as $index => $member) {
             //dd($member);

                // Fetch data related to the member from the 'stlmeas' table using a join with 'bill_rcc_mbr' table
                $memberdata = DB::table('stlmeas')
                ->join('bill_rcc_mbr', 'bill_rcc_mbr.rc_mbr_id', '=', 'stlmeas.rc_mbr_id')
                ->where('bill_rcc_mbr.t_bill_id', $tbillid)
                // ->where('t_item_no', '=', $itemno)
                ->get();

             // If there is member data, generate HTML table
                if ( !$memberdata->isEmpty()) {
                // Create table headers with member details
                $html .= '<table style="border-collapse: collapse; width: 100%;"><thead>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px; background-color:lightblue;">Sr No:' . $member->member_sr_no . '</th>';
                $html .= '<th colspan="6" style="border: 1px solid black; padding: 8px; background-color: lightblue;">RCC Member :' . $member->rcc_member . '</th>';
                $html .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: lightblue;">Member Particular :' . $member->member_particulars . '</th>';
                $html .= '<th colspan="3" style="border: 1px solid black; padding: 8px; background-color: lightblue;">No Of Members :' . $member->no_of_members . '</th>';
                $html .= '</thead>';

                $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 15%; min-width: 15%;">Bar Particulars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 7%;">6mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">8mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 7%;">10mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">12mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 7%;">16mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">20mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">25mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">28mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Record Entry No</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 3%; min-width: 3%;">Check</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%; min-width: 10%;">check Date</th></thead>';
                
                    //stell data for checking 
                    foreach ($stldata as $bar) {
                        if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                        //    dd($bar);

                        //check date of ee 
                        $formattedDateMeas = date('d/m/Y', strtotime($bar->date_meas));
                        $ee_chk_date = date('d/m/Y', strtotime($bar->ee_chk_dt));


                        $Record_Entry_No = DB::table('recordms')

                        ->where('t_bill_id', $tbillid)
                        ->where('Rec_date', $bar->date_meas)
                        ->value('Record_Entry_No');

                        // $html .= '<tr>';
                        // $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
                        $html .= '<tbody>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 5%;  min-width: 3%; text-align:left;" >'. $bar->bar_sr_no .'</td>';
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 15%; min-width: 15%; text-align:left;">'. $bar->bar_particulars.'</td>';
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
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%; text-align:right;">'. $Record_Entry_No .'</td>';

                        if($bar->ee_check==1){
                            $html .= '<td style="width: 3%; padding-left: 50px; border: 1px solid black;"><input id="checkbox" class="form-check-input form-check" type="checkbox" checked disabled ></td>';
                        }
                        else{
                            $html .= '<td style="width: 3%; padding-left: 50px; border: 1px solid black;"><input id="checkbox" class="form-check-input form-check" type="checkbox"  disabled ></td>';
                        }
                        $html .='<td style="border: 1px solid black; padding: 8px; width: 10%; min-width: 10%;">'.$ee_chk_date.'</td>';

                            }


                        }
                    }
            }
            $html .= '
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
            <th colspan="3" style="border: 1px solid black; padding: 8px; background-color: white; text-align:right; width: 10%; min-width: 10%;"></th>
        </tr>';
        $html .='</tbody>';
                            $html .='</table>';


                            //summary for the emb all data
                $embssumarry=DB::table('embs')->where('b_item_id' , $bitemid)->where('t_bill_id' , $tbillid)->get();

                //all emb data run for the summary
                foreach($embssumarry as $nordata)
                    {
                        // dd($nordata);
                        $formula= $nordata->formula;
                        $html .= '<table style="border-collapse: collapse; width: 100%;"><tbody><td style="border: 1px solid black; padding: 8px; width: 5%;">' . $nordata->sr_no . '</td><td style="border: 1px solid black; padding: 8px; width: 39%; word-wrap: break-word; max-width: 200px; text-align:left;">' . $nordata->parti . '</td>';
                        if($formula)
                        {
                            $html .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width: 15%; text-align:right;">' . $nordata->formula . '</td><td style="border: 1px solid black; padding: 8px; width: 6%; text-align:right;">' . $nordata->qty . '</td>';
                        }
                        else
                        {
                            $html .= '<td style="border: 1px solid black; padding: 8px; width: 12%; text-align:right;">' . $nordata->number . '</td><td style="border: 1px solid black; padding: 8px; width: 11%; text:align-right;">' . $nordata->length . '</td><td style="border: 1px solid black; padding: 8px; text-align:right; width: 11%;">' . $nordata->breadth . '</td><td style="border: 1px solid black; padding: 8px; width: 11%; text-align:right;">' . $nordata->height . '</td><td style="border: 1px solid black; padding: 8px; width: 15%; text-align:right;">' . $nordata->qty . '</td>';
                        }
                        $html .='</tbody></table>';
                    }
        }

            else
            {
                //Measurement data for given bitemid
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

            $titemno = DB::table('bil_item')
            ->where('t_item_no','=',$itemno)
            ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc','exec_qty','item_unit','bill_rt','ratecode','cur_qty','cur_amt','cur_amt')
            ->where('t_bill_id', '=', $tbillid)
            ->first();
        //   dd($titemno);

            $TndData = DB::table('tnditems')
            ->select('tnd_qty','exs_nm','item_unit')
            ->where('t_item_no', '=', $itemno)
            ->first();

            $iteminfo = DB::table('bil_item')
            ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'exs_nm','exec_qty','item_unit','cur_amt','bill_rt','ratecode','cur_qty')
            ->where('t_bill_id', '=', $tbillid)
            ->where('t_item_no','=',$itemno)
            ->get();

            //dd($html);
            if($html !==''){
                return response()->json(['iteminfo'=>$iteminfo,'html'=>$html,'TndData'=>$TndData,'titemno'=>$titemno]);

            }
            else{
                return response()->json(['Item1Data'=>$Item1Data,'iteminfo'=>$iteminfo,'titemno'=>$titemno,'TndData'=>$TndData]);
            }
    }

   //* SaveBtnExecutive function handles saving data based on the request inputs.
    public function SaveBtnExecutive(Request $request)
    {
        try{
          // Uncomment the line below to dump and debug the entire request input
        // dd($request);

        $billDate=$request->input('billDate');

        $WorkId = $request->input('workid');
        // dd($WorkId);

        $tbillid=$request->input('tbillid');
        // dd($tbillid);

        $titemnovalues=$request->input('titemnovalues');
        //dd($titemnovalues);

        $dateInput=$request->input('dateInput');
        // dd($dateInput);

        $recnovalues=$request->input('recnovalues');
        // dd($recnovalues);

        $je_check=$request->input('je_check');
        //dd($je_check);

        $steelid=$request->input('steelid');
        //dd($steelid);
        $fund_Hd1 = DB::table('workmasters')
        ->select('fundhdms.Fund_HD_M')
        ->join('fundhdms', function ($join) use ($WorkId) {
            $join->on(DB::raw("LEFT(workmasters.F_H_Code, 4)"), '=', DB::raw("LEFT(fundhdms.F_H_CODE, 4)"))
                ->where('workmasters.Work_Id', '=', $WorkId);
        })
        ->first();
        $customDateInputS=$request->input('customDateInputS');
       //dd($customDateInputS);

        // Access the session variable set in the previous function
        $storedBillDate = $request->session()->get('billDate');
        //dd($storedBillDate);

         // Calling a common function to fetch common header data
        $commonheader=$this->commongotoembcontroller($WorkId , $steelid,$storedBillDate,$tbillid,$recnovalues);
        //dd($commonheader);

        // Fetching work details based on WorkId
        $workDetails1 = DB::table('workmasters')

        ->select('Work_Nm', 'Sub_Div', 'Agency_Nm', 'Work_Id', 'Wo_Dt','Period','WO_No','Stip_Comp_Dt')
        ->where('Work_Id', '=', $WorkId)
        ->first();

        //$je_check_Steel_Headingkey=$request->input('je_check_Steel_Heading');


        $eeqty=$request->input('eeqty');
        // Fetching 'countcombinarray' input from request
        $countcombinarray=$request->input('countcombinarray');
            //dd($countcombinarray);

        // Fetching 'btnsave' and 'BtnRevert' inputs
        $btnsave=$request->input('btnsave');

        //$btnall=$request->input('btnall');

        $BtnRevert=$request->input('BtnRevert');
            //dd($btnsave , $btnall);

        // Fetching 'titemno' data based on tbillid
        $titemno = DB::table('bil_item')
        ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc', 'exec_qty', 'item_unit')
        ->where('t_bill_id', '=', $tbillid)
        ->get();

         // Fetching 'Record_Entry_No' from recordms based on tbillid
        $Recordeno = DB::table('recordms')
        ->select('Record_Entry_No')
        ->where('t_bill_id', '=', $tbillid)
        ->get();

         // Fetching 'divName1' based on WorkId
        $divName1 = DB::table('workmasters')
        ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
        ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
        ->where('workmasters.Work_Id', '=', $WorkId)
        ->value('divisions.div');

        // Save button Code..............
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

             // Fetching 'meas_id' from embs based on tbillid and recdt
            $Emb_Measid = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $recdt)->pluck('meas_id')->toArray();
            //dd($Emb_Measid);
            // Fetching 'steelid' from stlmeas based on tbillid and recdt
            $Stlmeas_Stlid = DB::table('stlmeas')
            ->where('t_bill_id', $tbillid)
            ->where('date_meas', $recdt)
            ->pluck('steelid')
            ->toArray();
            //dd($Stlmeas_Stlid);

             // Checking and updating 'ee_check' in embs based on je_check_Itemkey1
            $je_check_Itemkey1=$request->input('je_check_Item');
            if($je_check_Itemkey1 === null  ){
                // dd($je_check_Steelkey1);
                foreach($Emb_Measid as $jecheck){
                    //dd($jecheck);
                    DB::table('embs')
                    ->where('meas_id', $jecheck)
                    ->update(['ee_check' => 0]);
                // dd("Updated normal to 0");
                }//dd("Updated Steel to 0");
            }
            else{
            //dd($je_check_Itemkey1);
            $je_check_Itemkey=array_keys($je_check_Itemkey1);
            //dd($je_check_Itemkey);
            $unchked_embs = array_diff($Emb_Measid , $je_check_Itemkey);
            // dd($unchked_embs);
                foreach($unchked_embs as $jecheck){
                    //dd($jecheck);
                    DB::table('embs')
                    ->where('meas_id', $jecheck)
                    ->update(['ee_check' => 0]);
                // dd("Updated normal to 0");
                }//dd("Updated Steel to 0");
            }

              // Fetching data from embs and stlmeas based on tbillid and recdt
            $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $recdt)->get();
            //dd($NormalDb);

            $StillDb = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdt)->get();
            //dd($StillDb);

              // Concatenating data from NormalDb and StillDb
            $combinarray = $NormalDb->concat($StillDb);
            //dd($combinarray);

            //Count of combine data...
            $countcombinarray=count($combinarray);
            //dd($countcombinarray);


            // Counting 'ee_check' in stlmeas based on tbillid and recdt
            $Stldyechkcount1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdt)->where('ee_check',"=",1)->get();
            $Stldyechkcount=count($Stldyechkcount1);
            //dd($Stldyechkcount);

            //measurement count of ee checked
            $EmbdyeChkCount = DB::table('embs')
            ->where('t_bill_id', $tbillid)
            ->where('measurment_dt', $recdt)
            ->where('ee_check', "=", 1)
            ->count();
            //  dd($EmbdyeChkCount);

            //add both counts normal measurement and steel measurement
            $Count_Chked_Emb_Stl= $EmbdyeChkCount + $Stldyechkcount;
           // dd($Count_Chked_Emb_Stl , $countcombinarray);

          //if matched the counts all measurement and user checked array
            if ($Count_Chked_Emb_Stl === $countcombinarray) {
            // dd($jecheck);
            //update if matched data ee check
                DB::table('recordms')
                ->where('Record_Entry_Id', $recenid)
                ->update(['ee_check' => 1]);
                // dd("Updated normal to 0");
            }
            else{
             //update if matched data ee check
                DB::table('recordms')
                ->where('Record_Entry_Id', $recenid)
                ->update(['ee_check' => 0]);
            // dd("Updated normal to 0");
            }
            //Saving Checked CheckBoxes to table....
            $recenid= $recenid ?: [];
            if($recenid){
                $je_check_Itemkey=$request->input('je_check_Item');
                //dd($je_check_Itemkey);
                //Udating EMB Data Checkbox...
                if($je_check_Itemkey){

                    //dd($je_check_Item);
                    $je_check_Item=array_keys($je_check_Itemkey);
                    // dd($je_check_Item);
                    foreach($je_check_Item as $jecheck){
                        DB::table('embs')
                        ->where('meas_id', $jecheck)
                        ->update(['ee_check' => 1]);
                    }
                    // dd("Normal Data Checkbox is Update");
                }
                    // dd("CheckBox Saved successfully....");


                    if(!empty($eeqty))
                    {
                  foreach ($eeqty as $measId => $qty)     
                  {  
                      
                      if (is_array($je_check_Itemkey) && array_key_exists($measId, $je_check_Itemkey1))   
                      {
                              DB::table('embs')  
                            ->where('meas_id', $measId)    
                            ->update(['ee_chk_qty' => $qty]);     
                      }    
                  }       
               
                    }         

// Fetch PartA_Amt from bills table based on tbillid
$PartA_Amt= DB::table('bills')
->where('t_bill_id', $tbillid)
->value('c_part_a_amt');
// dd($PartA_Amt);

// Fetch PartB_Amt from bills table based on tbillid
$PartB_Amt= DB::table('bills')
->where('t_bill_id', $tbillid)
->value('c_part_b_amt');
// dd($PartB_Amt);
// Calculate the total amount by adding PartA_Amt and PartB_Amt
$b_item_amt=$PartA_Amt +  $PartB_Amt;


// Initialize variables for calculating checked percentage$totalMeasAmt = [];
    $PreviSelectedCheckboxAmount = 0; // Initialize the variable to store the total amount
    $Checked_Percentage = 0;

// Query embs table to fetch items with ee_check = 1 and notforpayment = 0 for tbillid
    $bitemidDBCalculation = DB::table('embs')
    ->where('t_bill_id', $tbillid)
    ->where('ee_check',1)
    ->where('notforpayment',0)
    ->select('b_item_id','meas_id','ee_chk_qty')
    ->get();
   // dd($bitemidDBCalculation);
   // Loop through fetched data to calculate previous selected checkbox amount
foreach($bitemidDBCalculation as $measdata)
{
     //dd($measdata);
    $ee_chk_tbl = DB::table('embs')
        ->where('meas_id', $measdata->meas_id)
        ->value('ee_check');

     
    // Fetch b_item_id for meas_id and tbillid from embs table
    $bitemid = DB::table('embs')
        ->where('t_bill_id', $tbillid)
        ->where('meas_id', $measdata->meas_id)
        ->value('b_item_id');
    // dd($bitemid);

// Fetch bill_rt for tbillid and b_item_id from bil_item table
        $bill_rt = DB::table('bil_item')
            ->where('t_bill_id', $tbillid)
            ->where('b_item_id', $bitemid)
            ->value('bill_rt');
    
     // Calculate measurement amount by multiplying bill_rt with ee_chk_qty
        $meas_amt = $bill_rt * $measdata->ee_chk_qty;
        $PreviSelectedCheckboxAmount += $meas_amt;
   
     //dd($PreviSelectedCheckboxAmount);
     //dd($notforpayment);
    }
    // Assign PreviSelectedCheckboxAmount to checked_mead_amt
    $checked_mead_amt = $PreviSelectedCheckboxAmount; // Assign $amount to 'checked_mead_amt'

// Calculate Checked_Percentage based on checked_mead_amt and b_item_amt
$Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
// Format the result to have only three digits after the decimal point
$Checked_Percentage = number_format($Checked_Percentage1, 2);

// Update EEChk_Amt and EEChk_percentage in bills table for tbillid
    DB::table('bills')
        ->where('t_bill_Id', $tbillid)
        ->update(['EEChk_Amt' => $checked_mead_amt , 'EEChk_percentage' => $Checked_Percentage]);

//dd($eeqty);
// Check if eeqty is not empty
         if(!empty($eeqty))
            {
                
            $EEqty=array_keys($eeqty);
            }          
            
            }

            //Saving Steel Date to database =========================================================================================
            if($customDateInputS){
                foreach ($customDateInputS as $key => $value) {
                    //dd($key, $value);
                    if($value){
                        DB::table('stlmeas')
                        ->where('steelid', $key)
                        ->update(['ee_chk_dt' => $value]);
                    }
                }
            }
            //Saving Embs Date to database =========================================================================================
            $customDateInputN=$request->input('customDateInputN');
            $recno = DB::table('recordms')
            ->where('Record_Entry_Id', $recenid)
            ->get('Record_Entry_No');
            // dd($recno);

            return $commonheader;
        }

        // Revert button Code.................
        elseif($BtnRevert==='revert')
        {
          $revert= DB::table('bills')
                 ->where('t_bill_id', $tbillid)
                 ->update(['mb_status' => 2,'mbstatus_so'=>0 ,'EE_revert' => 1]
                );
                
                DB::table('revort_reason')->updateOrInsert(
                    // Condition to check if a record exists for the t_bill_id
                    ['t_bill_id' => $tbillid],
                    // Data to insert or update
                    [
                        'ee_res' => $request->reason, // The reason from the form
                        'created_at' => now(), // Insert current timestamp if new
                        'updated_at' => now()   // Update the timestamp if record exists
                    ]
                );

                
                
                //revert mail notification send

           if($revert)
            {
    
            $revertstatus=3;
    
             //Work information
             $workdata=DB::table('workmasters')->where('Work_Id', $WorkId)->first();
    
             // Fetch the Agency  details related to the given work_id
             $from = DB::table('eemasters')->where('eeid', $workdata->EE_id)->first();
    //dd($from);
            
             // Fetch the JE details related to the given work_id
             $jeDetails = DB::table('jemasters')->where('jeid', $workdata->jeid)->first();
    
            
             if ($jeDetails) {
     
                 $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
                 //dd($jeDetails);
                 // Send the notification email to the JE
                 Mail::to($jeDetails->email)->queue(new RevertMBNotification($revertstatus, $workdata , $tbilldata , $from , $jeDetails));
             } else {
                 // Handle the case where no JE details are found
                 // You can log the error or throw an exception
             }
    
          }


                
                
                
                 //dd("Submteed");
              $workid=$WorkId;
              return redirect()->route('billlist', ['workid' => $workid]);
        }
    }
        catch (Exception $e)
        {
            // Log the exception or handle it as needed
            Log::error('Error in SaveBtnExecutive: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }

    }


    //exexcutive engineer all check quantity function (Submit All)
    public function SubmitAllEE(Request $request)
    {
        try
        {
       // Retrieve data from the request
        $tbillid= $request->tbillid;
        $recnovalues=$request->recordentryno;
        $WorkId=$request->workid;
        $Checkboxdata=$request->checkboxdata;
        $Percentage=$request->percentage;
        $Amount=$request->Amount;
        // Retrieve Record_Entry_Id using tbillid and Record_Entry_No
         $recenid = DB::table('recordms')
         ->where('t_bill_id', $tbillid)
         ->where('Record_Entry_No', $recnovalues)
         ->value('Record_Entry_Id');
         // dd($recenid);

     // Retrieve Rec_date using tbillid and Record_Entry_No
         $recdt= DB::table('recordms')
         ->where('t_bill_id', $tbillid)
         ->where('Record_Entry_No', $recnovalues)
         ->value('Rec_date');
        //  dd($recdt);

        // Retrieve meas_id from embs table where t_bill_id matches tbillid
         $Emb_Measid = DB::table('embs')->where('t_bill_id' , $tbillid)->pluck('meas_id')->toArray();
         //dd($Emb_Measid);
            // Retrieve steelid from stlmeas table where t_bill_id matches tbillid
         $Stlmeas_Stlid = DB::table('stlmeas')
         ->where('t_bill_id', $tbillid)
         ->pluck('steelid')
         ->toArray();
         //dd($Stlmeas_Stlid);

          // Retrieve all records from embs table where t_bill_id matches tbillid
             $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->get();
             //dd($NormalDb);

              // Count the number of records in stlmeas table where t_bill_id matches tbillid
             $StillDb1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->get();
             $StillDbcount=count($StillDb1);
         //dd($StillDb);
    
             //dd("Less than 5");
             $workid=$WorkId;

         // dd($workid,$percentageTextBox,$tbillid);

               // Prepare script for displaying confirmation dialog
             $script = "
             <script>
                 var workid = " . json_encode($workid) . ";
                 var tbillid = " . json_encode($tbillid) . ";
                 console.log(workid,tbillid);
                 Swal.fire({
                     icon: 'warning',
                     title: 'Warning...',
                     text: 'You have checked insufficient CheckBox and Measurement Check Dates. Still, do you want to submit?',
                     showCancelButton: true,
                     confirmButtonText: 'Yes',
                     cancelButtonText: 'No'
                 }).then(function(result) {
                     if (result.isConfirmed) {
                         window.location.href = '" . url('yesSubmitview/' . $workid . '/' . $tbillid) . "';
                     } else {
                         location.reload();
                     }
                 });
             </script>
         ";

 
        // Update ee_chk_qty and ee_check in embs table based on Checkboxdata received from request
       if(!empty($Checkboxdata))
         {


                     foreach($Checkboxdata as $checkdata)
                     {
                         //dd($checkdata);
                         DB::table('embs')->where('meas_id' , $checkdata['id'])->update([
                           'ee_chk_qty' => $checkdata['eeqty'],
                           'ee_check' => 1,
                         ]);
                     }
         }
          else{
            // If Checkboxdata is empty, update ee_check to 0 for all records where t_bill_id matches tbillid
            DB::table('embs')->where('t_bill_id' , $tbillid)->update([
                'ee_check' => 0,
              ]);
        }
         // Remove percentage sign from Percentage and comma from Amount
         $Percentage = str_replace('%', '', $Percentage);
         $Amount = str_replace(',' , '' , $Amount);
    
    // Now you can use $percentage as a clean number
    // For example, you can convert it to a float if needed
     // Convert Percentage to float if needed
    $Percentage = (float)$Percentage;

         //Update the percentage and amount in bills table
        DB::table('bills')->where('t_bill_Id' , $tbillid)->update([
            'EEChk_Amt' => $Amount,
            'EEChk_percentage' => $Percentage,
        ]);
// exit;
         // return view('listemb');

$tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
         
        //  return redirect()->route('emb', ['tbillid' => $tbillid])
        //  ->with('alert', 'Your custom warning message.');

        
         // Return JSON response containing the generated script
        return response()->json(['script' => $script]);
     
    }
    catch (\Exception $e) 
    {
        // Log the exception or handle it as needed
        Log::error('Error in SubmitAllEE: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred ' . $e->getMessage()], 500);
    }
}




    
// Function to calculate and return percentage and amount based on selected checkboxes
    public function PercentageLoad(Request $request)
    {
        try
        {
        //dd($request);
        $WorkId = $request->input('workid');
        // dd($WorkId);

        $tbillid=$request->input('tbillid');
        //  dd($WorkId,$tbillid);
        $dateid=$request->input('dateid');
        //  dd($WorkId,$tbillid,$dateid);

        // Retrieve part A amount from bills table where t_bill_id matches tbillid
        $PartA_Amt= DB::table('bills')
        ->where('t_bill_id', $tbillid)
        ->value('c_part_a_amt');
        // dd($PartA_Amt);

        // Retrieve part B amount from bills table where t_bill_id matches tbillid
        $PartB_Amt= DB::table('bills')
        ->where('t_bill_id', $tbillid)
        ->value('c_part_b_amt');
        // dd($PartB_Amt);


   // Calculate total bill item amount by summing part A and part B amounts
        $b_item_amt=$PartA_Amt +  $PartB_Amt;
        //   dd($b_item_amt);

   // Calculate 5% of the total bill item amount
        $fivePercent = $b_item_amt * 0.05;

         // Retrieve b_item_ids from embs table where t_bill_id matches tbillid, ee_check is 1, and notforpayment is 0
        $bitemids= DB::table('embs')
        ->where('t_bill_id', $tbillid)
        ->where('ee_check', 1)
        ->where('notforpayment' , 0)
        ->select('b_item_id','meas_id','t_bill_id','ee_chk_qty')
        ->get();
        
        //    dd($bitemids);


        $totalQuantity = 0;
        $resultArray = [];

        // Iterate through each item in the collection and multiply the 'qty' by $billRate
        foreach ($bitemids as $item) {

            $bill_rt = DB::table('bil_item')
            ->where('t_bill_id', $tbillid)
            ->where('b_item_id',$item->b_item_id)
            ->value('bill_rt');
             // dd($bill_rt);

            // Create a new item with the updated 'qty'
            $updatedItem = [
                'b_item_id' => $item->b_item_id,
                'meas_id' => $item->meas_id,
                't_bill_id' => $item->t_bill_id,
                'qty' => $item->ee_chk_qty * $bill_rt,
            ];

            // Add the updated quantity to the total
            $totalQuantity += $updatedItem['qty'];

            // Append the updated item to the result array
            $resultArray[] = $updatedItem;
        }

        // Now, $resultArray contains the updated results
        // dd($resultArray, $totalQuantity,$b_item_amt);
            //   dd($b_item_amt);

              if ($b_item_amt != 0)
               {
                $percentage = ($totalQuantity / $b_item_amt) * 100;
                // dd($percentage);
                $formattedPercentage = number_format($percentage, 2);

                 // Replace with your actual baseAmount value

    // Calculate the amount based on the percentage and baseAmount
            $amount = ($percentage / 100) * $b_item_amt;

                // dd($percentage,$formattedPercentage,$amount,$totalQuantity);
            } else {
                // Handle the case where $b_item_amt is 0 to avoid division by zero error
                // dd('Cannot calculate percentage. $b_item_amt is zero.');
            }



  // Retrieve checked data (meas_id and ee_chk_qty) from embs table where t_bill_id matches tbillid, ee_check is 1, and notforpayment is 0
        $checkeddata = DB::table('embs')
        ->select('meas_id' , 'ee_chk_qty')
        ->where('t_bill_id' , $tbillid)
        ->where('ee_check' , 1)
        ->where('notforpayment' , 0)
        ->get();

    // Instantiate CommonHelper class to format amount into Indian Rupees
    $convert=new CommonHelper();
        $amount=$convert->formatIndianRupees($amount);

        // Return JSON response with amount, formattedPercentage, and checkeddata
        return response()->json(['amount'=>$amount ,'formattedPercentage'=>$formattedPercentage , 'checkeddata' => $checkeddata]);
    }
    catch (\Exception $e) 
    {
        // Log the exception or handle it as needed
        Log::error('Error in SubmitAllEE: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred ' . $e->getMessage()], 500);
    }
}

// Function to calculate percentage indicator based on selected checkboxes and amount
    public function PercentIndicator(Request $request)
    {
        $meas_amt=0;
        $result[]=0;
        $checked_mead_amt=0;

         // Retrieve inputs from the request
        $amount=$request->amount;
        // dd($amount);
        $tbillid=$request->tbillid;
        // dd($tbillid);
        $WorkId=$request->workid;
        // dd($WorkId);
        $measid=$request->measid;
        // dd($measid);
        $meas_date_input=$request->dateid;
        //dd($meas_date_input);
        $percentageTextBox=$request->percentageTextBox;

        $AmountTextBox=$request->AmountTextBox;

        $measidvalue=$request->measidstringArray;
         //dd($measidvalue);

        // Convert the date using Carbon
        $carbonDate = Carbon::createFromFormat('d/m/Y', $meas_date_input);

        // Format the date in the desired format
        $meas_date = $carbonDate->format('Y-m-d');
        //dd($meas_date);

            $PartA_Amt= DB::table('bills')
            ->where('t_bill_id', $tbillid)
            ->value('c_part_a_amt');
            // dd($PartA_Amt);

            $PartB_Amt= DB::table('bills')
            ->where('t_bill_id', $tbillid)
            ->value('c_part_b_amt');

            // Calculate total bill item amount by summing part A and part B amounts
            $b_item_amt=$PartA_Amt +  $PartB_Amt;
            // dd($b_item_amt);

               // Select all b_item_ids where t_bill_id matches tbillid and measurment_dt matches meas_date
            $bitemids= DB::table('embs')
            ->where('t_bill_id', $tbillid)
            ->where('ee_check', 1)
            ->select('b_item_id','meas_id','t_bill_id','qty')
            ->get();
           //dd($bitemids);


        $totalQuantity = 0;
        $resultArray = [];

//Select aLl
        if($measid =="0")
        {
            // dd($measid);
             // Select all b_item_ids where t_bill_id matches tbillid and measurment_dt matches meas_date
            $b_item_ids = DB::table('embs')
            ->select('b_item_id')
            ->where('t_bill_id', $tbillid)
            ->where('measurment_dt', $meas_date)
            ->get();
            //dd($b_item_ids);
            //Select all percentage..........................................................
           // dd($tbillid,$meas_date);


            foreach($b_item_ids as $b_item_id)
            {
                // Retrieve ee_check value from recordms table where Rec_date matches meas_date and ee_check is 1
                $ee_chk_tbl = DB::table('recordms')
                ->where('Rec_date', $meas_date)
                ->where('ee_check',1)
                ->value('ee_check');
                //dd($ee_chk_tbl);

                 // Retrieve qty from embs table where t_bill_id matches tbillid and measurment_dt matches meas_date
                $qty = DB::table('embs')
                ->where('t_bill_id', $tbillid)
                ->where('measurment_dt', $meas_date)
                ->value('qty');
                //dd($qty);

                 // Retrieve bill_rt from bil_item table where b_item_id matches $b_item_id
                $bill_rt = DB::table('bil_item')
                ->where('b_item_id',$b_item_id)
                ->value('bill_rt');
                // /dd($bill_rt);

                 // Calculate checked_mead_amt based on amount and meas_amt
                if($ee_chk_tbl){
                    // $meas_amt= $meas_amt+($bill_rt * $qty);
                    $meas_amt=$bill_rt * $qty;
                    $checked_mead_amt=$amount-$meas_amt;
                    $result[]=$checked_mead_amt;
                    //dd($measid,$qty,$bill_rt,$meas_amt);
                }
                else
                {


                    $meas_amt=$bill_rt * $qty;
                    $checked_mead_amt=$amount+$meas_amt;

                      // Append checked_mead_amt to result array
                    $result[]=$checked_mead_amt;
                 //dd($measid,$qty,$bill_rt,$meas_amt);
                }
            }
             //dd($checked_mead_amt);
                $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
                //dd($Checked_Percentage);

                  // Calculate Checked_Percentage based on checked_mead_amt and b_item_amt
                // Format the result to have only three digits after the decimal point
              $Checked_Percentage = number_format($Checked_Percentage1, 2);
                return response()->json(['Checked_Percentage'=> $Checked_Percentage,'checked_mead_amt'=>$checked_mead_amt]);
            // dd($bitemid,$measid,$qty,$bill_rt,$meas_amt);
        }

        //Custome Checkbox Percentage.....
        // $meas_amt=0;
        if($measidvalue===null){
            //dd("Okkkkk");
            $checked_mead_amt = $amount;
        }
        else{
            // dd($measidvalue);
            // dd($amount);
            // dd($measidvalue);
            foreach($measidvalue as $measid)
            {
                  // Retrieve ee_check value from embs table where meas_id matches $measid and ee_check is 1
                $ee_chk_tbl = DB::table('embs')
                    ->where('meas_id', $measid)
                    ->where('ee_check',1)
                    ->value('ee_check');
                 //dd($ee_chk_tbl);
                  // Retrieve b_item_id from embs table where t_bill_id matches tbillid and meas_id matches $measid
                $bitemid = DB::table('embs')
                    ->where('t_bill_id', $tbillid)
                    ->where('meas_id', $measid)
                    ->value('b_item_id');
                // dd($bitemid);

                 // Retrieve qty from embs table where b_item_id matches $bitemid and meas_id matches $measid
                $qty = DB::table('embs')
                    ->where('b_item_id', $bitemid)
                    ->where('meas_id', $measid)
                    ->value('qty');
                // dd($qty);

                  // Retrieve bill_rt from bil_item table where t_bill_id matches tbillid and b_item_id matches $bitemid
                $bill_rt = DB::table('bil_item')
                    ->where('t_bill_id', $tbillid)
                    ->where('b_item_id', $bitemid)
                    ->value('bill_rt');
                // dd( $bitemid,$qty,$bill_rt,$measid);

                   // Calculate checked_mead_amt based on amount, ee_chk_tbl, and meas_amt
                if($ee_chk_tbl){
                    //dd("ee_chk_tbl");

                $meas_amt=$bill_rt * $qty;
                $checked_mead_amt= $amount - $meas_amt;
                }
                else
                {
                    $meas_amt= $bill_rt * $qty;
                    $checked_mead_amt= $amount + $meas_amt;
                }
            }

        }
        //dd($checked_mead_amt);
        // dd($result,$qty,$bill_rt,$meas_amt);
  // Calculate Checked_Percentage based on checked_mead_amt and b_item_amt
        $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
        //dd($Checked_Percentage1,$checked_mead_amt);

        // Format the result to have only three digits after the decimal point
        $Checked_Percentage = number_format($Checked_Percentage1, 2);

         // Return JSON response with Checked_Percentage and checked_mead_amt
        return response()->json(['Checked_Percentage'=> $Checked_Percentage ,'checked_mead_amt'=>$checked_mead_amt]);
    }
    
    //* Calculate percentage load quantity based on selected checkboxes and amount.
public function precentageloadquantity(Request $request)
{
    // Retrieve inputs from the request
    $tbillid=$request->tbillid;
    // dd($tbillid);
    $WorkId=$request->workid;

// Retrieve Part A amount from bills table where t_bill_id matches tbillid
    $PartA_Amt= DB::table('bills')
    ->where('t_bill_id', $tbillid)
    ->value('c_part_a_amt');
    // dd($PartA_Amt);

      // Retrieve Part B amount from bills table where t_bill_id matches tbillid
    $PartB_Amt= DB::table('bills')
    ->where('t_bill_id', $tbillid)
    ->value('c_part_b_amt');
    // dd($PartB_Amt);
    // Calculate total bill item amount by summing Part A and Part B amounts
    $b_item_amt=$PartA_Amt +  $PartB_Amt;

 // Retrieve checkbox data from the request
    $checkboxdatas=$request->checkboxData;
   
    // Initialize variables
     $totalMeasAmt = [];
        $PreviSelectedCheckboxAmount = 0; // Initialize the variable to store the total amount
        $Checked_Percentage = 0;


        $bitemidDBCalculation = DB::table('embs')
        ->where('t_bill_id', $tbillid)
        ->where('ee_check',1)
        ->where('ee_check',1)
        ->select('b_item_id','meas_id','ee_chk_qty')
        ->get();

         // If $checkboxdatas is empty, return zero values for Checked_Percentage and checked_mead_amt
        if (empty($checkboxdatas)) {
            // If $checkboxdatas is empty, set $checked_mead_amt and $Checked_Percentage to zero
            $checked_mead_amt = 0;
            $Checked_Percentage = 0;

            return response()->json(['Checked_Percentage'=> $Checked_Percentage ,'checked_mead_amt'=>$checked_mead_amt]);

        }
        
        //Update executive engineer check Quantity
        foreach ($checkboxdatas as &$item) {
    $meas_id = $item['id'];
    $quantity = $item['eeqty'];
    //dd($meas_id);

    $eeqtydb=DB::table('embs')->where('meas_id' , $meas_id)->first('ee_chk_qty');
    //dd($eeqtydb , $quantity);
        if ($quantity > $eeqtydb->ee_chk_qty) {
            // Update the quantity in the nested array if it exceeds the database quantity
            $item['eeqty'] = $eeqtydb->ee_chk_qty;
            //dd($eeqtydb->ee_chk_qty);
        }
    }

        
        
          // Calculate the total amount based on selected checkboxes 
    foreach($checkboxdatas as $measdata)
    {
          // Retrieve ee_check value from embs table where meas_id matches $measdata['id']
        $ee_chk_tbl = DB::table('embs')
            ->where('meas_id', $measdata['id'])
            ->value('ee_check');
          //dd($ee_chk_tbl);
            // Retrieve b_item_id from embs table where t_bill_id matches tbillid and meas_id matches $measdata['id']
        $bitemid = DB::table('embs')
            ->where('t_bill_id', $tbillid)
            ->where('meas_id', $measdata['id'])
            ->value('b_item_id');
        // dd($bitemid);

         // Retrieve notforpayment value from embs table where meas_id matches $measdata['id']
        $notforpayment = DB::table('embs')
        ->where('meas_id', $measdata['id'])
        ->value('notforpayment');

        //dd($notforpayment);
      // Proceed with calculation if notforpayment is not 1
        if ($notforpayment != 1) {
            //dd($notforpayment);

            // If notforpayment is not 1, proceed with the calculation
            $bill_rt = DB::table('bil_item')
                ->where('t_bill_id', $tbillid)
                ->where('b_item_id', $bitemid)
                ->value('bill_rt');
        
            $meas_amt = $bill_rt * $measdata['eeqty'];
            $PreviSelectedCheckboxAmount += $meas_amt;
        }
         //dd($PreviSelectedCheckboxAmount);
         //dd($notforpayment);


       
        }
         //dd($PreviSelectedCheckboxAmount,$amount);
        $checked_mead_amt = $PreviSelectedCheckboxAmount; // Assign $amount to 'checked_mead_amt'

      // Calculate Checked_Percentage based on PreviSelectedCheckboxAmount and b_item_amt
        $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
        // Format the result to have only three digits after the decimal point
        $Checked_Percentage = number_format($Checked_Percentage1, 2);
        //dd($Checked_Percentage,$checked_mead_amt);
 
 
       // Format checked_mead_amt using CommonHelper method formatIndianRupees
        $convert=new CommonHelper();
        $checked_mead_amt=$convert->formatIndianRupees($checked_mead_amt);

     
        // Return JSON response with Checked_Percentage and checked_mead_amt
    return response()->json(['Checked_Percentage'=> $Checked_Percentage ,'checked_mead_amt'=>$checked_mead_amt]);
}


//Update mb_status field in bills table and redirect to billlist route.
    public function funYesSubmit(Request $request,$workid,$tbillid)
    {
        
        // Update mb_status to 5 in bills table where t_bill_id matches $tbillid
       $updateMbstatus = DB::table('bills')
        ->where('t_bill_id', $tbillid)
        ->update(['mb_status' => 6]);
        // dd($workid);
        
        // Check if the update was successful
        if ($updateMbstatus) {
                 
         //Email notification for MB status

          // Define the new status
          $newStatus = 6;


          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $workid)->first();

          // Fetch the JE  details related to the given work_id
          $jeDetails = DB::table('jemasters')->where('jeid', $workdata->jeid)->first();
          //dd($eeDetails);
          
            // Fetch the EE  details related to the given work_id
            $from = DB::table('eemasters')->where('eeid', $workdata->EE_id)->first();

          if ($jeDetails) {
 
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);
              // Send the notification email to the JE
              Mail::to($jeDetails->email)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $from , $jeDetails));
          } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }

        }



       // Redirect to the billlist route with workid parameter
        return redirect()->route('billlist', ['workid' => $workid]);
    }




}
