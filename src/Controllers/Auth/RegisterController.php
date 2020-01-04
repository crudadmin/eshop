<?php

namespace AdminEshop\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Admin;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('client.guest');
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    public function store()
    {
        $client = Admin::getModel('client');

        $row = $client->validateRequest();

        if ( ! request()->has('terms') )
            return autoAjax()->modal(false)->error(_('Pre pokračovanie musíte súhlasiť so všeobecnými podmienkami.'));

        auth()->guard('client')->login($client->create($row), 1);

        return autoAjax()->modal(_('Boli ste úspešne zaregistrovaný, ďakujeme.'))->reload();
    }
}
