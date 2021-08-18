<?php

namespace App\Helper;

class FormatValidator
{
    public static function isJSON($string) : bool
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE);
    }
}