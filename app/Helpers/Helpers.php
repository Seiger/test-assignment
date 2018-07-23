<?php
/**
 * Created by PhpStorm.
 * User: Seiger
 * Date: 22.07.2018
 * Time: 14:34
 */

namespace App\Helpers;


use Carbon\Carbon;

class Helpers
{
    /**
     * @param string $date
     * @param string $format
     * @return bool
     */
    public static function validateDate(string $date, string $format): bool
    {
        try {
            $carbonDate = Carbon::createFromFormat($format, $date);
            return $carbonDate->format($format) === $date;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}