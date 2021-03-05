<?php

namespace App\Exceptions;

use Exception;

class InvalidExchangeException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'errors' => json_decode($this->getMessage())
        ], 400);
    }
}
