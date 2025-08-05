<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PDF;
use Swift_TransportException;

class EmailHelper
{
    public static $disableEmailException = 1;
    public static $disable_email_exception = 1;


    public static function sendPasswordResetOtp($user, $isTest = false)
    {

        // dd(Str::lower($user->email));
        Log::info("sendPasswordResetOtp", (array)$user);
        try {
            if ($user && $user->email) {
                $to = Str::lower($user->email);
                $subject = 'Forgot Password Otp';
                // $link = config('constants.RESET_PASSWORD_LINK_ORG') . $user->remember_token;
                $res = Mail::send('emails.otp', ['user' => $user], function ($message) use ($to, $subject) {
                    $message->to($to);
                    $message->subject($subject);
                });
                if ($isTest) {
                    dump($res);
                }
                Log::info("Email Response", (array)$res);
            }
        } catch (Swift_TransportException $STe) {

            Log::error("Swift_TransportException ",(array) $STe);
            if ($isTest) {
                dd($STe);
            }
            if (self::$disable_email_exception == 0) {
                throw new \Exception("The mail service has encountered a problem. Please retry later or contact the site admin.");
            }
        } catch (\Exception $e) {
            Log::error("Exception ",(array) $e);
            if ($isTest) {
                dd($e);
            }
            if (self::$disable_email_exception == 0) {
                throw new \Exception($e->getMessage());
            }
        }
    }
    public static function sendOTP($user, $subject, $isTest = false)
    {
        try {
            Log::info("Email Info", (array) $user);
            if ($user && $user->email) {
                $to = Str::lower($user->email);
                $res = Mail::send('mail.otp', ['user' => $user], function ($message) use ($to, $subject) {
                    $message->to($to);
                    $message->subject($subject);
                });
                if ($isTest) {
                    dump($res);
                }
            }
        } catch (Swift_TransportException $STe) {
            if ($isTest) {
                dd($STe);
            }
            if (self::$disableEmailException == 0) {
                throw new \Exception(trans("messages.EMAIL_SERVICE_NOT_WORKS"));
            }
        } catch (\Exception $e) {
            if ($isTest) {
                dd($e);
            }
            if (self::$disableEmailException == 0) {
                throw new \Exception($e->getMessage());
            }
        }
    }
    
}
