<?php

namespace PhpOffice\PhpSpreadsheetTests\Calculation\Functions\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

class XLookupTest extends TestCase
{
    /**
     * @dataProvider providerXLOOKUP
     *
     * @param mixed $expectedResult
     * @param mixed $value
     * @param mixed $table
     * @param mixed $index
     */
    public function testXLOOKUP($expectedResult, $value, $table, $index, ?bool $lookup = null): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if (is_array($table)) {
            $sheet->fromArray($table);
            $dimension = $sheet->calculateWorksheetDimension();
        } else {
            $sheet->getCell('A1')->setValue($table);
            $dimension = 'A1';
        }
        if ($lookup === null) {
            $lastarg = '';
        } else {
            $lastarg = $lookup ? ',TRUE' : ',FALSE';
        }
        $sheet->getCell('Z98')->setValue($value);
        $sheet->getCell('Z97')->setValue($index);

        $sheet->getCell('Z99')->setValue("=XLOOKUP(Z98,$dimension,Z97$lastarg)");
        $result = $sheet->getCell('Z99')->getCalculatedValue();
        self::assertEquals($expectedResult, $result);
        $spreadsheet->disconnectWorksheets();
    }

    public function providerXLOOKUP(): array
    {
        return require 'tests/data/Calculation/LookupRef/XLOOKUP.php';
    }

    /**
     * @dataProvider providerXLookupArray
     */
    public function testXLookupArray(array $expectedResult, string $values, string $database, string $index): void
    {
        $calculation = Calculation::getInstance();

        $formula = "=XLOOKUP({$values}, {$database}, {$index}, false)";
        $result = $calculation->_calculateFormulaValue($formula);
        self::assertEquals($expectedResult, $result);
    }

    public function providerXLookupArray(): array
    {
        return [
            'row vector' => [
                [[4.19, 5.77, 4.14]],
                '{"Orange", "Green", "Red"}',
                '{"Red", 4.14; "Orange", 4.19; "Yellow", 5.17; "Green", 5.77; "Blue", 6.39}',
                '2',
            ],
        ];
    }
}
