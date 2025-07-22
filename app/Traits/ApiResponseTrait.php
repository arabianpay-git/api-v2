<?php

namespace App\Traits;

use App\Helpers\EncryptionService;

trait ApiResponseTrait
{
    public function returnData($value, $msg = "")
    {
        $EncryptionService = new EncryptionService();

        return response()->json([
            'status' => true,
            'errNum' => "S200",
            'msg' => $msg,
            'data' => $EncryptionService->encrypt($value),
        ]);
    }

    public function returnError($msg, $errNum = "E400")
    {
        return response()->json([
            'status' => false,
            'errNum' => $errNum,
            'msg' => $msg,
        ]);
    }
}
