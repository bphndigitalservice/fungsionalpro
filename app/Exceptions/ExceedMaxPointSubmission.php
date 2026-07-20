<?php

namespace App\Exceptions;

use Exception;

class ExceedMaxPointSubmission extends Exception
{
    public function __construct()
    {
        parent::__construct('exceed maximum point submission', 500, null);
    }
}
