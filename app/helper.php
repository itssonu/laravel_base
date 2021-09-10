<?php
function otp($digits)
{
    $otp = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
    $otp = 1234;
    return $otp;
}
