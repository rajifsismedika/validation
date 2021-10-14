<?php

namespace Rajifsismedika\Validation\Rules\Traits;

use Exception;

trait DateUtilsTrait
{

    /**
     * Check the $date is valid
     *
     * @param $date
     * @return bool
     */
    protected function isValidDate($date)
    {
        return (strtotime($date) !== false);
    }

    /**
     * Throw exception
     *
     * @param $value
     * @return Exception
     */
    protected function throwException($value)
    {
        // phpcs:ignore
        return new Exception("Expected a valid date, got '{$value}' instead. 2016-12-08, 2016-12-02 14:58, tomorrow are considered valid dates");
    }

    /**
     * Given $date and get the time stamp
     *
     * @param mixed $date
     * @return int
     */
    protected function getTimeStamp($date)
    {
        return strtotime($date);
    }
}
