<?php

namespace Themsaid\Langman\Support;

use Illuminate\Support\Arr as BaseArr;

class Arr extends BaseArr
{
    /**
     * Expand a dotted array. Opposite of Arr::dot().
     *
     * @param  array   $array
     * @param  bool  $recursively
     * @return array
     */
    public static function unDot($array, $recursively = true)
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (count($dottedKeys = explode('.', $key, 2)) > 1) {
                $results[$dottedKeys[0]][$dottedKeys[1]] = $value;
            } else {
                $results[$key] = $value;
            }
        }

        if ($recursively) {
            foreach ($results as $key => $value) {
                if (is_array($value) && ! empty($value)) {
                    $results[$key] = self::undot($value, $recursively);
                }
            }
        }

        return $results;
    }
}
