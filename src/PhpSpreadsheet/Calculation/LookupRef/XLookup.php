<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class XLookup extends LookupBase
{
    use ArrayEnabled;

    /**
     * XLOOKUP
     * The XLOOKUP function searches a range or an array, and then returns the item corresponding to the first match it finds.
     * If no match exists, then XLOOKUP can return the closest (approximate) match. 
     *
     * @param mixed $lookupValue The value that you want to match in lookup_array
     * @param mixed $lookupArray The range of cells being searched
     * @param mixed $indexNumber The array or range to return
     * @param mixed $notExactMatch determines if you are looking for an exact match based on lookup_value
     *
     * @return mixed The value of the found cell
     */
    public static function lookup($lookupValue, $lookupArray, $indexNumber, $notExactMatch = true)
    {
        if (is_array($lookupValue)) {
            return self::evaluateArrayArgumentsIgnore([self::class, __FUNCTION__], 1, $lookupValue, $lookupArray, $indexNumber, $notExactMatch);
        }

        $notExactMatch = (bool) ($notExactMatch ?? true);

        try {
            self::validateLookupArray($lookupArray);
            $indexNumber = self::validateIndexLookup($lookupArray, $indexNumber);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $f = array_keys($lookupArray);
        $firstRow = array_pop($f);
        if ((!is_array($lookupArray[$firstRow])) || ($indexNumber > count($lookupArray[$firstRow]))) {
            return ExcelError::REF();
        }
        $columnKeys = array_keys($lookupArray[$firstRow]);
        $returnColumn = $columnKeys[--$indexNumber];
        $firstColumn = array_shift($columnKeys) ?? 1;

        if (!$notExactMatch) {
            /** @var callable */
            $callable = [self::class, 'vlookupSort'];
            uasort($lookupArray, $callable);
        }

        $rowNumber = self::vLookupSearch($lookupValue, $lookupArray, $firstColumn, $notExactMatch);

        if ($rowNumber !== null) {
            // return the appropriate value
            return $lookupArray[$rowNumber][$returnColumn];
        }

        return ExcelError::NA();
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
     * @param  int|string $column
     */
    private static function vLookupSearch($lookupValue, array $lookupArray, $column, bool $notExactMatch): ?int
    {
        $lookupLower = StringHelper::strToLower((string) $lookupValue);

        $rowNumber = null;
        foreach ($lookupArray as $rowKey => $rowData) {
            $bothNumeric = is_numeric($lookupValue) && is_numeric($rowData[$column]);
            $bothNotNumeric = !is_numeric($lookupValue) && !is_numeric($rowData[$column]);
            $cellDataLower = StringHelper::strToLower((string) $rowData[$column]);

            // break if we have passed possible keys
            if (
                $notExactMatch &&
                (($bothNumeric && ($rowData[$column] > $lookupValue)) ||
                ($bothNotNumeric && ($cellDataLower > $lookupLower)))
            ) {
                break;
            }

            $rowNumber = self::checkMatch(
                $bothNumeric,
                $bothNotNumeric,
                $notExactMatch,
                $rowKey,
                $cellDataLower,
                $lookupLower,
                $rowNumber
            );
        }

        return $rowNumber;
    }
}
