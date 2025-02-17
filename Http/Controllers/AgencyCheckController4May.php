<?php

namespace App\Http\Controllers;

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


class AgencyCheckController extends Controller
{
   public function AgencyCnt(Request $request)
   {
        // dd($request);
        $tbillid=$request->input('t_bill_Id');
        // dd($tbillid);

        $WorkId=$request->input("workid");
        // dd($WorkId);

        $billdate=$request->input("Bill_Dt");
        // dd($billdate);


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
            $workDetails = DB::table('workmasters')
                ->select('Work_Nm', 'Sub_Div', 'Agency_Nm', 'Work_Id', 'Wo_Dt','Period','WO_No','Stip_Comp_Dt')
                ->where('Work_Id', '=', $WorkId)
                ->first();

            $fund_Hd = DB::table('workmasters')
                ->select('fundhdms.Fund_HD_M')
                ->join('fundhdms', function ($join) use ($WorkId) {
                    $join->on(DB::raw("LEFT(workmasters.F_H_Code, 4)"), '=', DB::raw("LEFT(fundhdms.F_H_CODE, 4)"))
                        ->where('workmasters.Work_Id', '=', $WorkId);
                })
                ->first();

            $recinfo=  DB::table('recordms')
                    ->where('Work_Id', '=', $WorkId)
                    ->get();
                    //dd($recinfo);

            $divName = DB::table('workmasters')
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
               //dd($RecordData->Record_Entry_No);

            $titemnoRecords = DB::table('bil_item')
                ->select('t_item_no', 'item_desc', 'exec_qty', 'ratecode', 'bill_rt')
                ->where('t_bill_id', '=', $tbillid)
                ->get();

            $Recordwise = DB::table('recordms')
            ->where('t_bill_id', '=', $tbillid)
            ->get();

            $html ='';

            $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
            $html .= '<table>';
        foreach($billitemdata as $itemdata)
        {
            $bitemId=$itemdata->b_item_id;
            //dd($bitemId);
            $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->get();
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



            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
            //dd($itemid);
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
            {
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->get();

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
            ->whereExists(function ($query) use ($bitemId) {
            $query->select(DB::raw(1))
            ->from('stlmeas')
            ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
            ->where('bill_rcc_mbr.b_item_id', $bitemId);
            })
            ->get();


            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();


            foreach ($bill_member as $index => $member) {
                //dd($member);
                    $rcmbrid=$member->rc_mbr_id;
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->get();
                //dd($memberdata);

            if ( !$memberdata->isEmpty()) {
            $html .= '<tr>';
            $html .= '<table style="border-collapse: collapse; width: 100%;  background-color: lightblue;"><thead>';
            $html .= '<th colspan="1" style="border: 1px solid black; padding: 8px;">Sr No :' . $member->member_sr_no . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px; ">RCC Member :' . $member->rcc_member . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px; ">Member Particular :' . $member->member_particulars . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black; padding: 8px;">No Of Members :' . $member->no_of_members . '</th>';
            $html .= '</thead></table>';
            $html .= '</tr>';

            foreach ($stldata as $bar) {

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


        $maxRepeatedDateEE = DB::table('embs')
        ->select('ee_chk_dt', DB::raw('COUNT(ee_chk_dt) as count'))
        ->where('Work_Id', '=', $WorkId)
        ->where('t_Bill_Id', '=', $tbillid)
        ->groupBy('ee_chk_dt')
        ->orderBy('ee_chk_dt', 'desc')
        ->first();
        // dd($maxRepeatedDate);


        $maxRepeatedDateDYE = DB::table('embs')
        ->select('dyE_chk_dt', DB::raw('COUNT(dyE_chk_dt) as count'))
        ->where('Work_Id', '=', $WorkId)
        ->where('t_Bill_Id', '=', $tbillid)
        ->groupBy('dyE_chk_dt')
        ->orderBy('dyE_chk_dt', 'desc')
        ->first();
        // dd($maxRepeatedDateDYE);


        $default_agecy_ee_dt=$maxRepeatedDateEE->ee_chk_dt;
        $default_agecy_dye_dt=$maxRepeatedDateDYE->dyE_chk_dt;

        $returnHTML = $html;
        // /dd($workDetails);


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
        //dd($sectionEngName);
        if ($sectionEngName) {
            $DBSectionEngNames[] = $sectionEngName->name;
        }
    }
                        // dd($DBSectionEngNames);
        return view('AgencyCheck',compact('DBSectionEngNames','returnHTML','billdate','workDetails','default_agecy_dye_dt','fund_Hd', 'sectionEngineer', 'divName', 'Work_Dtl', 'Recordwise', 'divNm', 'bitemid', 'FinalRecordEntryNo', 'titemnoRecords',  'embdtls', 'Item1Data', 'RecordData', 'tbillid', 'titemno', 'itemid','default_agecy_ee_dt'));
    }

    public function FunctionSubmitAgency(Request $request)
    {
        // /dd($request);
        $WorkId=$request->input('WorkId');
        $tbillid=$request->input('tbillid');
        $Agency_Chk_Dt=$request->input('date');
        //  dd($WorkId,$tbillid);

        DB::table('bills')
                ->where('Work_Id', '=', $WorkId)
                ->where('t_Bill_Id', '=', $tbillid)
                ->update([
                    'Work_Id' => $WorkId,
                    'Agency_Check'=>1,
                    'agency_Check_Date' => $Agency_Chk_Dt]);
                    // dd("Done...");
                    // AgencyCnt($WorkId,$tbillid,$Agency_Chk_Dt);

                    $workid=$WorkId;

                    DB::table('bills')
                    ->where('t_bill_id', $tbillid)
                    ->update(['mb_status' => 6]);

                    return redirect()->route('billlist', ['workid' => $workid]);


   }
}
