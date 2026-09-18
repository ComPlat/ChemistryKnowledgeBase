<?php

namespace DIQA\WikiFarm\Exceptions;

use Exception;
use Throwable;

class ValidationException extends Exception {

    private $params;

    public function __construct($message = "", $params = [], $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->params = $params;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }


}