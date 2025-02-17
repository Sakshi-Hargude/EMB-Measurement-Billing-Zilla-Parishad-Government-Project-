<?php
namespace App\Http\Controllers;
use App\Models\Workmaster;
use DateTime; 
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use App\Helpers\CommonHelper;


class ExecutiveEngineerCnt extends Controller{
   public function funExecutiveData(Request $request) {
        // /dd($request);
        // dd("OKKKKKKKKKKKKKKKKKKKKK..............");
      // dd($request);
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
                //dd($tBillNo,$WorkId,$tbillid,$billDate);
                $bitemid = DB::table('bil_item')
                ->where('t_bill_id', '=', $tbillid)
                ->get('b_item_id');

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

            $bitemsnm = DB::table('bil_item')
                ->where('t_bill_id', '=', $tbillid)
                ->get();

            $exists = DB::table('recordms')
                ->where('t_Bill_Id', $tbillid)
                ->get();

            if ($exists) {
                DB::table('recordms')
                    ->where('t_Bill_Id', $tbillid)
                    ->where('Work_Id', '=', $WorkId)
                    ->delete();

            }
            // dd("Record is deleted");

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
        //dd($mergeddts);
        //dd($mergeddts[]->measurment_dt);
        //dd($mergeddts[0]->date_meas);
        $obdata = [];

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

        $distinctdts = DB::table('embs')
            ->Join('stlmeas', 'embs.Work_Id', '=', 'stlmeas.Work_Id')
            ->select(
                DB::raw('DISTINCT DATE_FORMAT(embs.measurment_dt, "%Y-%m-%d") as formatted_measurment_dt'),
                DB::raw('DATE_FORMAT(stlmeas.date_meas, "%Y-%m-%d") as formatted_date_meas')
            )
            ->where('embs.Work_Id', '=', $WorkId)

            ->get();
        //dd($distinctdts);
            $combinedDates = [];


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
           //dd($dtarr);
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
           $combinarray = $NormalDb->concat($StillDb);
           //dd($combinarray);

           //Count of combine data...
           $countcombinarray=count($combinarray);
           //dd($countcombinarray);
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
           //dd($Count_Chked_Emb_Stl , $countcombinarray);
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
                   //  dd($tBillNo,$WorkId,$tbillid,$billDate);
                        $workDetails1 = DB::table('workmasters')
                        ->select('Work_Nm', 'Sub_Div', 'Agency_Nm', 'Work_Id', 'Wo_Dt','Period','WO_No','Stip_Comp_Dt')
                        ->where('Work_Id', '=', $WorkId)
                        ->first();
                        //dd($workDetails1);

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

                        $bitemid = DB::table('bil_item')
                            ->where('t_bill_id', '=', $tbillid)
                            ->get('b_item_id');


                        foreach ($bitemid as $items) {
                            $bitemId = $items->b_item_id;
                            $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->get()->value('item_id');

                            if (
                                in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017",
                                    "002023", "002024", "003351", "003352", "003878"])
                            ) {
                                //dd("Steel Data");
                            } else {
                                //dd("Normal data ");
                            }
                        }

                        $bitemsnm = DB::table('bil_item')
                            ->where('t_bill_id', '=', $tbillid)
                            ->get();

                        $exists = DB::table('recordms')
                            ->where('t_Bill_Id', $tbillid)
                            ->get();
                        // dd("Record is deleted");


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



                        $recinfo=  DB::table('recordms')
                                ->where('Work_Id', '=', $WorkId)
                                ->get();
                                //dd($recinfo);

                        $divName1 = DB::table('workmasters')
                            ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                            ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
                            ->where('workmasters.Work_Id', '=', $WorkId)
                            ->value('divisions.div');

                        $sectionEngineer = DB::table('designations')->get();

                        $Work_Dtl = DB::table('workmasters')
                            ->select('Work_Nm', 'Sub_Div', 'WO_No', 'Period', 'Stip_Comp_Dt')
                            ->where('Work_Id', '=', $WorkId)
                            ->first();

                        $divNm = DB::table('workmasters')
                            ->join('subdivms', 'workmasters.Sub_Div_Id', '=', 'subdivms.Sub_Div_Id')
                            ->leftJoin('divisions', 'subdivms.Div_Id', '=', 'divisions.Div_Id')
                            ->where('workmasters.Work_Id', '=', $WorkId)
                            ->value('divisions.div');

                        $titemno = DB::table('bil_item')
                            ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc', 'exec_qty', 'item_unit')
                            ->where('t_bill_id', '=', $tbillid)
                            ->get();
                        //dd($titemno);

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

                        $titemnoRecords = DB::table('bil_item')
                            ->select('t_item_no', 'item_desc', 'exec_qty', 'ratecode', 'bill_rt')
                            ->where('t_bill_id', '=', $tbillid)
                            ->get();

                        //dd($titemnoRecords);
                        $Recordeno = DB::table('recordms')
                        ->select('Record_Entry_No')
                        ->where('t_bill_id', '=', $tbillid)
                        ->get();
                        //dd($Recordeno);

                        $DBsectionEng=DB::table('workmasters')
                        ->select('jeid')
                        ->where('Work_Id',$WorkId)
                        ->get();
                    //   dd($DBsectionEng);
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
                        // dd($DBSectionEngNames);
                            $max_dye_date = DB::table('embs')
                            ->where('t_bill_id' , $tbillid)
                            ->min('dyE_chk_dt');

             $checkeddata = DB::table('embs')
                            ->select('meas_id' , 'ee_chk_qty')
                            ->where('t_bill_id' , $tbillid)
                            ->where('ee_check' , 1)
                            ->where('notforpayment' , 0)
                            ->get();

                        // dd($DBSectionEngNames);
            return view('ExecutiveEngineerEMB',compact('DBSectionEngNames','max_dye_date','workDetails1','billDate','fund_Hd1','divName1','Recordeno','titemnoRecords','titemno','tbillid','recnovalues'));
            //return redirect()->route('billlist', ['WorkId' => $WorkId]);
    }

    public function RecordWiseExecutiveCheckFun(Request $request)
    {
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
        // dd($WorkIdvv,$tbillid,$Rec_E_No);
        $html ='';

        $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();

        $recdate = DB::table('recordms')
        ->select('Rec_date')
        ->where('t_bill_id', $tbillid)
        ->where('Record_Entry_No', $Rec_E_No)
        ->value('Rec_date');

        $RecDate = date("d/m/Y", strtotime($recdate));
        // dd($RecDate);


    // $measnormaldata will contain the maximum value of 'dyE_chk_dt'

        foreach($billitemdata as $itemdata)
            {
                $bitemId=$itemdata->b_item_id;
                // dd($bitemId);
                $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $recdate)->get();
                $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $recdate)->get();
                //meas data check
            //meas data check
            $EmbdyeChkCount = DB::table('embs')->where('t_bill_id', $tbillid)->where('measurment_dt', $recdate) ->where('ee_check',  1)->count();
            //  dd($EmbdyeChkCount);

            $Stldyechkcount = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdate)->where('ee_check',"=",1)->count();
            // dd($Stldyechkcount);
            $Count_Chked_Emb_Stl= $EmbdyeChkCount+$Stldyechkcount;

                $stlmeascount=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdate)->count();
                $embcount = DB::table('embs')->where('t_bill_id', $tbillid)->where('measurment_dt', $recdate)->get();

                $embCountWithoutTMT = 0;

                if ($embcount->isNotEmpty()) {
                    foreach ($embcount as $tmtdata) {
                        if (strpos($tmtdata->parti, 'TMT') !== 0) {
                            $embCountWithoutTMT++;
                        }
                    }
                }

            //dd($embCountWithoutTMT);
                // dd($stlmeascount);
                $measdatacount=$embCountWithoutTMT+$stlmeascount;
                //dd($measdatacount);

            if (!$measnormaldata->isEmpty() || !$meassteeldata->isEmpty()) {

                //$html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><tr><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th><th style="border: 1px solid black; padding: 8px; background-color: lightpink; width: 90%; text-align: justify;"> ' . $itemdata->exs_nm . '</th></tr></thead></table>';
$html .= '<table class="" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="padding: 8px; background-color: lightpink; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th>
                    <th style="padding: 8px; background-color: lightpink; width: 90%; text-align: justify;">' . $itemdata->exs_nm . '</th>
                </tr>
            </thead>
          </table>';

                $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
                //dd($itemid);
                if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
                {
                    $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->get();

                    $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
                    'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


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

                    $sums = array_fill_keys($ldiamColumns, 0);

                    foreach ($stldata as $row) {
                        foreach ($ldiamColumns as $ldiamColumn) {
                            $sums[$ldiamColumn] += $row->$ldiamColumn;
                        }
                    }//dd($stldata);

                    $bill_member = DB::table('bill_rcc_mbr')
                        ->whereExists(function ($query) use ($bitemId) {
                        $query->select(DB::raw(1))
                            ->from('stlmeas')
                            ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                            ->where('bill_rcc_mbr.b_item_id', $bitemId);
                        })
                        ->get();


                    $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

                    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;"><thead><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 3%;  min-width: 3%;">Sr No</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 25%; min-width: 25%;">Bar Particulars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 6%; min-width: 6%;">No of Bars</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 7%; min-width: 7%;">Length of Bars</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">6mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">8mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">10mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">12mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">16mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">20mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">25mm</th>
                    <th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%; min-width: 5%;">28mm</th><th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 3%; min-width: 3%;">Check</th></thead>';

                    foreach ($bill_member as $index => $member) {
                            //dd($member)5
                        $rcmbrid=$member->rc_mbr_id;
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $recdate)->get();
                                //dd($memberdata);

                        if ( !$memberdata->isEmpty()) {

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
                    $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();

                    if($meassteeldata->isEmpty()){
                        $html .= '<table class="table-striped" style="border-right: 1px solid black; width:100%;"><thead><th style="border: 1px solid black; width: 5%; border-color: black;">Sr. No</th>
                        <th style="border: 1px solid black; width: 30%; border-color: black;">Particulars</th><th style="border: 1px solid black; width: 7%; border-color: black;">Number</th><th style="border: 1px solid black; width: 7%; border-color: black;">Length</th>
                        <th style="border: 1px solid black; width: 7%; border-color: black;">Breadth</th><th style="border: 1px solid black; width: 7%; border-color: black;">Height</th><th style="border: 1px solid black; width: 7%; border-color: black;">Quantity</th><th style="border: 1px solid black; width: 4%; border-color: black;">Check</th><th style="border: 1px solid black; width: 10%; border-color: black;">Checked Quantity</th>
                        </thead><tbody>';
                    }
                    foreach($normaldata as $nordata)
                    {
                        $dye_chk_date = date('d/m/Y', strtotime($nordata->dyE_chk_dt));
                        $measidstring = "'$nordata->meas_id'";
                    $formula= $nordata->formula;
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

              // dd($measdatacount,$Count_Chked_Emb_Stl);
            // return response()->json(['countcombinarray'=>$countcombinarray,'BillDt'=>$BillDt,'combinarray'=> $combinarray,'html'=>$html,'RecDate'=>$RecDate,'Count_Chked_Emb_Stl'=>$Count_Chked_Emb_Stl]);
            return response()->json(['measdatacount'=>$measdatacount,'html'=>$html,'RecDate'=>$RecDate,'Count_Chked_Emb_Stl'=>$Count_Chked_Emb_Stl]);

    }

    // public function RecordWiseExecutiveCheckFun(Request $request) {
    //     $tbillidv = $request->input('tbillid_valuer');
    //     //dd($tbillidv);
    //     $itemidv =$request->input('itemid_valuer');

    //     $WorkIdvv =$request->input('WorkId_valuer');
    //     //  dd($WorkIdvv,$itemidv,$tbillidv);

    //     $Rec_E_No=$request->input('Record_Entry_Nor');
    //         // dd($Rec_E_No);

    //     $SelectDtAll= $request->input('SelectDtAll');
    //     //dd($SelectDtAll);
    //     $SelectDtAllS= $request->input('SelectDtAllS');

    //     $CheckedSelectAll=0;
    //     //dd("SelectDtAll:",$SelectDtAll);
    //     $redtValues = DB::table('recordms')
    //     ->select('Rec_date')
    //     ->where('t_bill_id', $tbillidv)
    //     ->where('Record_Entry_No', $Rec_E_No)
    //     ->value('Rec_date');

    //     $recenid = DB::table('recordms')
    //     ->where('t_bill_id', $tbillidv)
    //     ->where('Record_Entry_No', $Rec_E_No)
    //     ->value('Record_Entry_Id');
    //     //dd($recenid);


    //     $dyedate=DB::table('recordms')
    //     ->where('Record_Entry_Id', $recenid)
    //     ->value('ee_chk_dt');
    //     //dd($dyedate);

    //     $recordmscheckeddata=DB::table('recordms')
    //     ->where('Record_Entry_Id', $recenid)
    //     ->value('ee_check');
    //     //dd($recordmscheckeddata);

    //     $firstDate = $redtValues[0];
    //     // dd($firstDate);

    //     $formattedDate = date("d-m-Y", strtotime($redtValues));
    //     //dd($formattedDate);

    //     $Ndata = DB::table('embs')
    //         ->select('embs.*')
    //         ->where('embs.measurment_dt', $redtValues)
    //         ->where('Work_Id',$WorkIdvv)
    //         ->get();
    //     //dd($Ndata);
    //     $normaldatacount=count($Ndata);
    //     //dd($normaldatacount);

    //     $Sdata = DB::table('stlmeas')
    //     ->select('stlmeas.*')
    //     ->where('stlmeas.date_meas', $redtValues)
    //     ->where('work_id',$WorkIdvv)
    //     ->get();

    //     $bitemsid = DB::table('bil_item')
    //     ->select('item_id','tnd_qty','exs_nm','item_unit')
    //     ->where('t_bill_id', '=', $tbillidv)
    //     ->get();
    //     //dd($bitemsid);

    //     $titemno = DB::table('bil_item')
    //     ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc','exec_qty','item_unit')
    //     ->where('t_bill_id', '=', $tbillidv)
    //     ->get();


    //     // dd($titemno);

    //     $strhtml = "";
    //     $strhtmlformula = "";
    //     $normaldesc = "";
    //     $steeldesc="";
    //     $strhtmlsteel="";
    //     $itemnosteel="";
    //     $itemnovalueeSteel =null;
    //     $exsnmsteel="";
    //     $titemnovaluee=null;
    //     $itemno=null;
    //     $exsnm=null;
    //     $DataBillItem = DB::table('bil_item')->where('t_bill_id', $tbillidv)->get('b_item_id');
    //     // $RecordNormalHTMLhead="";
    //     foreach($DataBillItem  as $main){
    //     // dd($main);
    //     $bitemidss=$main->b_item_id;
    //     $steelid='';
    //     // Main / Outer Forloop ------------------------------------------------------
    //     //dd($main);
    //     $strhtml = '';
    //     $strhtmlformula = '';

    //     //Bill Item Table Related data="
    //     $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillidv)->where('measurment_dt' , $redtValues)->get();
    //     //dd($NormalDb);
    //     $StillDb = DB::table('stlmeas')->where('t_bill_id' , $tbillidv)->where('date_meas' , $redtValues)->get();
    //     //dd($StillDb);
    //     // $countcombinarray=count($StillDb);
    //         //dd($countcombinarray);
    //     //$combinarray = $NormalDb+$StillDb;
    //     $combinarray = $NormalDb->concat($StillDb);
    //     //dd($combinarray);

    //     //Count of combine data...
    //     $countcombinarray=count($combinarray);
    //     //dd($countcombinarray);

    //     // $EmbdyeChk = DB::table('embs')->where('t_bill_id' , $tbillidv)->where('measurment_dt' , $redtValues)->where('ee_check',"=",1)->get();
    //     // $Stldyechk = DB::table('stlmeas')->where('t_bill_id' , $tbillidv)->where('date_meas' , $redtValues)->where('ee_check',"=",1)->get();

    //     $EmbdyeChkCount = DB::table('embs')
    //     ->where('t_bill_id', $tbillidv)
    //     ->where('measurment_dt', $redtValues)
    //     ->where('ee_check', "=", 1)
    //     ->count();
    //     // dd($EmbdyeChkCount);
    //     // $Count_Chked_Emb_Stl  $countcombinarray
    //     $Stldyechkcount = DB::table('stlmeas')->where('t_bill_id' , $tbillidv)->where('date_meas' , $redtValues)->where('ee_check',"=",1)->count();
    //     // dd($Stldyechkcount);
    // $Count_Chked_Emb_Stl= $EmbdyeChkCount+$Stldyechkcount;
    // //dd($Count_Chked_Emb_Stl);
    // if($Count_Chked_Emb_Stl==$countcombinarray)
    // {

    // }
    //     //dd($countcombinarray);
    //     $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
    //     'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

    //     foreach ($combinarray as &$data) {
    //         if (is_object($data)) {
    //             //dd($data);
    //             foreach ($ldiamColumns as $ldiamColumn) {
    //                 if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length)
    //                 {
    //                     $temp = $data->$ldiamColumn;
    //                     $data->$ldiamColumn = $data->bar_length;
    //                     $data->bar_length = $temp;
    //                     break; // Stop checking other ldiam columns if we found a match
    //                 }
    //             }
    //         }
    //     }

    //     $steeldesc='';
    //     //dd($combinarray);
    //     foreach($combinarray  as $subloop){
    //         // dd($subloop);
    //         //dd($measidstring);
    //         $measidsnormal='';

    //         $bitemid=$subloop->b_item_id;
    //         $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('item_id');
    //         //dd($bitemid);
    //         if($SelectDtAllS !=''){
    //                     if ( in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016",
    //                                                     "002017", "002023", "002024", "003351", "003352", "003878"])
    //                                                     )
    //                     {
    //                     // dd($subloop);
    //                     //$subloop
    //                     $array = (array)$subloop;
    //                     $keys = array_keys($array);
    //                     if( $keys[4] === 'sr_no')
    //                     { }
    //                     else
    //                     {
    //                         $itemnosteel=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('t_item_no');
    //                         $exsnmsteel=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('exs_nm');

    //                         $itemnovalueeSteel = DB::table('bil_item')
    //                         ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value_Steel'))
    //                         ->where('b_item_id' , $bitemid)
    //                         ->value('combined_value_Steel');
    //                         // dd($itemnovalueeSteel);

    //                         $rcmbrid=$subloop->rc_mbr_id;
    //                         $rccmbr=DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->value('rcc_member');
    //                         $mbrparti=DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->value('member_particulars');
    //                         $no_member=DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->value('no_of_members');

    //                         $formattedDatesteel=$subloop->date_meas;
    //                         //dd($formattedDatesteel);
    //                         $steelmeasdate = date("d-m-Y", strtotime($formattedDatesteel));
    //                          //dd($steelmeasdate);
    //                         // dd($subloop);

    //                         if($subloop !==null){
    //                             $subloop->meas_id='';
    //                             if( $subloop->ee_check==1)
    //                             {
    //                                 $strhtmlsteel .=
    //                                 '<div class="container1"><table class="table1 table-responsive" style="background-color: lightpink;"><tr><th  style="width: 5%; ">Item No:</th><td  style="width: 3%;  ">' . $itemnovalueeSteel . '</td>
    //                                 <th  style="width: 5%; ">Item Description:</th><td  style="width: 70%; ">' . $exsnmsteel . '</td>
    //                                 </tr></table></div>';
    //                                 $strhtmlsteel .= '
    //                                     <table class="table table-bordered table-striped"><tr style="text-align: center; background-color: lightgray;">
    //                                             <tr>
    //                                                 <th style="width: 5%;">Sr No</th>
    //                                                     <td style="width: 3%; border-color: black;">' . $subloop->bar_sr_no . '</td>
    //                                                 <th style="width: 10%;">RCC Member:</th>
    //                                                     <td style="width: 30%; border-color: black;">' . $rccmbr . '</td>
    //                                                 <th style="width: 15%;">Member Particular:</th>
    //                                                     <td style="width: 40%; border-color: black;">' . $mbrparti . '</td>
    //                                                 <th style="width: 15%;">No Of Members:</th>
    //                                                     <td style="width: 20%;">' . $no_member . '</td>
    //                                             </tr>
    //                                     </table>
    //                                    <table class="table table-bordered table-striped" style="width: 100%;">
    //                                    <tr>
    //                                     <th style="width: 3%;">Sr No</th>
    //                                     <th style="width: 10%;">Bar Particulars</th>
    //                                     <th style="width: 5%;">No of Bars</th>
    //                                     <th style="width: 5%;">Length of Bars</th>
    //                                     <th style="width: 5%;">6mm</th>
    //                                     <th style="width: 5%;">8mm</th>
    //                                     <th style="width: 5%;">10mm</th>
    //                                     <th style="width: 5%;">12mm</th>
    //                                     <th style="width: 5%;">16mm</th>
    //                                     <th style="width: 5%;">20mm</th>
    //                                     <th style="width: 5%;">25mm</th>
    //                                     <th style="width: 5%;">28mm</th>
    //                                     <th style="width: 5%;">32mm</th>
    //                                     <th style="width: 5%;">36mm</th>
    //                                     <th style="width: 5%;">40mm</th>
    //                                     <th style="width: 7%;">dyE Checked Date</th>
    //                                     <th style="width: 5%;">Check</th>
    //                                     <th style="width: 5%;">Checked Date</th>
    //                                 </tr>
    //                                 <tr>
    //                                     <td style="width: 5%;">' . $subloop->bar_sr_no . '</td>
    //                                     <td style="width: 10%;">' . $subloop->bar_particulars . '</td>
    //                                     <td style="width: 5%;">' . $subloop->no_of_bars . '</td>
    //                                     <td style="width: 8%;">' . $subloop->bar_length . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam6 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam8 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam10 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam12 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam16 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam20 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam25 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam28 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam32 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam36 . '</td>
    //                                     <td style="width: 5%;">' . $subloop->ldiam40 . '</td>
    //                                     <td style="width: 7%;">' . $steelmeasdate . '</td>
    //                                     <td style="width: 5%; padding-left: 30px;">
    //                                     <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Steel[' . $subloop->steelid . ']."  onclick="CustomeCheckBoxSFun('.$countcombinarray.');" checked>
    //                                     </td><td><input type="date" class="form-control customDt" value="'. $SelectDtAllS.'"   name="customDateInputS['. $subloop->steelid.']" onchange="CustomeDtSFun('. $subloop->steelid.');"></td></tr>
    //                                     </table>';
    //                             }
    //                             else{
    //                                 $strhtmlsteel .=
    //                                 '<div class="container1"><table class="table1 table-responsive" style="background-color: lightpink;"><tr><th  style="width: 5%; ">Item No:</th><td  style="width: 3%;  ">' . $itemnovalueeSteel . '</td>
    //                                 <th  style="width: 5%; ">Item Description:</th><td  style="width: 70%; ">' . $exsnmsteel . '</td>
    //                                 </tr></table></div>';
    //                                 $strhtmlsteel .= '<table class="table3 table-responsive table-bordered table-striped">
    //                                 <tr style="text-align: center; background-color: lightgray;">
    //                                     <th style="width: 5%;">Sr No</th>
    //                                         <td style="width: 5%; border-color: black;">' . $subloop->bar_sr_no . '</td>
    //                                     <th style="width: 10%;">RCC Member:</th>
    //                                         <td style="width: 30%; border-color: black;">' . $rccmbr . '</td>
    //                                     <th style="width: 15%;">Member Particular:</th>
    //                                      <td style="width: 20%; border-color: black;">' . $mbrparti . '</td>
    //                                     <th style="width: 15%;">No Of Members:</th>
    //                                         <td style="width: 20%;">' . $no_member . '</td>
    //                                 </tr>
    //                             </table>
    //                             <table class="table table-responsive table-bordered table-striped">
    //                             <tr>
    //                             <th style="width: 3%;">Sr No</th>
    //                             <th style="width: 10%;">Bar Particulars</th>
    //                             <th style="width: 5%;">No of Bars</th>
    //                             <th style="width: 5%;">Length of Bars</th>
    //                             <th style="width: 5%;">6mm</th>
    //                             <th style="width: 5%;">8mm</th>
    //                             <th style="width: 5%;">10mm</th>
    //                             <th style="width: 5%;">12mm</th>
    //                             <th style="width: 5%;">16mm</th>
    //                             <th style="width: 5%;">20mm</th>
    //                             <th style="width: 5%;">25mm</th>
    //                             <th style="width: 5%;">28mm</th>
    //                             <th style="width: 5%;">32mm</th>
    //                             <th style="width: 5%;">36mm</th>
    //                             <th style="width: 5%;">40mm</th>
    //                             <th style="width: 7%;">dyE Checked Date</th>
    //                             <th style="width: 5%;">Check</th>
    //                             <th style="width: 5%;">Checked Date</th>
    //                             </tr>
    //                             <tr>
    //                                 <td style="width: 5%;">' . $subloop->bar_sr_no . '</td>
    //                                 <td style="width: 10%;">' . $subloop->bar_particulars . '</td>
    //                                 <td style="width: 5%;">' . $subloop->no_of_bars . '</td>
    //                                 <td style="width: 8%;">' . $subloop->bar_length . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam6 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam8 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam10 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam12 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam16 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam20 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam25 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam28 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam32 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam36 . '</td>
    //                                 <td style="width: 5%;">' . $subloop->ldiam40 . '</td>
    //                                 <td style="width: 7%;">' . $steelmeasdate . '</td>
    //                                 <td style="width: 5%; padding-left: 30px;">
    //                                     <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Steel[' . $subloop->steelid . ']."  onclick="CustomeCheckBoxSFun('.$countcombinarray.');">
    //                                 </td>
    //                                 <td><input type="date" class="form-control customDt" value="'. $SelectDtAllS.'"   name="customDateInputS['. $subloop->steelid.']" onchange="CustomeDtSFun('. $subloop->steelid.');">
    //                                 </td>
    //                             </tr>
    //                         </table></div></div>';
    //                             }

    //                         }
    //                     }
    //                 }
    //                 else
    //                 {
    //                     $measidstring = "'$subloop->meas_id'";
    //                     $subloop->steelid='';
    //                     // $measidsnormal=$subloop->meas_id;
    //                     $itemno=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('t_item_no');
    //                     $titemnovaluee = DB::table('bil_item')
    //                     ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'))
    //                     ->where('b_item_id' , $bitemidss)
    //                     ->value('combined_value');
    //                     //dd($titemnovaluee);
    //                     $formatted_dye_dt = date("d-m-Y", strtotime($subloop->dyE_chk_dt));
    //                     $exsnm=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('exs_nm');
    //                     //=============================================================================================================================================================================
    //                     if($subloop->ee_check==1){
    //                         if ($subloop->formula == null) {
    //                             $strhtml .= '<table class="table1 table-responsive">
    //                                             <tr>
    //                                                 <th style="width: 5%;">Item :</th>
    //                                                 <td style="width: 3%;">' . $titemnovaluee . '</td>
    //                                                 <th style="width: 5%;">Item Description:</th>
    //                                                 <td style="width: 70%;">' . $exsnm . '</td>
    //                                             </tr>
    //                                         </table>
    //                             <table class="table3 table-responsive table-bordered table-striped">
    //                                 <tr>
    //                                     <th style="width: 3%; border-color: black;">Sr</th>
    //                                     <th style="width: 30%; border-color: black;">Particulars</th>
    //                                     <th style="width: 7%; border-color: black;">Height</th>
    //                                     <th style="width: 7%; border-color: black;">Number</th>
    //                                     <th style="width: 7%; border-color: black;">Length</th>
    //                                     <th style="width: 7%; border-color: black;">Breadth</th>
    //                                     <th style="width: 7%; border-color: black;">Quantity</th>
    //                                     <th style="width: 7%; border-color: black;">dyE Checked Date</th>
    //                                     <th style="width: 7%; border-color: black;">Check</th>
    //                                     <th style="width: 5%; border-color: black;">Checked Date</th>
    //                                 </tr>
    //                                 <tr>
    //                                     <td style="width: 4%; border-color: black;">'.$subloop->sr_no.'</td>
    //                                     <td style="width: 30%; border-color: black;">'.$subloop->parti.'</td>
    //                                     <td style="width: 7%; border-color: black;">'.$subloop->number.'</td>
    //                                     <td style="width: 7%; border-color: black;">'.$subloop->length.'</td>
    //                                     <td style="width: 7%; border-color: black;">'.$subloop->breadth.'</td>
    //                                     <td style="width: 7%; border-color: black;">'.$subloop->height.'</td>
    //                                     <td style="width: 7%; border-color: black;">'.$subloop->qty.'</td>
    //                                     <td style="width: 7%; border-color: black;">'.$formatted_dye_dt.'</td>
    //                                     <td style="width: 5%; padding-left: 50px; padding-bottom: 30px; border-color:black;">
    //                                     <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox checkboxN" type="checkbox" name="je_check_Item['.$subloop->meas_id.']" onclick="CustomeCheckBoxSFun('.$countcombinarray.'
    //                                 );CheckIndicator('.$measidstring.');" checked>
    //                                     </td>
    //                                     <td style="width: 7%; border-color: black;">
    //                                     <input type="date" class="form-control customDtEmb" value="' . $SelectDtAllS . '" name="customDateInputN['. $subloop->meas_id.']" onchange="CustomeDtFunN('. $subloop->meas_id.');">
    //                                     </td>
    //                                 </tr>
    //                             </table></div>';

    //                         }
    //                         else
    //                         {
    //                                 $strhtml .='<table class="table1 table-responsive">
    //                                 <tr>
    //                                     <th style="width: 5%;">Item No:</th>
    //                                     <td style="width: 3%;"></td>
    //                                     <th style="width: 5%;">Item Description:</th>
    //                                     <td style="width: 70%;">' . $exsnm . '</td>
    //                                 </tr>
    //                             </table>

    //                                 <table class="table3 table-bordered table-responsive">
    //                                     <tr>
    //                                         <th style="width: 5%; border-color: black;">Sr. No</th>
    //                                         <th style="width: 45%; border-color: black;">Particulars</th>
    //                                         <th style="width: 29%; border-color: black;">Formula</th>
    //                                         <th style="width: 29%; border-color: black;">Quantity</th>
    //                                         <th style="width: 7%; border-color: black;">dyE Checked Date</th>
    //                                         <th style="width: 5%; border-color: black;">Check</th>
    //                                         <th style="width: 7%; border-color: black;">Checked Date</th>
    //                                     </tr>
    //                                     <tr>
    //                                         <td style="width: 5%; border-color:black;">'.$subloop->sr_no.'</td>
    //                                         <td style="width: 45%; border-color:black;">'.$subloop->parti.'</td>
    //                                         <td style="width: 29%; text-align: center; border-color:black;">'.$subloop->meas_id.'</td>
    //                                         <td style="width: 29%; text-align: center; border-color:black;">'.$subloop->qty.'</td>
    //                                         <td style="width: 7%; text-align: center; border-color:black;">'.$formatted_dye_dt.'</td>
    //                                         <td style="width: 5%; padding-left: 50px; padding-bottom: 30px; border-color:black;">
    //                                         <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox checkboxN" type="checkbox" name="je_check_Item['.$subloop->meas_id.']" onclick="CustomeCheckBoxSFun('.$countcombinarray.'
    //                                     );CheckIndicator('.$measidstring.');" checked>
    //                                         </td>
    //                                         <td style="width: 7%;">
    //                                             <input type="date" class="form-control customDtEmb" value="' . $SelectDtAllS . '" name="customDateInputN['. $subloop->meas_id.']" onchange="CustomeDtFunN('. $subloop->meas_id.');">
    //                                         </td>
    //                                     </tr>
    //                                 </table>';

    //                         }
    //                     }
    //                     else{

    //                             if ($subloop->formula == null) {
    //                                 $strhtml .='<table class="table1 table-responsive">
    //                                                 <tr>
    //                                                     <th style="width: 5%;">Item No:</th>
    //                                                     <td style="width: 3%;"></td>
    //                                                     <th style="width: 5%;">Item Description:</th>
    //                                                     <td style="width: 70%;">' . $exsnm . '</td>
    //                                                 </tr>
    //                                             </table>
    //                                 <table class="table3 table-responsive table-bordered table-striped">
    //                                     <tr>
    //                                         <th style="width: 4%; border-color: black;">Sr. No</th>
    //                                         <th style="width: 30%; border-color: black;">Particulars</th>
    //                                         <th style="width: 7%; border-color: black;">Height</th>
    //                                         <th style="width: 7%; border-color: black;">Number</th>
    //                                         <th style="width: 7%; border-color: black;">Length</th>
    //                                         <th style="width: 7%; border-color: black;">Breadth</th>
    //                                         <th style="width: 7%; border-color: black;">Quantity</th>
    //                                         <th style="width: 7%; border-color: black;">dyE Checked Date</th>
    //                                         <th style="width: 7%; border-color: black;">Check</th>
    //                                         <th style="width: 5%; border-color: black;">Checked Date</th>
    //                                     </tr>
    //                                     <tr>
    //                                         <td style="width: 4%; border-color: black;">'.$subloop->sr_no.'</td>
    //                                         <td style="width: 30%; border-color: black;">'.$subloop->parti.'</td>
    //                                         <td style="width: 7%; border-color: black;">'.$subloop->number.'</td>
    //                                         <td style="width: 7%; border-color: black;">'.$subloop->length.'</td>
    //                                         <td style="width: 7%; border-color: black;">'.$subloop->breadth.'</td>
    //                                         <td style="width: 7%; border-color: black;">'.$subloop->height.'</td>
    //                                         <td style="width: 7%; border-color: black;">'.$subloop->qty.'</td>
    //                                         <td style="width: 7%; text-align: center; border-color:black;">'.$formatted_dye_dt.'</td>
    //                                         <td style="width: 5%; padding-left: 50px; padding-bottom: 30px; border-color:black;">
    //                                         <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox checkboxN" type="checkbox" name="je_check_Item['.$subloop->meas_id.']" onclick="CustomeCheckBoxSFun('.$countcombinarray.');CheckIndicator('.$measidstring.');" unchecked>
    //                                         </td>
    //                                         <td style="width: 7%; border-color: black;">
    //                                             <input type="date" class="form-control customDtEmb" value="' . $SelectDtAllS . '" name="customDateInputN['. $subloop->meas_id.']" onchange="CustomeDtFunN('. $subloop->meas_id.');">
    //                                         </td>
    //                                     </tr>
    //                                 </table>';

    //                             }
    //                             else
    //                             {
    //                                 $strhtml .='<table class="table1 table-responsive">
    //                                                 <tr>
    //                                                     <th style="width: 5%;">Item No:</th>
    //                                                     <td style="width: 3%;"></td>
    //                                                     <th style="width: 5%;">Item Description:</th>
    //                                                     <td style="width: 70%;">' . $exsnm . '</td>
    //                                                 </tr>
    //                                             </table>
    //                                             <table class="table3 table-bordered table-responsive">
    //                                                 <tr>
    //                                                     <th style="width: 5%; border-color: black;">Sr. No</th>
    //                                                     <th style="width: 45%; border-color: black;">Particulars</th>
    //                                                     <th style="width: 29%; border-color: black;">Formula</th>
    //                                                     <th style="width: 29%; border-color: black;">Quantity</th>
    //                                                     <th style="width: 7%; border-color: black;">dyE Checked Date</th>
    //                                                     <th style="width: 5%; border-color: black;">Check</th>
    //                                                     <th style="width: 7%; border-color: black;">Checked Date</th>
    //                                                 </tr>
    //                                                 <tr>
    //                                                     <td style="width: 5%; border-color:black;">'.$subloop->sr_no.'</td>
    //                                                     <td style="width: 45%; border-color:black;">'.$subloop->parti.'</td>
    //                                                     <td style="width: 29%; text-align: center; border-color:black;">'.$subloop->formula.'</td>
    //                                                     <td style="width: 29%; text-align: center; border-color:black;">'.$subloop->qty.'</td>
    //                                                     <td style="width: 7%; text-align: center; border-color:black;">'.$formatted_dye_dt.'</td>
    //                                                     <td style="width: 5%; padding-left: 50px; padding-bottom: 30px; border-color:black;">
    //                                                     <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox checkboxN" type="checkbox" name="je_check_Item['.$subloop->meas_id.']" onclick="CustomeCheckBoxSFun('.$countcombinarray.');CheckIndicator('.$measidstring.');" unchecked>
    //                                                     </td>
    //                                                     <td style="width: 7%;">
    //                                                         <input type="date" class="form-control customDtEmb" value="' . $SelectDtAllS . '" name="customDateInputN['. $subloop->meas_id.']" onchange="CustomeDtFunN('. $subloop->meas_id.');">
    //                                                     </td>
    //                                                 </tr>
    //                                             </table> ';
    //                             }
    //                     }
    //                 }
    //     }
    //         else{
    //             if ( in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016",
    //             "002017", "002023", "002024", "003351", "003352", "003878"])
    //             )
    //             {
    //             // dd($subloop);
    //             //$subloop
    //             $array = (array)$subloop;
    //             $keys = array_keys($array);
    //             if( $keys[4] === 'sr_no')
    //             { }
    //             else
    //             {
    //             $itemnosteel=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('t_item_no');
    //             $exsnmsteel=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('exs_nm');

    //             $itemnovalueeSteel = DB::table('bil_item')
    //             ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value_Steel'))
    //             ->where('b_item_id' , $bitemid)
    //             ->value('combined_value_Steel');
    //             // dd($itemnovalueeSteel);


    //             $rcmbrid=$subloop->rc_mbr_id;
    //             $rccmbr=DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->value('rcc_member');
    //             $mbrparti=DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->value('member_particulars');
    //             $no_member=DB::table('bill_rcc_mbr')->where('rc_mbr_id' , $rcmbrid)->value('no_of_members');

    //             $formattedDatesteel=$subloop->date_meas;

    //             $steelmeasdate = date("d-m-Y", strtotime($formattedDatesteel));

    //             //dd($itemid);
    //             // dd($subloop);
    //             if($subloop !==null){
    //             $subloop->meas_id='';

    //             if( $subloop->ee_check==1)
    //             {
    //             // $strhtmlsteelhtml= $this->Steelhtml( $subloop ,$SelectDtAllS,$countcombinarray,$rcmbrid,$rccmbr,$mbrparti,$no_member);
    //             // //dd($strhtmlsteelhtml);
    //             // $strhtmlsteel .= $strhtmlsteelhtml;

    //             $strhtmlsteel .=
    //             '<div class="container1"><table class="table1 table-responsive" style="background-color: lightpink;"><tr><th  style="width: 5%; ">Item No:</th><td  style="width: 3%;  ">' . $itemnovalueeSteel . '</td>
    //             <th  style="width: 5%; ">Item Description:</th><td  style="width: 70%; ">' . $exsnmsteel . '</td>
    //             </tr></table></div>';
    //             $strhtmlsteel .= ' <table class="table3 table-responsive table-bordered table-striped">
    //                                 <tr style="text-align: center; background-color: lightgray;">
    //                                     <th style="width: 5%; border-color: black;">Sr No</th>
    //                                         <td style="width: 3%; border-color: black;">' . $subloop->bar_sr_no . '</td>
    //                                     <th style="width: 10%; border-color: black;">RCC Member:</th>
    //                                         <td style="width: 30%; border-color: black;">' . $rccmbr . '</td>
    //                                     <th style="width: 15%; border-color: black;">Member Particular:</th>
    //                                     <td style="width: 40%; border-color: black;">' . $mbrparti . '</td>
    //                                     <th style="width: 15%; border-color: black;">No Of Members:</th>
    //                                         <td style="width: 20%; border-color: black;">' . $no_member . '</td>
    //                                 </tr>
    //                             </table>
    //                             <table class="table table-responsive table-bordered table-striped">
    //                                 <tr>
    //                                     <th style="width: 3%;">Sr No</th>
    //                                     <th style="width: 10%;">Bar Particulars</th>
    //                                     <th style="width: 5%;">No of Bars</th>
    //                                     <th style="width: 5%;">Length of Bars</th>
    //                                     <th style="width: 5%;">6mm</th>
    //                                     <th style="width: 5%;">8mm</th>
    //                                     <th style="width: 5%;">10mm</th>
    //                                     <th style="width: 5%;">12mm</th>
    //                                     <th style="width: 5%;">16mm</th>
    //                                     <th style="width: 5%;">20mm</th>
    //                                     <th style="width: 5%;">25mm</th>
    //                                     <th style="width: 5%;">28mm</th>
    //                                     <th style="width: 5%;">32mm</th>
    //                                     <th style="width: 5%;">36mm</th>
    //                                     <th style="width: 5%;">40mm</th>
    //                                     <th style="width: 7%;">dyE Checked Date</th>
    //                                     <th style="width: 5%;">Check</th>
    //                                     <th style="width: 5%;">Checked Date</th>
    //                                 </tr>
    //                                     <tr>
    //                                         <td style="width: 5%;">' . $subloop->bar_sr_no . '</td>
    //                                         <td style="width: 10%;">' . $subloop->bar_particulars . '</td>
    //                                         <td style="width: 5%;">' . $subloop->no_of_bars . '</td>
    //                                         <td style="width: 5%;">' . $subloop->bar_length . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam6 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam8 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam10 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam12 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam16 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam20 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam25 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam28 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam32 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam36 . '</td>
    //                                         <td style="width: 5%;">' . $subloop->ldiam40 . '</td>
    //                                         <td style="width: 7%;">' . $steelmeasdate . '</td>
    //                                         <td style="width: 5%; padding-left: 30px;">
    //                                             <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Steel[' . $subloop->steelid . ']."  onchange="CustomeCheckBoxSFun('.$countcombinarray.');" checked>
    //                                         </td>
    //                                         <td>
    //                                             <input type="date" class="form-control customDt"  value="' .$subloop->ee_chk_dt . '" name="customDateInputS['. $subloop->steelid.']" onchange="CustomeDtSFun('. $subloop->steelid.' );">
    //                                         </td>
    //                                     </tr>
    //                             </table>';


    //             }
    //             else{
    //                 $strhtmlsteel .=
    //                 '<div class="container1"><table class="table1 table-responsive" style="background-color: lightpink;"><tr><th  style="width: 5%; ">Item No:</th><td  style="width: 3%;  ">' . $itemnovalueeSteel . '</td>
    //                 <th  style="width: 5%; ">Item Description:</th><td  style="width: 70%; ">' . $exsnmsteel . '</td>
    //                 </tr></table></div>';
    //                 $strhtmlsteel .= '
    //                         <table class="table3 table-responsive table-bordered table-striped">
    //                         <tr style="text-align: center; background-color: lightgray;">
    //                             <th style="width: 5%; border-color: black;">Sr No</th>
    //                                 <td style="width: 3%; border-color: black;">' . $subloop->bar_sr_no . '</td>
    //                             <th style="width: 10%; border-color: black;">RCC Member:</th>
    //                                 <td style="width: 30%; border-color: black;">' . $rccmbr . '</td>
    //                             <th style="width: 15%; border-color: black;">Member Particular:</th>
    //                             <td style="width: 40%; border-color: black;">' . $mbrparti . '</td>
    //                             <th style="width: 15%; border-color: black;">No Of Members:</th>
    //                                 <td style="width: 20%;">' . $no_member . '</td>
    //                         </tr>
    //                         </table>
    //                         <table class="table table-responsive table-bordered table-striped">
    //                         <tr>
    //                         <th style="width: 3%;">Sr No</th>
    //                         <th style="width: 10%;">Bar Particulars</th>
    //                         <th style="width: 5%;">No of Bars</th>
    //                         <th style="width: 5%;">Length of Bars</th>
    //                         <th style="width: 5%;">6mm</th>
    //                         <th style="width: 5%;">8mm</th>
    //                         <th style="width: 5%;">10mm</th>
    //                         <th style="width: 5%;">12mm</th>
    //                         <th style="width: 5%;">16mm</th>
    //                         <th style="width: 5%;">20mm</th>
    //                         <th style="width: 5%;">25mm</th>
    //                         <th style="width: 5%;">28mm</th>
    //                         <th style="width: 5%;">32mm</th>
    //                         <th style="width: 5%;">36mm</th>
    //                         <th style="width: 5%;">40mm</th>
    //                         <th style="width: 7%;">dyE Checked Date</th>
    //                         <th style="width: 5%;">Check</th>
    //                         <th style="width: 5%;">Checked Date</th>
    //                     </tr>

    //                         <tr>
    //                             <td style="width: 5%;">' . $subloop->bar_sr_no . '</td>
    //                             <td style="width: 10%;">' . $subloop->bar_particulars . '</td>
    //                             <td style="width: 5%;">' . $subloop->no_of_bars . '</td>
    //                             <td style="width: 8%;">' . $subloop->bar_length . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam6 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam8 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam10 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam12 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam16 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam20 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam25 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam28 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam32 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam36 . '</td>
    //                             <td style="width: 5%;">' . $subloop->ldiam40 . '</td>
    //                             <td style="width: 7%;">' . $steelmeasdate . '</td>
    //                             <td style="width: 5%; padding-left: 30px;">
    //                                 <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox" type="checkbox" name="je_check_Steel[' . $subloop->steelid . ']."  onclick="CustomeCheckBoxSFun('.$countcombinarray.'); " unchecked>
    //                             </td>
    //                             <td>
    //                                 <input type="date" class="form-control customDt" value="' .$subloop->ee_chk_dt . '"   name="customDateInputS['. $subloop->steelid.']" onchange="CustomeDtSFun('. $subloop->steelid.');">
    //                             </td>
    //                         </tr>
    //                     </tbody>
    //                 </table>
    //             </div>';

    //             }

    //             }
    //             }
    //             }
    //             else
    //             {
    //                 $measidstring = "'$subloop->meas_id'";
    //             $subloop->steelid='';
    //             // $measidsnormal=$subloop->meas_id;
    //             $itemno=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('t_item_no');
    //             $titemnovaluee = DB::table('bil_item')
    //             ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'))
    //             ->where('b_item_id' , $bitemidss)
    //             ->value('combined_value');
    //             //dd($titemnovaluee);
    //             $formatted_dye_dt = date("d-m-Y", strtotime($subloop->dyE_chk_dt));


    //             $exsnm=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('exs_nm');
    //             //=============================================================================================================================================================================
    //             if($subloop->ee_check==1){
    //             if ($subloop->formula == null) {
    //                 $strhtml .= '  <table class="table1 table-responsive">
    //                         <tr>
    //                             <th style="width: 5%;">Item No:</th>
    //                             <td style="width: 3%;">' . $titemnovaluee . '</td>
    //                             <th style="width: 5%;">Item Description:</th>
    //                             <td style="width: 70%;">' . $exsnm . '</td>
    //                         </tr>
    //                     </table>
    //                     <table class="table3 table-responsive table-bordered table-striped">
    //                         <tr>
    //                             <th style="width: 4%; border-color: black;">Sr. No</th>
    //                             <th style="width: 30%; border-color: black;">Particulars</th>
    //                             <th style="width: 7%; border-color: black;">Height</th>
    //                             <th style="width: 7%; border-color: black;">Number</th>
    //                             <th style="width: 7%; border-color: black;">Length</th>
    //                             <th style="width: 7%; border-color: black;">Breadth</th>
    //                             <th style="width: 7%; border-color: black;">Quantity</th>
    //                             <th style="width: 7%; border-color: black;">dyE Checked Date</th>
    //                             <th style="width: 5%; border-color: black;">Check</th>
    //                             <th style="width: 5%; border-color: black;">Checked Date</th>
    //                         </tr>
    //                         <tr>
    //                             <td style="width: 4%; border-color: black;">'.$subloop->sr_no.'</td>
    //                             <td style="width: 30%; border-color: black;">'.$subloop->parti.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->number.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->length.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->breadth.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->height.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->qty.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$formatted_dye_dt.'</td>
    //                             <td style="width: 5%; padding-left: 50px; padding-bottom: 30px; border-color:black;">
    //                             <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox checkboxN" type="checkbox" name="je_check_Item['.$subloop->meas_id.']" onclick="CustomeCheckBoxSFun('.$countcombinarray.');CheckIndicator('.$measidstring.');" checked>
    //                             </td>
    //                             <td style="width: 7%; border-color: black;">
    //                                 <input type="date" class="form-control customDtEmb" value="'.$subloop->ee_chk_dt.'" name="customDateInputN['.$subloop->meas_id.']" onchange="CustomeDtFunN('.$subloop->meas_id.');">
    //                             </td>
    //                         </tr>
    //                     </table>
    //               ';

    //             }
    //             else
    //             {
    //                 $strhtml .= '
    //                     <table class="table1 table-responsive">
    //                         <tr>
    //                             <th style="width: 5%;">Item No:</th>
    //                             <td style="width: 3%;">' . $titemnovaluee . '</td>
    //                             <th style="width: 5%;">Item Description:</th>
    //                             <td style="width: 70%;">' . $exsnm . '</td>
    //                         </tr>
    //                     </table>

    //                     <table class="table3 table-bordered table-responsive">
    //                         <tr>
    //                             <th style="width: 5%; border-color: black;">Sr. No</th>
    //                             <th style="width: 45%; border-color: black;">Particulars</th>
    //                             <th style="width: 29%; border-color: black;">Formula</th>
    //                             <th style="width: 7%; border-color: black;">Quantity</th>
    //                             <th style="width: 7%; border-color: black;">dyE Checked Date</th>
    //                             <th style="width: 5%; border-color: black;">Check</th>
    //                             <th style="width: 7%; border-color: black;">Checked Date</th>
    //                         </tr>
    //                         <tr>
    //                             <td style="width: 5%; border-color: black;">'.$subloop->sr_no.'</td>
    //                             <td style="width: 45%; border-color: black;">'.$subloop->parti.'</td>
    //                             <td style="width: 29%; border-color: black;">'.$subloop->formula.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->qty.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$formatted_dye_dt.'</td>
    //                             <td style="width: 5%; border-color: black; padding-left: 50px; padding-bottom: 30px; ">
    //                                 <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox checkboxN" type="checkbox" name="je_check_Item[' .$subloop->meas_id. ']" onclick="CustomeCheckBoxSFun(' . $countcombinarray . ');CheckIndicator('.$measidstring.');" checked>
    //                             </td>
    //                             <td style="width: 7%; border-color: black;">
    //                                 <input type="date" class="form-control customDtEmb" value="' .$subloop->ee_chk_dt . '" name="customDateInputN['. $subloop->meas_id.']" onchange="CustomeDtFunN('. $subloop->meas_id.');">
    //                             </td>
    //                         </tr>
    //                     </table> ';

    //             }
    //             }
    //             else{
    //             if ($subloop->formula == null) {
    //                 $strhtml .= '  <table class="table1 table-responsive">
    //                 <tr>
    //                     <th style="width: 5%;">Item No:</th>
    //                     <td style="width: 3%;">' . $titemnovaluee . '</td>
    //                     <th style="width: 5%;">Item Description:</th>
    //                     <td style="width: 70%;">' . $exsnm . '</td>
    //                 </tr>
    //             </table>
    //                 <table class="table3 table-bordered table-striped table-responsive" style="border-right: 1px solid black;">
    //                         <tr>
    //                             <th style="width: 4%; border-color: black;">Sr. No </th>
    //                             <th style="width: 30%; border-color: black;">Particulars</th>
    //                             <th style="width: 7%; border-color: black;">Number</th>
    //                             <th style="width: 7%; border-color: black;">Length</th>
    //                             <th style="width: 7%; border-color: black;">Breadth</th>
    //                             <th style="width: 7%; border-color: black;">Height</th>
    //                             <th style="width: 7%; border-color: black;">Quantity</th>
    //                             <th style="width: 7%; border-color: black;">dyE Checked Date</th>
    //                             <th style="width: 5%; border-color: black;">Check</th>
    //                             <th style="width: 7%; border-color: black;">Checked Date</th>
    //                         </tr>
    //                         <tr>
    //                             <td style="width: 4%; border-color: black;">'.$subloop->sr_no.'</td>
    //                             <td style="width: 30%; border-color: black;">'.$subloop->parti.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->number.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->length.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->breadth.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->height.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$subloop->qty.'</td>
    //                             <td style="width: 7%; border-color: black;">'.$formatted_dye_dt.'</td>
    //                             <td style="width: 5%; padding-left: 50px; padding-bottom: 30px; border-color:black;">
    //                             <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox checkboxN" type="checkbox" name="je_check_Item['.$subloop->meas_id.']" onclick="CustomeCheckBoxSFun('.$countcombinarray.');CheckIndicator('.$measidstring.');" unchecked>
    //                             </td>
    //                             <td style="width: 7%; border-color: black;">
    //                                 <input type="date" class="form-control customDtEmb" value="'.$subloop->ee_chk_dt.'" name="customDateInputN['.$subloop->meas_id.']" onchange="CustomeDtFunN('.$subloop->meas_id.');">
    //                             </td>
    //                         </tr>
    //                 </table> ';

    //             }
    //             else
    //             {
    //                 $strhtml .= '
    //                 <table class="table1 table-responsive">
    //                     <tr>
    //                         <th style="width: 5%;">Item No:</th>
    //                         <td style="width: 3%;">' . $titemnovaluee . '</td>
    //                         <th style="width: 5%;">Item Description:</th>
    //                         <td style="width: 70%;">' . $exsnm . '</td>
    //                     </tr>
    //                 </table>
    //                 <table class="table3 table-bordered table-responsive">
    //                     <tr>
    //                         <th style="width: 5%; border-color: black;">Sr. No </th>
    //                         <th style="width: 45%; border-color: black;">Particulars</th>
    //                         <th style="width: 29%; border-color: black;">formula</th>
    //                         <th style="width: 29%; border-color: black;">Quantity</th>
    //                         <th style="width: 7%; border-color: black;">dyE Checked Date</th>
    //                         <th style="width: 5%; border-color: black;">Check</th>
    //                         <th style="width: 7%; border-color: black;">Checked Date</th>
    //                     </tr>
    //                     <tr>
    //                         <td style="width: 5%; border-color:black;">'.$subloop->sr_no.'</td>
    //                         <td style="width: 45%; border-color:black;">'.$subloop->parti.'</td>
    //                         <td style="width: 29%; text-align: center; border-color:black;">'.$subloop->formula.'</td>
    //                         <td style="width: 29%; text-align: center; border-color:black;">'.$subloop->qty.'</td>
    //                         <td style="width: 7%; border-color: black;">'.$formatted_dye_dt.'</td>
    //                         <td style="width: 5%; padding-left: 50px; padding-bottom: 30px; border-color:black;">
    //                         <input id="RselectAll" class="checkboxS form-check-input form-check custom-checkbox checkboxN" type="checkbox" name="je_check_Item['.$subloop->meas_id.']" onclick="CustomeCheckBoxSFun('.$countcombinarray.');CheckIndicator('.$measidstring.');" unchecked>
    //                         </td>
    //                         <td style="width: 7%;">
    //                             <input type="date" class="form-control customDtEmb" value="' .$subloop->ee_chk_dt . '" name="customDateInputN['. $subloop->meas_id.']" onchange="CustomeDtFunN('. $subloop->meas_id.');">
    //                         </td>
    //                     </tr>
    //                 </table>
    //           ';
    //             }
    //             }
    //             }
    //         }
    //     }


    //     return response()->json(['CheckedSelectAll'=>$CheckedSelectAll,'dyedate'=> $dyedate,'normaldesc' => $normaldesc, 'strhtml' => $strhtml , 'steeldesc' => $steeldesc , 'strhtmlsteel' => $strhtmlsteel,'formattedDate'=>$formattedDate,'$bitemsid'=>$bitemsid,'$strhtmlformula'=>$strhtmlformula,'countcombinarray'=>$countcombinarray,
    //                          "normaldatacount"=>$normaldatacount,"measidsnormal"=>$measidsnormal,"steelid"=>$steelid,"recordmscheckeddata"=>$recordmscheckeddata,'SelectDtAllS'=>$SelectDtAllS,'Count_Chked_Emb_Stl'=>$Count_Chked_Emb_Stl]);
    //     }
    // }

    public function ItemwiseExecutiveCheckFun(Request $request)
    {
        //dd($request);
        $tbillid = $request->input('tbillid_value');
        // dd($tbillid);
        $recno=$request->input('recordEntryNo');
        //dd($recno);
        $itemid =$request->input('itemid_value');
        //dd($itemid);
        $WorkIdv =$request->input('WorkId_value');

        $itemno=$request->input('combined_value');
        //dd($itemno);

        $titemno = DB::table('bil_item')
        ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc','exec_qty','item_unit','cur_amt','bill_rt','ratecode','cur_qty')
        ->where('t_bill_id', '=', $tbillid)
        ->where('t_item_no','=',$itemno)
        ->get();
        //dd($titemno);

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
            $bitemid=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('t_item_no' , $itemNo)
            ->where('sub_no', $subno)->value('b_item_id');
            //dd($bitemid);
        }
        else
        {
            $bitemid=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('t_item_no' , $itemNo)
            ->value('b_item_id');
        }


        $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->value('item_id');
        // dd($itemid);
            if (
            in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016",
                                        "002017", "002023", "002024", "003351", "003352", "003878"])
                                        )
            {

            $stldata = DB::table('stlmeas')
            ->select('stlmeas.*')
            ->join('bill_rcc_mbr', 'stlmeas.rc_mbr_id', '=', 'bill_rcc_mbr.rc_mbr_id')
            ->where('bill_rcc_mbr.t_bill_id', $tbillid)
            ->where('bill_rcc_mbr.b_item_id', $bitemid)
            ->get();

            //  dd($stldata,$tbillid,$bitemid);
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

                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemid) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemid);
                })
                ->get();

            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemid)->pluck('rc_mbr_id')->toArray();

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
                // dd($stldata);
                    foreach ($stldata as $bar) {
                        if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                        //    dd($bar);
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

                $embssumarry=DB::table('embs')->where('b_item_id' , $bitemid)->where('t_bill_id' , $tbillid)->get();

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


    public function SaveBtnExecutive(Request $request)
    {
        try
        {
         //dd($request);

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

        //$je_check_Steelkey1=$request->input('je_check_Steel');
        //dd($je_check_Steelkey1);

        // Access the session variable set in the previous function
        $storedBillDate = $request->session()->get('billDate');
        //dd($storedBillDate);
        $commonheader=$this->commongotoembcontroller($WorkId , $steelid,$storedBillDate,$tbillid,$recnovalues);
        //dd($commonheader);

        $workDetails1 = DB::table('workmasters')
        ->select('Work_Nm', 'Sub_Div', 'Agency_Nm', 'Work_Id', 'Wo_Dt','Period','WO_No','Stip_Comp_Dt')
        ->where('Work_Id', '=', $WorkId)
        ->first();

        //$je_check_Steel_Headingkey=$request->input('je_check_Steel_Heading');


        $eeqty=$request->input('eeqty');
        $countcombinarray=$request->input('countcombinarray');
            //dd($countcombinarray);

        $btnsave=$request->input('btnsave');

        //$btnall=$request->input('btnall');

        $BtnRevert=$request->input('BtnRevert');
            //dd($btnsave , $btnall);

        $titemno = DB::table('bil_item')
        ->select(DB::raw('COALESCE(CONCAT(t_item_no, sub_no), t_item_no, sub_no) as combined_value'), 'item_desc', 'exec_qty', 'item_unit')
        ->where('t_bill_id', '=', $tbillid)
        ->get();
        $Recordeno = DB::table('recordms')
        ->select('Record_Entry_No')
        ->where('t_bill_id', '=', $tbillid)
        ->get();

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

            $Emb_Measid = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $recdt)->pluck('meas_id')->toArray();
            //dd($Emb_Measid);
            $Stlmeas_Stlid = DB::table('stlmeas')
            ->where('t_bill_id', $tbillid)
            ->where('date_meas', $recdt)
            ->pluck('steelid')
            ->toArray();
            //dd($Stlmeas_Stlid);

            //dd($je_check_Steelkey1);
            // if($je_check_Steelkey1 === null  ){
            //     // dd($je_check_Steelkey1);
            //     foreach($Stlmeas_Stlid as $jecheck){
            //     //dd($jecheck);
            //         DB::table('stlmeas')
            //         ->where('steelid', $jecheck)
            //         ->update(['ee_check' => 0]);
            //     }
            // }
            // else{
            //     $je_check_Steelkey=array_keys($je_check_Steelkey1);
            //     $unchked_stl = array_diff($Stlmeas_Stlid , $je_check_Steelkey);

            //     // dd($Stlmeas_Stlid,$je_check_Steelkey);
            //     $unchked_stl = array_diff($Stlmeas_Stlid , $je_check_Steelkey);
            //     //dd($unchked_stl);
            //         foreach($unchked_stl as $jecheck){
            //             // dd($jecheck);
            //             DB::table('stlmeas')
            //             ->where('steelid', $jecheck)

            //             ->update(['ee_check' => 0]);
            //         }
            // }
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

            $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->where('measurment_dt' , $recdt)->get();
            //dd($NormalDb);

            $StillDb = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdt)->get();
            //dd($StillDb);

            $combinarray = $NormalDb->concat($StillDb);
            //dd($combinarray);

            //Count of combine data...
            $countcombinarray=count($combinarray);
            //dd($countcombinarray);
            $Stldyechkcount1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('date_meas' , $recdt)->where('ee_check',"=",1)->get();
            $Stldyechkcount=count($Stldyechkcount1);
            //dd($Stldyechkcount);

            $EmbdyeChkCount = DB::table('embs')
            ->where('t_bill_id', $tbillid)
            ->where('measurment_dt', $recdt)
            ->where('ee_check', "=", 1)
            ->count();
            //  dd($EmbdyeChkCount);

            $Count_Chked_Emb_Stl= $EmbdyeChkCount + $Stldyechkcount;
           // dd($Count_Chked_Emb_Stl , $countcombinarray);
            if ($Count_Chked_Emb_Stl === $countcombinarray) {
            // dd($jecheck);
                DB::table('recordms')
                ->where('Record_Entry_Id', $recenid)
                ->update(['ee_check' => 1]);
                // dd("Updated normal to 0");
            }
            else{
            // dd($jecheck);
                DB::table('recordms')
                ->where('Record_Entry_Id', $recenid)
                ->update(['ee_check' => 0]);
            // dd("Updated normal to 0");
            }

            //Saving Checked CheckBoxes to table....
            $recenid= $recenid ?: [];
            if($recenid){
                //Updating Steel checkbox...
                // // dd($je_check_Steelkey);
                // $je_check_Steelkey=$request->input('je_check_Steel');

                // if($je_check_Steelkey){
                // $countje=count($je_check_Steelkey);
                // //dd($countje);
                //     for ($i=0; $i<$countje; $i++) {
                //     $jecheckv = array_keys($je_check_Steelkey);
                //     $updateSQL = DB::table('stlmeas')
                //         ->where('steelid', $jecheckv[$i])
                //         ->update(['ee_check' => 1]);
                //     }
                // }
                $je_check_Itemkey=$request->input('je_check_Item');
                //dd($je_check_Itemkey);
                //Udating EMB Data Checkbox...
                if($je_check_Itemkey){

                    //dd($je_check_Item);
                    $je_check_Item=array_keys($je_check_Itemkey);
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


                  foreach ($eeqty as $measId => $qty) {
                      
                      if (is_array($je_check_Itemkey) && array_key_exists($measId, $je_check_Itemkey1)) {
                              DB::table('embs')
                            ->where('meas_id', $measId)
                            ->update(['ee_chk_qty' => $qty]);
                      }
               }
               
                    }


$PartA_Amt= DB::table('bills')
->where('t_bill_id', $tbillid)
->value('c_part_a_amt');
// dd($PartA_Amt);

$PartB_Amt= DB::table('bills')
->where('t_bill_id', $tbillid)
->value('c_part_b_amt');
// dd($PartB_Amt);
$b_item_amt=$PartA_Amt +  $PartB_Amt;


//dd($checkboxdatas);
$totalMeasAmt = [];
    $PreviSelectedCheckboxAmount = 0; // Initialize the variable to store the total amount
    $Checked_Percentage = 0;


    $bitemidDBCalculation = DB::table('embs')
    ->where('t_bill_id', $tbillid)
    ->where('ee_check',1)
    ->where('notforpayment',0)
    ->select('b_item_id','meas_id','ee_chk_qty')
    ->get();
   // dd($bitemidDBCalculation);
foreach($bitemidDBCalculation as $measdata)
{
     //dd($measdata);
    $ee_chk_tbl = DB::table('embs')
        ->where('meas_id', $measdata->meas_id)
        ->value('ee_check');
      //dd($ee_chk_tbl);
    $bitemid = DB::table('embs')
        ->where('t_bill_id', $tbillid)
        ->where('meas_id', $measdata->meas_id)
        ->value('b_item_id');
    // dd($bitemid);


        $bill_rt = DB::table('bil_item')
            ->where('t_bill_id', $tbillid)
            ->where('b_item_id', $bitemid)
            ->value('bill_rt');
    
        $meas_amt = $bill_rt * $measdata->ee_chk_qty;
        $PreviSelectedCheckboxAmount += $meas_amt;
   
     //dd($PreviSelectedCheckboxAmount);
     //dd($notforpayment);


   
    }
     //dd($PreviSelectedCheckboxAmount,$amount);
    $checked_mead_amt = $PreviSelectedCheckboxAmount; // Assign $amount to 'checked_mead_amt'

//dd($checked_mead_amt);
$Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
// Format the result to have only three digits after the decimal point
$Checked_Percentage = number_format($Checked_Percentage1, 2);

    DB::table('bills')
        ->where('t_bill_Id', $tbillid)
        ->update(['EEChk_Amt' => $checked_mead_amt , 'EEChk_percentage' => $Checked_Percentage]);

//dd($eeqty);

         if(!empty($eeqty))
            {
                
            $EEqty=array_keys($eeqty);
            }                    // if($je_check_Itemkey){

                    //     //dd($eeqty);
                    //     $je_check_Item=array_keys($je_check_Itemkey);
                    //     foreach($je_check_Item as $jecheckid){

                    //         if (array_key_exists($jecheckid, $eeqty)) {
                    //             dd($eeqty);
                    //             // If it exists, update the corresponding eeqty value
                    //             DB::table('embs')
                    //         ->where('meas_id', $jecheck)
                    //         ->update(['ee_chk_qty' => $eeqty[$jecheckid]]);
                              
                    //         }
                           
                           
                    //     }
                    //     // dd("Normal Data Checkbox is Update");
                    // }
            }

            //Saving Steel Date to database =========================================================================================
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
                        ->update(['ee_chk_dt' => $value]);
                    }
                }
            }
            //Saving Embs Date to database =========================================================================================
            $customDateInputN=$request->input('customDateInputN');
                //dd($customDateInputN);
                //dd($customDateInputN);
            //     if($customDateInputN){
            //     foreach ($customDateInputN as $key => $value) {
            //         //dd($key, $value);
            //         if($value){
            //             DB::table('embs')
            //             ->where('meas_id', $key)
            //             ->update(['ee_chk_dt' => $value]);
            //         }

            //     }
            // }

            $recno = DB::table('recordms')
            ->where('Record_Entry_Id', $recenid)
            ->get('Record_Entry_No');
            // dd($recno);

            return $commonheader;
        }





        // Revert button Code.................
        elseif($BtnRevert==='revert')
        {
           DB::table('bills')
                 ->where('t_bill_id', $tbillid)
                 ->update(['mb_status' => 2,'mbstatus_so'=>0]
                );
                 //dd("Submteed");
              $workid=$WorkId;
              return redirect()->route('billlist', ['workid' => $workid]);
        }
    }
                catch (Exception $e) {
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
        //dd($request);
       

        $tbillid= $request->tbillid;

        $recnovalues=$request->recordentryno;

        $WorkId=$request->workid;


        $Checkboxdata=$request->checkboxdata;
        

        $Percentage=$request->percentage;

        $Amount=$request->Amount;

   
         // dd("In submit");
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

         $Emb_Measid = DB::table('embs')->where('t_bill_id' , $tbillid)->pluck('meas_id')->toArray();
         //dd($Emb_Measid);
         $Stlmeas_Stlid = DB::table('stlmeas')
         ->where('t_bill_id', $tbillid)
         ->pluck('steelid')
         ->toArray();
         //dd($Stlmeas_Stlid);

             $NormalDb = DB::table('embs')->where('t_bill_id' , $tbillid)->get();
             //dd($NormalDb);

             $StillDb1 = DB::table('stlmeas')->where('t_bill_id' , $tbillid)->get();
             $StillDbcount=count($StillDb1);
         //dd($StillDb);
          
       
             //dd("Less than 5");
             $workid=$WorkId;

         // dd($workid,$percentageTextBox,$tbillid);

            
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


            //dd('Ok');
            DB::table('embs')->where('t_bill_id' , $tbillid)->update([
                'ee_check' => 0,
              ]);
        }
         
         
         
         $Percentage = str_replace('%', '', $Percentage);
         $Amount = str_replace(',' , '' , $Amount);
    
    // Now you can use $percentage as a clean number
    // For example, you can convert it to a float if needed
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

        
        
        return response()->json(['script' => $script]);
    }
    catch (\Exception $e) {
        // Log the exception or handle it as needed
        Log::error('Error in SubmitAllEE: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred ' . $e->getMessage()], 500);
    }
}





    

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

        $PartA_Amt= DB::table('bills')
        ->where('t_bill_id', $tbillid)
        ->value('c_part_a_amt');
        // dd($PartA_Amt);

        $PartB_Amt= DB::table('bills')
        ->where('t_bill_id', $tbillid)
        ->value('c_part_b_amt');
        // dd($PartB_Amt);



        $b_item_amt=$PartA_Amt +  $PartB_Amt;
        //   dd($b_item_amt);

        $fivePercent = $b_item_amt * 0.05;

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



        // foreach($bitemids as $bitemid){
        //     $measid=$bitemid->meas_id;
        //     //dd($measid);

        //     $qty = DB::table('embs')
        //         ->where('t_bill_id', $tbillid)
        //         ->where('meas_id', $measid)
        //         ->value('qty');
        //     //dd($qty);

        //     $bill_rt = DB::table('bil_item')
        //         ->where('t_bill_id', $tbillid)
        //         ->value('bill_rt');
        //     //dd($bill_rt);

        //     $meas_amt=$bill_rt * $qty;
        //     //dd($meas_amt);
        //     $checked_mead_amt=$checked_mead_amt+$meas_amt;
        //     //dd($checked_mead_amt);
        //     $result[]=$checked_mead_amt;
           //dd($result);
            // dd($checked_mead_amt);
            //dd($bitemid,$measid,$qty,$bill_rt,$meas_amt);
        // }
        //dd($bitemid,$measid,$qty,$bill_rt,$checked_mead_amt);
        //dd($checked_mead_amt);
        // $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
        //dd($Checked_Percentage);
        //Format the result to have only three digits after the decimal point
        // $Checked_Percentage = number_format($Checked_Percentage1, 3);
        //dd($Checked_Percentage,$result,$b_item_amt);
        //dd($Checked_Percentage);

        $checkeddata = DB::table('embs')
        ->select('meas_id' , 'ee_chk_qty')
        ->where('t_bill_id' , $tbillid)
        ->where('ee_check' , 1)
        ->where('notforpayment' , 0)
        ->get();


 $convert=new CommonHelper();
        $amount=$convert->formatIndianRupees($amount);
        // Select all checkbox......
        return response()->json(['amount'=>$amount ,'formattedPercentage'=>$formattedPercentage , 'checkeddata' => $checkeddata]);
    }
        catch (\Exception $e) 
    {
        // Log the exception or handle it as needed
        Log::error('Error in SubmitAllEE: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred ' . $e->getMessage()], 500);
    }
}


    public function PercentIndicator(Request $request)
    {
        $meas_amt=0;
        $result[]=0;
        $checked_mead_amt=0;
        //dd($request);
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
            // dd($PartB_Amt);
            $b_item_amt=$PartA_Amt +  $PartB_Amt;
            // dd($b_item_amt);

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
           // dd($tbillid,$meas_date);
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
                $ee_chk_tbl = DB::table('recordms')
                ->where('Rec_date', $meas_date)
                ->where('ee_check',1)
                ->value('ee_check');
                //dd($ee_chk_tbl);

                $qty = DB::table('embs')
                ->where('t_bill_id', $tbillid)
                ->where('measurment_dt', $meas_date)
                ->value('qty');
                //dd($qty);

                $bill_rt = DB::table('bil_item')
                ->where('b_item_id',$b_item_id)
                ->value('bill_rt');
                // /dd($bill_rt);

                if($ee_chk_tbl){
                    // $meas_amt= $meas_amt+($bill_rt * $qty);
                    $meas_amt=$bill_rt * $qty;
                    $checked_mead_amt=$amount-$meas_amt;
                    $result[]=$checked_mead_amt;
                    //dd($measid,$qty,$bill_rt,$meas_amt);
                }
                else
                {
                //     // dd($measid);

                //     $qty = DB::table('embs')
                //     ->where('t_bill_id', $tbillid)
                //     ->where('measurment_dt', $meas_date)
                //     ->value('qty');
                //     // dd($qty);

                //     $bill_rt = DB::table('bil_item')
                //         ->where('t_bill_id', $tbillid)
                //         ->value('bill_rt');
                //     //dd($bill_rt);


                    $meas_amt=$bill_rt * $qty;
                    $checked_mead_amt=$amount+$meas_amt;

                    $result[]=$checked_mead_amt;
                 //dd($measid,$qty,$bill_rt,$meas_amt);
                }
            }
             // dd($meas_amt);
            //  $checked_mead_amt= $amount + $meas_amt;
             //  dd($checked_mead_amt);
                //dd($prev_amt);
            //  $result[]=$checked_mead_amt;

             //dd($checked_mead_amt);
                $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
                //dd($Checked_Percentage);

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
                // dd($measid);
                $ee_chk_tbl = DB::table('embs')
                    ->where('meas_id', $measid)
                    ->where('ee_check',1)
                    ->value('ee_check');
                 //dd($ee_chk_tbl);
                $bitemid = DB::table('embs')
                    ->where('t_bill_id', $tbillid)
                    ->where('meas_id', $measid)
                    ->value('b_item_id');
                // dd($bitemid);

                $qty = DB::table('embs')
                    ->where('b_item_id', $bitemid)
                    ->where('meas_id', $measid)
                    ->value('qty');
                // dd($qty);

                $bill_rt = DB::table('bil_item')
                    ->where('t_bill_id', $tbillid)
                    ->where('b_item_id', $bitemid)
                    ->value('bill_rt');
                // dd( $bitemid,$qty,$bill_rt,$measid);

                if($ee_chk_tbl){
                    //dd("ee_chk_tbl");

                $meas_amt=$bill_rt * $qty;
                //     dd($meas_amt);
                    // $checked_mead_amt=$amount-$meas_amt;
                // $meas_amt= $meas_amt-($bill_rt * $qty);
                // dd($meas_amt);
                $checked_mead_amt= $amount - $meas_amt;
               // dd($meas_amt,$checked_mead_amt);
                }
                else
                {
                    //dd("Not ee_chk_tbl");
                    // $meas_amt= $meas_amt+($bill_rt * $qty);
                    // dd($meas_amt,$amount);
                    $meas_amt= $bill_rt * $qty;
                    // $checked_mead_amt= $amount + $meas_amt;
                    // dd($checked_mead_amt);
                    //dd($meas_amt);
                    $checked_mead_amt= $amount + $meas_amt;
                    //  dd($checked_mead_amt);
                    //dd($prev_amt);
                }
            }

        }
        //dd($checked_mead_amt);
        // dd($result,$qty,$bill_rt,$meas_amt);

        $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
        //dd($Checked_Percentage1,$checked_mead_amt);

        // Format the result to have only three digits after the decimal point
        $Checked_Percentage = number_format($Checked_Percentage1, 2);
        //dd($Checked_Percentage);
        // if($Checked_Percentage >= $fivePercent)
        // {
        //     // dd("In if");
        //     echo "<script>
        //         document.addEventListener('DOMContentLoaded', function() {
        //             Swal.fire({
        //                 icon: 'warning',
        //                 title: 'Warning...',
        //                 text: 'All CheckBox should be checked and Measurement Checked Dates should be filled.'
        //             });
        //         });
        //     </script>";
        // }
        // else
        // {
        //     //dd("No...5% checking is not done yet...."); $b_item_amt
        // }
        //dd("Okkk");
        return response()->json(['Checked_Percentage'=> $Checked_Percentage ,'checked_mead_amt'=>$checked_mead_amt]);
    }
    
    public function precentageloadquantity(Request $request)
{
    $tbillid=$request->tbillid;
    // dd($tbillid);
    $WorkId=$request->workid;


    $PartA_Amt= DB::table('bills')
    ->where('t_bill_id', $tbillid)
    ->value('c_part_a_amt');
    // dd($PartA_Amt);

    $PartB_Amt= DB::table('bills')
    ->where('t_bill_id', $tbillid)
    ->value('c_part_b_amt');
    // dd($PartB_Amt);
    $b_item_amt=$PartA_Amt +  $PartB_Amt;


    $checkboxdatas=$request->checkboxData;
    //dd($checkboxdatas);
 $totalMeasAmt = [];
        $PreviSelectedCheckboxAmount = 0; // Initialize the variable to store the total amount
        $Checked_Percentage = 0;


        $bitemidDBCalculation = DB::table('embs')
        ->where('t_bill_id', $tbillid)
        ->where('ee_check',1)
        ->where('ee_check',1)
        ->select('b_item_id','meas_id','ee_chk_qty')
        ->get();





        // foreach ($bitemidDBCalculation as $item) {
        //     $bitemid = $item->b_item_id;
        
        //     // Retrieve bill_rt from the database for this b_item_id
        //     $bill_rtDB = DB::table('bil_item')
        //         ->where('t_bill_id', $tbillid)
        //         ->where('b_item_id', $bitemid)
        //         ->value('bill_rt');
        
        //     // Calculate the multiplication of quantity and bill rate for this item
        //     $totalMeasAmt[$bitemid] = $item->ee_chk_qty * $bill_rtDB;
        
        //     // Accumulate the total amount for all items
        //     $PreviSelectedCheckboxAmount += $totalMeasAmt[$bitemid]; // Accumulate each item's total amount
        // }
        //dd($checkboxdatas);
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

        
        
        
    foreach($checkboxdatas as $measdata)
    {
         //dd($measid['measid']);
        $ee_chk_tbl = DB::table('embs')
            ->where('meas_id', $measdata['id'])
            ->value('ee_check');
          //dd($ee_chk_tbl);
        $bitemid = DB::table('embs')
            ->where('t_bill_id', $tbillid)
            ->where('meas_id', $measdata['id'])
            ->value('b_item_id');
        // dd($bitemid);

        $notforpayment = DB::table('embs')
        ->where('meas_id', $measdata['id'])
        ->value('notforpayment');
        // $qty = DB::table('embs')
        //     ->where('b_item_id', $bitemid)
        //     ->where('meas_id', $measid['id'])
        //     ->value('qty');
        // dd($qty);

//dd($notforpayment);

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

//dd($checked_mead_amt);
$Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
// Format the result to have only three digits after the decimal point
$Checked_Percentage = number_format($Checked_Percentage1, 2);
 //dd($Checked_Percentage,$checked_mead_amt);
 
 
 
 $convert=new CommonHelper();
        $checked_mead_amt=$convert->formatIndianRupees($checked_mead_amt);


return response()->json(['Checked_Percentage'=> $Checked_Percentage ,'checked_mead_amt'=>$checked_mead_amt]);
}


    public function funYesSubmit(Request $request,$workid,$tbillid)
    {
        DB::table('bills')
        ->where('t_bill_id', $tbillid)
        ->update(['mb_status' => 5]);
        // dd($workid);
        // dd($workid);
        return redirect()->route('billlist', ['workid' => $workid]);
    }




}
