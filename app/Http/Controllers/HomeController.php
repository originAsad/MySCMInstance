<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Plan;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //Get all plans from stripe api;
        $plans=Plan::getStripePlans();
        //check if authenticated
        $is_subscribed=Auth::user()->subscribed('main');
        //If subscribed get the Subscription
        $subscription=Auth::user()->subscription('main');

        return view('home',compact('plans','is_subscribed','subscription'));
    }
}
