<?php

namespace App\Http\Controllers;

use App\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class PlanController extends Controller
{
    /**
     * Show Plan with form to subscribe
     *
     * @param $id
     */
    public function show($id){
        //Get the plan id from the cache
        $plan=$this->getPlanByIdOrFail($id);
        // $plans=Plan::findOrFail($id);
        return view('plan',compact('plan'));
    }
    
    /**
     * Handle subscription request
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function subscribe(Request $request){
        //Validate request
        $this->validate( $request, [
              'stripeToken' => 'required',
              'plan' => 'required'
            ]);

            \Stripe\Stripe::setApiKey ( 'sk_test_INJvZWiP2ctnB2tzWvUwB1jo' );
        // User chosen plan
        $pickedPlan = $request->get('plan');
        // Current logged in user
        $me = Auth::user();
        try {
            // check already subscribed and if already subscribed with picked plan
            if( $me->subscribed('main') && ! $me->subscribedToPlan($pickedPlan, 'main') ) {
                // swap if different plan attempt
                $me->subscription('main')->swap($pickedPlan);
            } else {
                // Its new subscription
                // if user has a coupon, create new subscription with coupon applied
                if( $coupon = $request->get('coupon') ) {

                    $me->newSubscription( 'main', $pickedPlan)
                        ->withCoupon($coupon)
                        ->create($request->get('stripeToken'), [
                            'email' => $me->email
                        ]);

                } else {

                    // Create subscription
                    $me->newSubscription( 'main', $pickedPlan)->create($request->get('stripeToken'), [
                        'email' => $me->email,
                        'description' => $me->name
                    ]);
                }

            }
        } catch (\Exception $e) {
            // Catch any error from Stripe API request and show
           return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }
        return redirect()->route('home')->with('status', 'You are now subscribed to ' . $pickedPlan . ' plan.');
    }
    /**
     * Get Cached Plan by Id
     * @param $id
     * @return mixed
     */
    private function getPlanByIdOrFail($id)
    {
        $plans = Plan::getStripePlans();

        if( ! $plans ) throw new NotFoundHttpException;

        return array_first(array_filter( $plans, function($plan) use ($id) {
            return $id == $plan->id;
        }));
    }

}
