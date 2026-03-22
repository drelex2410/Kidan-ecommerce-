<?php

namespace App\Http\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class SmsServices
{
    public function sendSMS($to, $text, $from = null): bool
    {
        try {

            if (get_setting('active_sms_gateway') == 'twilio') {
                $TWILIO_SID = env('TWILIO_SID');
                $TWILIO_AUTH_TOKEN = env('TWILIO_AUTH_TOKEN');

                Http::withHeaders([
                    'Authorization' => 'Basic ' . \base64_encode("$TWILIO_SID:$TWILIO_AUTH_TOKEN")
                ])->asForm()->post("https://api.twilio.com/2010-04-01/Accounts/$TWILIO_SID/Messages.json", [
                    "Body" => $text,
                    "From" => env('VALID_TWILLO_NUMBER'),
                    "To" => $to,
                ]);
                return true;
            } elseif (get_setting('active_sms_gateway') == 'vonage') {
                Http::post('https://rest.nexmo.com/sms/json', [
                    'from' => env('APP_NAME'),
                    'text' => $text,
                    'to' => $to,
                    'api_key' => env('VONAGE_KEY'),
                    'api_secret' => env('VONAGE_SECRET'),
                ]);
                return true;
            }
        } catch (Exception $e) {
            report($e);
        }

        report(new Exception('SMS delivery failed or no active SMS gateway is configured.'));

        return false;
    }

    public function phoneVerificationSms($to, $code): bool
    {
        $sms = 'Your verification code for ' . env('APP_NAME') . ' is ' . $code . '.';
        return $this->sendSMS($to, $sms);
    }
    public function forgotPasswordSms($to, $code): bool
    {
        $sms = 'Your password reset code for ' . env('APP_NAME') . ' is ' . $code . '.';
        return $this->sendSMS($to, $sms);
    }
}
