<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class XLookup extends LookupBase
{
    use ArrayEnabled;

    const MATCH_MODE_EXACT = 0;
    const MATCH_MODE_EXACT_OR_SMALLER = -1;
    const MATCH_MODE_EXACT_OR_LARGER = 1;
    const MATCH_MODE_EXACT_WILDCARD = 2;

    /**
     * XLOOKUP
     * The XLOOKUP function searches a range or an array, and then returns the item corresponding to the first match it finds.
     * If no match exists, then XLOOKUP can return the closest (approximate) match. 
     *
     * @param mixed $lookupValue The value that you want to match in lookup_array
     * @param mixed $lookupArray The range of cells being searched
     * @param mixed $returnArray The array or range to return
     * @param mixed $ifNotFound determines if you are looking for an exact match based on lookup_value
     * @param mixed $matchMode Specify the match type
     * @param mixed $searchMode Specify the search mode to use
     *
     * @return mixed The value of the found cell
     */
    public static function lookup($lookupValue, $lookupArray, $returnArray, $ifNotFound = null, $matchMode = self::MATCH_MODE_EXACT, $searchMode = 1)
    {
        if (is_array($lookupValue)) {
            return self::evaluateArrayArgumentsIgnore([self::class, __FUNCTION__], [1,2], $lookupValue, $lookupArray, $returnArray, $ifNotFound, $matchMode, $searchMode);
        }

        try {
            // Validate both arrays are actually arrays
            self::validateLookupArray($lookupArray);
            self::validateLookupArray($returnArray);

            // Remove keys
            $lookupArray = array_values($lookupArray);
            $returnArray = array_values($returnArray);

            // Find the dimensions of the lookupArray - if only a single row, then that becomes the lookup array
            if (count($lookupArray) === 1)
                $lookupArray = array_values($lookupArray[0]);
            else // otherwise, trim to just the first column values
                $lookupArray = array_map([self::class, 'xLookupTrimArray'], $lookupArray);

        } catch (Exception $e) {
            return ExcelError::REF();
        }

        if ($searchMode != 1) {
            // Do this later
            // /** @var callable */
            // $callable = [self::class, 'vlookupSort'];
            // uasort($lookupArray, $callable);
        }

        $returnIndex = self::xLookupSearch($lookupValue, $lookupArray, $matchMode);

        if ($returnIndex !== null) {
            if ($returnIndex >= count($returnArray))
                return ExcelError::VALUE();

            // return the appropriate value
            return self::xLookupImplodeArray($returnArray[$returnIndex]);
        }
        else if (is_string($ifNotFound) || is_numeric($ifNotFound))
            return $ifNotFound;

        return ExcelError::NA();
    }

    private static function xLookupTrimArray(mixed $arrayEl)
    {
        if (is_array($arrayEl))
            return count($arrayEl) ? array_values($arrayEl)[0] : null;
            
        return $arrayEl;
    }

    private static function xLookupImplodeArray(mixed $arrayOrString)
    {
        if (is_array($arrayOrString))
            return implode(", ", $arrayOrString);

        return $arrayOrString;
    }

    private static function vlookupSort(array $a, array $b): int
    {
        reset($a);
        $firstColumn = key($a);
        $aLower = StringHelper::strToLower((string) $a[$firstColumn]);
        $bLower = StringHelper::strToLower((string) $b[$firstColumn]);

        if ($aLower == $bLower) {
            return 0;
        }

        return ($aLower < $bLower) ? -1 : 1;
    }

    /**
     * @param mixed $lookupValue The value that you want to match in lookup_array
     * @param array $lookupArray
     */
    private static function xLookupSearch($lookupValue, array $lookupArray, int $matchMode): ?int
    {
        $lookupLower = StringHelper::strToLower((string) $lookupValue);

        $returnIndex = null;
        foreach ($lookupArray as $lookupIndex => $data) {
            $bothNumeric = is_numeric($lookupValue) && is_numeric($data);
            $bothNotNumeric = !is_numeric($lookupValue) && !is_numeric($data);
            $cellDataLower = StringHelper::strToLower((string) $data);

            // Only need to compare two numeric or two non-numeric values
            if ($bothNumeric || $bothNotNumeric)
            {
                // EXACT MATCH
                if ($cellDataLower === $lookupLower)
                {
                    $returnIndex = $lookupIndex;
                    break;
                }
                else if ($matchMode !== self::MATCH_MODE_EXACT && $returnIndex === null)
                    $returnIndex = $lookupIndex;
                else if ($returnIndex !== null &&
                    ($matchMode === self::MATCH_MODE_EXACT_OR_SMALLER &&
                    $cellDataLower < $lookupLower &&
                    $cellDataLower > $lookupArray[$returnIndex]) ||
                    ($matchMode === self::MATCH_MODE_EXACT_OR_LARGER &&
                    $cellDataLower > $lookupLower &&
                    $cellDataLower < $lookupArray[$returnIndex])
                )
                    $returnIndex = $lookupIndex;
            }
        }

        return $returnIndex;
    }
}
