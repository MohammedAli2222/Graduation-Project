<?php

namespace App\Exceptions;

use Illuminate\Http\Request;

use Exception;

class AppointmentAlreadyAttendedException extends Exception
{
    public function render(Request $request)
    {
        return response_error(null, 400, 'This appointment has already been attended.');
    }
}
