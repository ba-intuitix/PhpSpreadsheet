<?php

// function densityGrid(): array
// {
//     return [
//         ['Density', 'Viscosity', 'Temperature'],
//         [0.457, 3.55, 500],
//         [0.525, 3.25, 400],
//         [0.616, 2.93, 300],
//         [0.675, 2.75, 250],
//         [0.746, 2.57, 200],
//         [0.835, 2.38, 150],
//         [0.946, 2.17, 100],
//         [1.090, 1.95, 50],
//         [1.290, 1.71, 0],
//     ];
// }
// uses same densityGrid as VLOOKUP

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

$ifNotFound = 'fdnsfidnufindufindsf';

return [
    [
        400,
        0.525,
        densityGrid(),
        'A1:A10',
        'C1:C10',
    ],
    [
        ExcelError::VALUE(),
        0.525,
        densityGrid(),
        'A1:A10',
        'C1:C9',
    ],
    [
        '3.25, 400',
        0.525,
        densityGrid(),
        'A1:A10',
        'B1:C10',
    ],
    [
        ExcelError::NA(),
        'HELLO WORLD',
        densityGrid(),
        'A1:A10',
        'C1:C10',
    ],
    [
        $ifNotFound,
        'HELLO WORLD',
        densityGrid(),
        'A1:A10',
        'C1:C10',
        $ifNotFound
    ],
    [
        0.457,
        'Density',
        densityGrid(),
        'A1:C1',
        'A2:C2',
    ],
    [
        250,
        0.7,
        densityGrid(),
        'A1:A10',
        'C1:C10',
        0,
        -1
    ],
    [
        $ifNotFound,
        0.2,
        densityGrid(),
        'A1:A10',
        'C1:C10',
        $ifNotFound,
        -1
    ],
    [
        200,
        0.7,
        densityGrid(),
        'A1:A10',
        'C1:C10',
        0,
        1
    ],
    [
        $ifNotFound,
        1.5,
        densityGrid(),
        'A1:A10',
        'C1:C10',
        $ifNotFound,
        1
    ],
];
