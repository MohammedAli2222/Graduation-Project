<?php

namespace App\Exceptions;

use Exception;

class PendingAppointmentsException extends Exception
{
    public function render($request)
    {
        return response_error(null, 400, 'Please complete or cancel scheduled appointments first.');
    }
}
