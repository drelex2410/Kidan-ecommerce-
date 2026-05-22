<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClubPointResource;
use App\Models\ClubPoint;
use App\Models\ClubPointDetail;
use App\Models\CombinedOrder;
use App\Models\Wallet;
use App\Support\ClubPointMath;
use Auth;
use Illuminate\Http\Request;

class ClubPointController extends Controller
{
    public function earningRechargeHistory()
    {
        $data = [
            'success' => true,
            'status' => 200
        ];
        return ClubPointResource::collection(ClubPoint::where('user_id', auth('api')->user()->id)->latest()->paginate(12))->additional($data);
    }


    public function processClubPoints(CombinedOrder $combinedOrder, $club_points)
    {
        $club_point = new ClubPoint;
        $club_point->user_id = $combinedOrder->user_id;
        $club_point->points = ClubPointMath::normalize($club_points);
        $club_point->combined_order_id = $combinedOrder->id;
        $club_point->convert_status = 0;
        $club_point->save();

        foreach ($combinedOrder->orders as $key => $order) {
            foreach ($order->orderDetails as $key => $orderDetail) {
                $club_point_detail = new ClubPointDetail;
                $club_point_detail->club_point_id = $club_point->id;
                $club_point_detail->product_id = $orderDetail->product_id;
                $club_point_detail->product_qty =  $orderDetail->quantity;
                $club_point_detail->point = ClubPointMath::multiply(
                    $orderDetail->product->earn_point,
                    $orderDetail->quantity
                );
                $club_point_detail->save();
            }
        }

        if ($combinedOrder->user) {
            $combinedOrder->user->club_points = ClubPointMath::add(
                $combinedOrder->user->club_points,
                $club_points
            );
            $combinedOrder->user->save();
        }
    }


    public function convert_point_into_wallet(Request $request)
    {
        $club_point = ClubPoint::findOrFail($request->id);

        $combinedOrder = $club_point->combined_order;

        $unPaidOrder = $combinedOrder->orders()->where('payment_status', "unpaid")->first();


        if (is_null($unPaidOrder)) {
            if ($club_point->convert_status == 0) {
                $wallet = new Wallet;
                $wallet->user_id = Auth::user()->id;
                $rate = ClubPointMath::normalize(get_setting('club_point_convert_rate', 1));
                if (ClubPointMath::compare($rate, '0') === 0) {
                    $rate = ClubPointMath::normalize(1);
                }
                $walletAmount = ClubPointMath::divide($club_point->points, $rate);
                $wallet->amount = ClubPointMath::toFloat($walletAmount);
                $wallet->payment_method = 'Club Point Converted';
                $wallet->payment_details = 'Club Point Converted';
                $wallet->save();
                $user = auth('api')->user();
                $user->balance = (float) $user->balance + ClubPointMath::toFloat($walletAmount);
                $user->club_points = ClubPointMath::clampToZero(
                    ClubPointMath::subtract($user->club_points, $club_point->points)
                );
                $user->save();
                $club_point->convert_status = 1;
            }

            if ($club_point->save()) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 3; // 3 means all orders are not paid so you can not convert it
        }
    }
}
