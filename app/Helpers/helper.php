<?php

if (! function_exists('response_success')) {
    function response_success($data = null, $status = 200, $message = 'ok')
    {
        return response()->json([
            'data' => $data,
            'status' => $status,
            'message' => $message,
        ], $status);
    }
}

if (! function_exists('response_error')) {
    function response_error($data = null, $status = 400, $message = 'An error occurred')
    {
        return response()->json([
            'data' => $data,
            'status' => $status,
            'message' => $message,
        ], $status);
    }

}
