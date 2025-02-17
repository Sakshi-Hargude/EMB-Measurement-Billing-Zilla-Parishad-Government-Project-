<?php

namespace App;

use App\Models\Subdivms;
use App\Helpers\ExcelReader;
use App\Models\Workmaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response as FacadeResponse;


class AllExcelsheet
{
    public function Excelsheet1($request)
    {
        // dd($workid);

                                if (!$request->hasFile('excel_file')) 
                                {
                                    return redirect()->back()->with('error', 'No file uploaded. Please choose an Excel file to upload.');
                                }
                            
                                // Get the uploaded file from the 'excel_file' field
                                $file = $request->file('excel_file');
                            //dd($file);
                                // Validate if the file is an Excel file
                                $isValidExcelFile = $file->isValid() && in_array($file->getClientOriginalExtension(), ['xls', 'xlsx']);
                            
                                if (!$isValidExcelFile) 
                                {
                                    // Handle invalid file format error
                                    return redirect()->back()->with('error', 'Invalid file format. Please upload an Excel file (.xls or .xlsx).');
                                }
                            
                                // Get the work ID from the request (assuming you have a form field for the work ID)
                                // $workId = $request->work_id;
                            
                                $excelReader = new ExcelReader();
                                $data[] = $excelReader->reader($file);
                                // dd($data);
                            
                                // Ensure $data is an array
                                if (!is_array($data)) {
                                    $data = [];
                                }
                                // dd($data);
        return $data;               
    }

                            


    public function Excelsheet2($request,$workid)
    {
        // dd($workid);
                                        // Check if the file was uploaded successfully
                                        if (!$request->hasFile('excel_file')) {
                                            return response()->json(['error' => 'No file uploaded. Please choose an Excel file to upload.']);
                                        }
                                    
                                        // Get the uploaded file from the 'excel_file' field
                                        $file = $request->file('excel_file');
                                    //dd( $file);
                                        // Validate if the file is an Excel file
                                        $isValidExcelFile = $file->isValid() && in_array($file->getClientOriginalExtension(), ['xls', 'xlsx']);
                                    
                                        if (!$isValidExcelFile) {
                                            // Handle invalid file format error
                                            return response()->json(['error' => 'Invalid file format. Please upload an Excel file (.xls or .xlsx).']);
                                        }
                                    
                                        // Load and read the Excel file using the ExcelReader class
                                        $excelReader = new ExcelReader();
                                    // Read data from Sheet 2
                                       $dataSheet2 = $excelReader->readersheet2($file,$workid);
                                    //    dd($dataSheet2);
        
        // insert data in table tempsheet2excels from excel sheet2

        return $dataSheet2; 
                                            // Assuming the data you want is in the 1st index (column 2)
    }



            public function Excelsheet4($request)
            
                                {

                                                    // Check if the file was uploaded successfully
                                                    if (!$request->hasFile('excel_file')) {
                                                        return response()->json(['error' => 'No file uploaded. Please choose an Excel file to upload.']);
                                                    }
                                                
                                                    // Get the uploaded file from the 'excel_file' field
                                                    $file = $request->file('excel_file');
                                                
                                                    // Validate if the file is an Excel file
                                                    $isValidExcelFile = $file->isValid() && in_array($file->getClientOriginalExtension(), ['xls', 'xlsx']);
                                                
                                                    if (!$isValidExcelFile) {
                                                        // Handle invalid file format error
                                                        return response()->json(['error' => 'Invalid file format. Please upload an Excel file (.xls or .xlsx).']);
                                                    }
                                                
                                                    // Load and read the Excel file using the ExcelReader class
                                                    $excelReader = new ExcelReader();
                                                   $dataSheet4=$excelReader->readersheet4($file);
                                                    //  dd($dataSheet4);
           return $dataSheet4; 
        }




}