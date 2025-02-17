<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function show()
    {
        //$upiId = '9011316264@ybl'; // Replace with the actual UPI ID you want to receive payments
        //$upiId = 'truptia.magdum@okhdfcbank';
        $upiId = 'pm.priyanka@postbank';
        $amount = 1; // Replace with the actual payment amount
    
        // Construct the UPI payment URL
        $paymentInfo = "upi://pay?pa=$upiId&pn=MerchantName&mc=1234&tid=transactionId&tr=uniqueReferenceId&tn=PaymentDescription&am=$amount";
        
       
       // logo image retrive from public path
        $imagePath2 = storage_path('app/public/sis-logo.png');
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;

    // create qrcode using Qrcode class
        $qrCode = QrCode::size(700)
            ->backgroundColor(255, 255, 255)
            ->color(0, 0, 0)
            ->margin(10)
            ->merge($imageSrc2, 0.5, true)
            ->generate($paymentInfo);
    // You may want to return this QR code to your view for display
    return view('Qrcode')->with('qrCode', $qrCode);
    }
}