<?php

namespace App\Http\Controllers;


use App\Models\Product;
use App\Models\Review;
use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponseTrait;
    public function getNotifications(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'page' => 'nullable|integer',
        ]);

        $notifications = \App\Models\Notification::where('user_id', $request->user()->id)
            ->where('type', $request->type)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'data' => $notifications
        ]);
    }

    public function updateRead(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|integer|exists:notifications,id',
        ]);

        $notification = \App\Models\Notification::where('id', $request->notification_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => false,
                'errNum' => 'E422',
                'msg' => trans('api.notification_not_found')
            ]);
        }

        $notification->update([
            'read_at' => now()
        ]);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => trans('api.notification_marked_as_read')
        ]);
    }
 }