<?php

namespace AdminEshop\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Lang;
use Admin;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('client.guest', ['except' => 'logout']);
    }

    protected function guard()
    {
        return auth()->guard('client');
    }

    /*
     * Redirect after login
     */
    public function authenticated($request, $user)
    {
        if ( $user->published_at === null )
        {
            $this->guard()->logout();

            return response()->json([
                'message' => _('Vaš účet bol deaktivovaný, pre viac informacii nás kontaktujte.')
            ], 422);
        }

        $this->updateLastLogin();

        return ['redirect' => url()->previous()];
    }

    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        return response()->json([
            'message' => Lang::get('auth.throttle', ['seconds' => $seconds])
        ], 422);
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        return redirect($this->redirectTo);
    }

    /*
     * Find user with old md5 password an change his value
     */
    private function checkAndResetOldPassword($request)
    {
        //Check if old passwords are available
        if ( env('OLDPASSWORDS_PASSWORDS', false) !== true )
            return false;

        $data = $this->credentials($request);

        $user = Admin::getModel('Client')
                    ->whereNotNull('salt')
                    ->withUnpublished()
                    ->where($this->username(), $data[$this->username()])
                    ->select(['id', 'password', 'salt', 'published_at'])
                    ->first();

        //Update old user password into new format
        if ( ! $user || md5($data['password'] . $user->salt ) != $user->password )
            return false;

        $user->update([
            'password' => bcrypt($data['password']),
            'salt' => null,
        ]);

        return $user;
    }

    /*
     * Update last login activity
     */
    private function updateLastLogin()
    {
        $this->guard()->user()->update([
            'last_logged_at' => \Carbon\Carbon::now(),
        ]);

        return true;
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        return response()->json([
            'message' => trans('auth.failed'),
        ], 422);
    }

    /*
     * Log in user, with checking old way secure of password from old eshop
     */
    protected function attemptLogin(Request $request)
    {
        /*
         * Login with old MD5 + salt
         */
        if ( ($user = $this->checkAndResetOldPassword($request)) !== false )
        {
            $this->guard()->login($user, $request->filled('remember'));

            return true;
        }

        if ( ! $this->guard()->attempt( $this->credentials($request), $request->filled('remember') ) )
            return false;

        return true;
    }
}