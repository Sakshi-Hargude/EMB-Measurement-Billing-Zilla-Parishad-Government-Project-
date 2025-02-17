<?php

namespace App\Http\Controllers; // Make sure this is the correct namespace for your controller

// use Illuminate\Http\Request;
use Illuminate\Http\Request;
use App\Request as AppRequest; // Import the App\Request class
use App\AllExcelsheet;
class Trial extends Controller
{
    public function upload(Request $request)
    {
        $excelHelper = new AllExcelsheet();
        $excelHelper->Excelsheet1($request);

        // You can also process the uploaded file here
        // $file = $request->file('file');
        // $file->store('uploads'); // Example of storing the file

        // Redirect back after processing the file
        return redirect()->back();    }
}
