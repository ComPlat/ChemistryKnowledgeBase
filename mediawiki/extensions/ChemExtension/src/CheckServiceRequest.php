<?php

namespace DIQA\ChemExtension;

interface CheckServiceRequest {
    public function check(): \CurlHandle|false;
}
