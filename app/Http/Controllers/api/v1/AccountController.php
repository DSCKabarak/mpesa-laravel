<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Exception;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'amount' => 'required|string|max:50',
                'phone' => 'required|string|min:10 max:12'
            ]);
        if ($validator->fails()) {
            return response()->json(["status" => "error", "message" => $validator->getMessageBag()->first()]);
        }
        else
        {
            $amount=$request->amount;
                    $phone=$request->phone;
                    $amount=intval($amount);
                    $phone=intval($phone);
                    $businessShortCode=""; //Add your business short code here
                    $passkey="";//Add your passkey here
                    $date = date('YmdHis');
                    $password= (base64_encode(strval($businessShortCode).$passkey.strval($date)));
                    $generated_token=$this->generateToken();
                    $decoded=json_decode($generated_token);
                    $token=$decoded->access_token;
                    $expire_time=$decoded->expires_in;
                    $ch = curl_init('https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer '.$token,
                        'Content-Type: application/json'
                    ]);
                    $data= [
                        "BusinessShortCode"=>$businessShortCode,
                        "Password"=>$password,
                        "Timestamp"=>strval($date),
                        "TransactionType"=> "CustomerPayBillOnline",
                        "Amount"=> $amount,
                        "PartyA"=> $phone,
                        "PartyB"=>$businessShortCode,
                        "PhoneNumber"=> $phone,
                        "CallBackURL"=>'https://54a1-102-140-197-73.ngrok.io/api/v1/accounts/add', //Change to your ngrok/api/v1/accounts/add
                        "AccountReference"=>"M-pesa test",
                        "TransactionDesc"=> "M-pesa test payment"
                    ];
                    $data=json_encode($data);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    $result=json_decode($response);

          /*            $credit_checkout=new Checkout();
                      $credit_checkout->checkout_id=$result->CheckoutRequestID;
                      $credit_checkout->save();*/

                    return $result;
                }






    }

    public function updateDatabase(Request  $request)
    {
        $content=$request->getContent();
       // error_log($content);
        try{
            //$object=Json::encode($content);
            $callBackArray = json_decode($content, true);
            $result_code=$callBackArray['Body']['stkCallback']['ResultCode'];

            if(intval($result_code) == 0) {
                $MerchantRequestID= $callBackArray['Body']['stkCallback']['MerchantRequestID'];
                $checkoutRequestId = $callBackArray['Body']['stkCallback']['CheckoutRequestID'];
                $amount = $callBackArray['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
                $trans_code = $callBackArray['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
                $date = $callBackArray['Body']['stkCallback']['CallbackMetadata']['Item'][3]['Value'];
                $phone = $callBackArray['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];


             /*   if($checkoutRequestId == "check your checkout db")
                {
                    //Logic to update database
                    Log::info("processed successfully,updating account db");



                }
                else
                {


                    Log::info("Something went wrong here");

                }*/

                //Logic to update database
                Log::info("processed successfully,updating account db");





            }
            error_log($result_code);

        }
        catch (\Exception $e)
        {
            error_log($e);
        }

    }

    public function generateToken()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, ' https://api.safaricom.co.ke/oauth/v1/generate');
        $credentials = base64_encode('your consumer key here:your secret key here');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials)); //setting a custom header
        curl_setopt($curl, CURLOPT_HEADER, false);
        //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, config('services.mpesa.ssl'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        return $curl_response;
    }



}
