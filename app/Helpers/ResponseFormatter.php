<?php

namespace App\Helpers;

class ResponseFormatter
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error($message = 'Error', $errors = null, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Validation error response
     *
     * @param mixed $errors
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function validationError($errors, $message = 'Validation error')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }

    /**
     * Forbidden response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function forbidden($message = 'Forbidden')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function notFound($message = 'Not found')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }
}
