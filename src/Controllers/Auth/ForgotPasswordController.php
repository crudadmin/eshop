<?php

namespace AdminEshop\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('client.guest');
    }

    public function showLinkRequestForm()
    {
        return view('admineshop::auth.passwords.email');
    }

    protected function sendResetLinkResponse($response)
    {
        return autoAjax()->modal(trans($response));
    }

    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        return response()->json(
            ['email' => trans($response)]
        , 422);
    }

    public function broker()
    {
        return Password::broker('clients');
    }
}
