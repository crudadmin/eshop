<?php

namespace AdminEshop\Controllers\Store;

use AdminEshop\Controllers\Controller;
use Illuminate\Http\Request;
use Store;

class StoreController extends Controller
{
    public function setB2B($value)
    {
        Store::setB2B($value == 1 ? true : false);

        return redirect()->back();
    }
}
