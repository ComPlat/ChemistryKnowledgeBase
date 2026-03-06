<?php

namespace DIQA\ChemExtension\PublicationSearch;

abstract class PublicationFetcher {
    abstract function fetchPublication( callable $callback, $daysBack = 1);

    function name(): string
    {
        return static::class;
    }

    static function factory(): array
    {
        return [new CrossRefAPI()];
    }
}