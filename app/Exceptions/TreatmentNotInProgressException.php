<?php

namespace App\Exceptions;

use Exception;

class TreatmentNotInProgressException extends Exception
{
    public function render($request)
    {
        return response_error(null, 400, 'Treatment is not in progress.');
    }
}
