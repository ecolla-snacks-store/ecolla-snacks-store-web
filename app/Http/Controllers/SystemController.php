<?php

namespace App\Http\Controllers;

use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SystemController extends Controller
{
    public function freshSetup()
    {
        if (!Schema::hasTable('users')) {
            Artisan::call('migrate:fresh');
            Artisan::call('db:seed ProdSeeder');
        }

        echo "Setup up completed!";
    }

    public function systemUpdate(string $password)
    {
        $user = User::query()
            ->where('username', '=', 'admin')
            ->first();

        if (Hash::check($password, $user->password)) {
            // Update function import here
            // SystemUpdate::updateSomething();

            echo "<br><br>Update completed!";
        } else {
            abort(403);
        }
    }

    public function shippingFeeConfig(): JsonResponse
    {
        return response()->json(
            [
                'shippingFee' => SystemConfig::getShippingFee(),
                'freeShippingIsActivated' => SystemConfig::freeShippingIsActivated(),
                'freeShippingThreshold' => SystemConfig::getFreeShippingThreshold(),
                'freeShippingDesc' => SystemConfig::getFreeShippingDesc(),
            ]
        );
    }

    public function updateShippingFee(): JsonResponse
    {
        $fee = request('shipping_fee');

        if (SystemConfig::query()
            ->where('name', '=', 'shipping_fee')
            ->update(['value' => $fee])
        ) {
            return response()->json([], 204);
        } else {
            return response()->json([], 400);
        }
    }

    public function updateShippingDiscount(): JsonResponse
    {
        if (request()->has('freeShipping_isActivated')) {
            $isActivated = request('freeShipping_isActivated');

            if (SystemConfig::query()
                ->where('name', '=', 'freeShipping_isActivated')
                ->update(['value' => $isActivated])
            ) {
                return response()->json([], 204);
            } else {
                return response()->json([], 400);
            }
        } else {
            $threshold = request('freeShipping_threshold');
            $desc = request('freeShipping_desc');

            $thresholdIsUpdated = SystemConfig::query()
                ->where('name', '=', 'freeShipping_threshold')
                ->update(['value' => $threshold]);

            $descIsUpdated = SystemConfig::query()
                ->where('name', '=', 'freeShipping_desc')
                ->update(['value' => $desc]);

            if ($thresholdIsUpdated && $descIsUpdated) {
                return response()->json([], 204);
            } else {
                return response()->json([], 400);
            }
        }
    }
}
