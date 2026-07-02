<?php

namespace DIQA\FacetedSearch2\Exceptions;

use Exception;
class BackendException extends Exception {

    public function __construct($message, $code = -1, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    static function create(Exception $previous): BackendException
    {
        return new self($previous->getMessage(), $previous->getCode(), $previous);
    }
}