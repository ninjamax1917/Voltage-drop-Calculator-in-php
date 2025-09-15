<?php

declare(strict_types=1);

include_once 'vendor/autoload.php';

$devMode = true; // или false для продакшена

const TEMP_COEFF = 0.004; //температурный коэффициент сопротивления
const DEFAULT_TEMP = 20; //дефолтная температура, при которой указано удельное сопротивление

$voltage = [
    'VOLTAGE_AC_220' => 'Однофазный переменный ток',
    'VOLTAGE_AC_380' => 'Трехфазный переменный ток',
    'VOLTAGE_DC' => 'Постоянный ток'
];

$method = [
    'current' => 'По току',
    'power' => 'По мощности'
];

$materials = [
    'Медь' => 0.0175,
    'Алюминий' => 0.0282,
    'Сталь' => 0.13
];

$sections = [
    '0.5 мм²' => 0.5,
    '0.75 мм²' => 0.75,
    '1 мм²' => 1,
    '1.5 мм²' => 1.5,
    '2.5 мм²' => 2.5,
    '4 мм²' => 4,
    '6 мм²' => 6,
    '10 мм²' => 10,
    '16 мм²' => 16,
    '25 мм²' => 25,
    '35 мм²' => 35,
    '50 мм²' => 50,
    '70 мм²' => 70,
    '95 мм²' => 95,
    '120 мм²' => 120,
    '150 мм²' => 150,
    '185 мм²' => 185,
    '240 мм²' => 240
];

$result = null;
$errors = [];

/**
 * Универсальная валидация формы на основе массива правил
 */
function validateInput(array $formData, array $rules): array
{
    $errors = [];
    foreach ($rules as $field => $rule) {
        $value = $formData[$field] ?? null;
        foreach ($rule as $type => $params) {
            switch ($type) {
                case 'required':
                    if ($value === null || $value === '') {
                        $errors[] = $params;
                    }
                    break;
                case 'in':
                    if (!in_array($value, $params['values'], true)) {
                        $errors[] = $params['message'];
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($value) || $value < $params['min'] || $value > $params['max']) {
                        $errors[] = $params['message'];
                    }
                    break;
            }
        }
    }
    return $errors;
}

/**
 * Расчет результата
 */
function calculateResult(array $formData, array $materials, array $sections): array
{
    $voltageType = $formData['voltage'];
    $methodType = $formData['method'];
    $material = $formData['material'];
    $section = $formData['section'];
    $temperature = floatval($formData['temperature']);
    $length = floatval($formData['length']);
    $cosifi = floatval($formData['cosifi']);
    $voltageValue = floatval($formData['voltageValue']);

    if ($methodType === 'current') {
        $current = floatval($formData['current']);
        $power = ($voltageValue * $current) / 1000;
    } else {
        $power = floatval($formData['power']);
        if ($voltageType === 'VOLTAGE_DC') {
            $current = $power / $voltageValue;
        } else {
            $current = $power / ($voltageValue * $cosifi);
        }
    }

    $resistivity = $materials[$material];
    $sectionValue = $sections[$section];

    if ($temperature != DEFAULT_TEMP) {
        $resistivity *= (1 + TEMP_COEFF * ($temperature - DEFAULT_TEMP));
    }

    if ($voltageType === 'VOLTAGE_AC_380') {
        $voltageDrop = (sqrt(3) * $length * $current * $resistivity) / $sectionValue;
    } else {
        $voltageDrop = (2 * $length * $current * $resistivity) / $sectionValue;
    }

    $voltageDropPercent = ($voltageDrop / $voltageValue) * 100;

    return [
        'voltageDrop_number' => round($voltageDrop, 2),
        'voltageDrop_percent' => round($voltageDropPercent, 2),
        'current' => round($current, 2),
        'power' => round($power, 2),
    ];
}

/**
 * Возвращает значение напряжения по умолчанию для выбранного типа тока
 */
function getDefaultVoltage(string $voltageType): int
{
    switch ($voltageType) {
        case 'VOLTAGE_AC_220':
            return 220;
        case 'VOLTAGE_AC_380':
            return 380;
        default:
            return 12;
    }
}

// Массив правил для универсальной валидации
$validationRules = [
    'voltage' => [
        'required' => 'Выберите тип тока.',
        'in' => ['values' => array_keys($voltage), 'message' => 'Некорректный тип тока.'],
    ],
    'method' => [
        'required' => 'Выберите метод расчета.',
        'in' => ['values' => array_keys($method), 'message' => 'Некорректный метод расчета.'],
    ],
    'material' => [
        'required' => 'Выберите материал.',
        'in' => ['values' => array_keys($materials), 'message' => 'Некорректный материал.'],
    ],
    'section' => [
        'required' => 'Выберите площадь сечения.',
        'in' => ['values' => array_keys($sections), 'message' => 'Некорректная площадь сечения.'],
    ],
    'temperature' => [
        'required' => 'Введите температуру.',
        'numeric' => ['min' => -50, 'max' => 100, 'message' => 'Введите корректную температуру.'],
    ],
    'length' => [
        'required' => 'Введите длину кабеля.',
        'numeric' => ['min' => 0.01, 'max' => 10000, 'message' => 'Введите корректную длину кабеля.'],
    ],
    'voltageValue' => [
        'required' => 'Введите напряжение.',
        'numeric' => ['min' => 0.01, 'max' => 10000, 'message' => 'Введите корректное напряжение.'],
    ],
    'current' => [
        // Проверка только если выбран метод "current"
        // В основной логике ниже добавим динамическую проверку
    ],
    'power' => [
        // Проверка только если выбран метод "power"
    ],
    'cosifi' => [
        // Проверка только если выбран переменный ток
    ],
];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'voltage' => $_POST['voltage'] ?? '',
        'method' => $_POST['method'] ?? '',
        'material' => $_POST['material'] ?? '',
        'section' => $_POST['section'] ?? '',
        'temperature' => $_POST['temperature'] ?? '',
        'length' => $_POST['length'] ?? '',
        'cosifi' => $_POST['cosifi'] ?? '',
        'voltageValue' => $_POST['voltageValue'] ?? '',
        'current' => $_POST['current'] ?? '',
        'power' => $_POST['power'] ?? '',
    ];

    // Динамические проверки для current/power/cosifi
    $dynamicRules = $validationRules;

    if (($formData['method'] ?? '') === 'current') {
        $dynamicRules['current'] = [
            'required' => 'Введите силу тока.',
            'numeric' => ['min' => 0.01, 'max' => 10000, 'message' => 'Введите корректную силу тока.'],
        ];
    } elseif (($formData['method'] ?? '') === 'power') {
        $dynamicRules['power'] = [
            'required' => 'Введите мощность.',
            'numeric' => ['min' => 0.01, 'max' => 1000000, 'message' => 'Введите корректную мощность.'],
        ];
        if (($formData['voltage'] ?? '') !== 'VOLTAGE_DC') {
            $dynamicRules['cosifi'] = [
                'required' => 'Введите коэффициент мощности.',
                'numeric' => ['min' => 0.01, 'max' => 1, 'message' => 'Введите корректный коэффициент мощности.'],
            ];
        }
    }

    $errors = validateInput($formData, $dynamicRules);

    if (!$errors) {
        if ($formData['voltageValue'] === '') {
            $formData['voltageValue'] = getDefaultVoltage($formData['voltage']);
        }
        $result = calculateResult($formData, $materials, $sections);
    }
}

if (php_sapi_name() !== 'cli') {
    include 'template.php';
} //Не выводим HTML при тестах
