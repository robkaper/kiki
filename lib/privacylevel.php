<?php

namespace Kiki;

// Requires PHP>8.0
/*
enum PrivacyLevel: int
{
    case Global = 1;
    case Public = 2;
    case Network = 3;
    case Private = 4;
}
*/

class PrivacyLevel
{
    public const Global = 1;
    public const Public = 2;
    public const Network = 3;
    public const Private = 4;

    static public function getConstants()
    {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

    static public function getConstant( $value )
    {
        return array_search( $value, self::getConstants() );
    }

    static public function getValue( $key )
    {
        return self::getConstants()[ucfirst(strtolower($key))] ?? null;
    }
}
