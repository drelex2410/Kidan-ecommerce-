<?php

namespace App\Http\Controllers;

use App\Mail\EmailManager;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mail;

class NewsletterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:send_newsletters'])->only('index', 'send');
    }

    public function index(Request $request)
    {
        $users = User::where('user_type', 'customer')->where('email', '!=', null)->get();
        $subscribers = Subscriber::all();
        return view('backend.marketing.newsletters.index', compact('users', 'subscribers'));
    }

    public function send(Request $request)
    {
        if (config('mail.from.address') != null) {
            //sends newsletter to selected users
            if ($request->has('user_emails')) {
                foreach ($request->user_emails as $key => $email) {
                    $array['view'] = 'emails.newsletter';
                    $array['subject'] = $request->subject;
                    $array['from'] = config('mail.from.address');
                    $array['content'] = $request->content;

                    try {
                        Mail::to($email)->queue(new EmailManager($array));
                    } catch (\Exception $e) {
                        //dd($e);
                    }
                }
            }

            //sends newsletter to subscribers
            if ($request->has('subscriber_emails')) {
                foreach ($request->subscriber_emails as $key => $email) {
                    $array['view'] = 'emails.newsletter';
                    $array['subject'] = $request->subject;
                    $array['from'] = config('mail.from.address');
                    $array['content'] = $request->content;

                    try {
                        Mail::to($email)->queue(new EmailManager($array));
                    } catch (\Exception $e) {
                        //dd($e);
                    }
                }
            }
        } else {
            flash(translate('Please configure SMTP first'))->error();
            return back();
        }

        flash(translate('Newsletter has been send'))->success();
        return redirect()->route('admin.dashboard');
    }

    public function testEmail(Request $request)
    {
        $array['view'] = 'emails.newsletter';
        $array['subject'] = "SMTP Test";
        $array['from'] = config('mail.from.address');
        $array['content'] = "This is a test email.";

        try {
            Mail::to($request->email)->send(new EmailManager($array));
        } catch (\Throwable $exception) {
            Log::error('SMTP test email failed', [
                'mailer' => config('mail.default'),
                'queue_connection' => config('queue.default'),
                'to' => $request->email,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            flash(translate('SMTP test failed. Check the mail configuration and application logs.'))->error();

            return back();
        }

        flash(translate('An email has been sent.'))->success();
        return back();
    }
}
