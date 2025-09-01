<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;

use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressesController extends Controller
{
    use ApiResponseTrait;
    public function getAddresses()
    {
        $user = auth()->user();
        $addresses = Address::where('user_id', $user->id)
            ->with([
                'country:id,name',
                'state:id,name',
                'city:id,name'
            ])
            ->get()
            ->map(function ($address) {
                return [
                    'id'           => $address->id,
                    'address'      => $address->address,
                    'name'         => $address->name,
                    'country_id'   => $address->country_id,
                    'state_id'     => $address->state_id,
                    'city_id'      => $address->city_id,
                    'country_name' => optional($address->country)->name??"Saudi Arabia",
                    'state_name'   => optional($address->state)->name??"Riyadh",
                    'city_name'    => optional($address->city)->name??"Riyadh",
                    'phone'        => $address->phone,
                    'latitude'     => (float) $address->latitude,
                    'longitude'    => (float) $address->longitude,
                    'is_default'   => $address->set_default ? true : false,
                ];
            });
        
        return $this->returnData($addresses, 'Addresses retrieved successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'longitude'  => 'required|numeric',
            'latitude'   => 'required|numeric',
            'name'       => 'required|string|max:255',
            'address'    => 'required|string|max:500',
            'country_id' => 'required|integer|exists:countries,id',
            'state_id'   => 'required|integer|exists:states,id',
            'city_id'    => 'required|integer|exists:cities,id',
            'phone'      => 'required|string|regex:/^\d{7,15}$/',
        ]);

        try {
            $address = Address::create([
                'user_id'    => auth()->id(),
                'longitude'  => $request->longitude,
                'latitude'   => $request->latitude,
                'name'       => $request->name,
                'address'    => $request->address,
                'country_id' => $request->country_id,
                'state_id'   => $request->state_id,
                'city_id'    => $request->city_id,
                'phone'      => $request->phone,
                'set_default'=> false,
            ]);
            return $this->returnData($address, 'Address created successfully.');
           

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'errNum' => 'E500',
                'msg'    => 'Failed to create address: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $address_id)
    {
        $request->validate([
            'longitude'  => 'required|numeric',
            'latitude'   => 'required|numeric',
            'name'       => 'required|string|max:255',
            'address'    => 'required|string|max:500',
            'country_id' => 'required|integer|exists:countries,id',
            'state_id'   => 'required|integer|exists:states,id',
            'city_id'    => 'required|integer|exists:cities,id',
            'phone'      => 'required|string|regex:/^\d{7,15}$/',
        ]);

        $address = Address::find($address_id);
        if (!$address || $address->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'errNum' => 'E422',
                'msg'    => 'Address not found or unauthorized.'
            ]);
        }

        try {
            $address->update([
                'longitude'  => $request->longitude,
                'latitude'   => $request->latitude,
                'name'       => $request->name,
                'address'    => $request->address,
                'country_id' => $request->country_id,
                'state_id'   => $request->state_id,
                'city_id'    => $request->city_id,
                'phone'      => $request->phone,
            ]);
            return $this->returnData($address, 'Address updated successfully.');
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'errNum' => 'E500',
                'msg'    => 'Failed to update address: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function setDefault(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
        ]);

        $userId = $request->user()->id;

        // Reset all addresses
        Address::where('user_id', $userId)->update(['set_default' => 0]);

        // Set the selected address as default
        Address::where('id', $request->address_id)
            ->where('user_id', $userId)
            ->update(['set_default' => 1]);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Default address updated successfully.'
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
        ]);

        $userId = $request->user()->id;

        Address::where('id', $request->address_id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Address removed successfully.'
        ]);
    }


}