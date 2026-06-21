<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class UnauthorizedTreatmentException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request)
    {
        return response_error(
            null,
            403,
            'Unauthorized! You do not have permission to perform this action on this appointment.'
        );
    }
}
