<?php

namespace App\Exceptions;

class ExceedMaxPointSubmission extends \Exception
{
    public function __construct()
    {
        parent::__construct('exceed maximum point submission', 500, null);
    }
}
