<?php

namespace App\Services;

class VoltageDropCalculator
{
    public const TEMP_COEFF = 0.004;
    public const DEFAULT_TEMP = 20;

    public static array $voltage = [
        'VOLTAGE_AC_220' => 'Однофазный переменный ток',
        'VOLTAGE_AC_380' => 'Трехфазный переменный ток',
        'VOLTAGE_DC' => 'Постоянный ток'
    ];

    public static array $method = [
        'current' => 'По току',
        'power' => 'По мощности'
    ];

    public static array $materials = [
        'Медь' => 0.0175,
        'Алюминий' => 0.0282,
        'Сталь' => 0.13
    ];

    public static array $sections = [
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

    public function getValidationRules(array $formData): array
    {
        $rules = [
            'voltage' => [
                'required' => 'Выберите тип тока.',
                'in' => ['values' => array_keys(self::$voltage), 'message' => 'Некорректный тип тока.'],
            ],
            'method' => [
                'required' => 'Выберите метод расчета.',
                'in' => ['values' => array_keys(self::$method), 'message' => 'Некорректный метод расчета.'],
            ],
            'material' => [
                'required' => 'Выберите материал.',
                'in' => ['values' => array_keys(self::$materials), 'message' => 'Некорректный материал.'],
            ],
            'section' => [
                'required' => 'Выберите площадь сечения.',
                'in' => ['values' => array_keys(self::$sections), 'message' => 'Некорректная площадь сечения.'],
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
        ];

        if (($formData['method'] ?? '') === 'current') {
            $rules['current'] = [
                'required' => 'Введите силу тока.',
                'numeric' => ['min' => 0.01, 'max' => 10000, 'message' => 'Введите корректную силу тока.'],
            ];
        } elseif (($formData['method'] ?? '') === 'power') {
            $rules['power'] = [
                'required' => 'Введите мощность.',
                'numeric' => ['min' => 0.01, 'max' => 1000000, 'message' => 'Введите корректную мощность.'],
            ];
            if (($formData['voltage'] ?? '') !== 'VOLTAGE_DC') {
                $rules['cosifi'] = [
                    'required' => 'Введите коэффициент мощности.',
                    'numeric' => ['min' => 0.01, 'max' => 1, 'message' => 'Введите корректный коэффициент мощности.'],
                ];
            }
        }

        return $rules;
    }

    public function validateInput(array $formData, array $rules): array
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

    public function calculateResult(array $formData): array
    {
        $voltageType = $formData['voltage'];
        $methodType = $formData['method'];
        $material = $formData['material'];
        $section = $formData['section'];
        $temperature = floatval($formData['temperature']);
        $length = floatval($formData['length']);
        $cosifi = floatval($formData['cosifi'] ?? 1);
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

        $resistivity = self::$materials[$material];
        $sectionValue = self::$sections[$section];

        if ($temperature != self::DEFAULT_TEMP) {
            $resistivity *= (1 + self::TEMP_COEFF * ($temperature - self::DEFAULT_TEMP));
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

    public function getDefaultVoltage(string $voltageType): int
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
}
