<?php

declare(strict_types=1);

include_once 'vendor/autoload.php';

$devMode = true; // или false для продакшена

$voltage = [
    'VOLTAGE_AC_220' => 'Однофазный переменный ток',
    'VOLTAGE_AC_380' => 'Трехфазный переменный ток',
    'VOLTAGE_DC' => 'Постоянный ток'
]; //тип тока: однофазный ток, трехфазный ток, постоянный ток

$method = [
    'current' => 'По току',
    'power' => 'По мощности'
]; //метод расчета: по току или по мощности

$voltageValue; //значение напряжения

$materials = [
    'Медь' => 0.0175,
    'Алюминий' => 0.0282,
    'Сталь' => 0.13
]; //удельное сопротивление материала кабеля

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
]; //площадь сечения кабеля

$current; //сила тока

$power; //мощность

$temperature; //температура окружающей среды

$length; //длина кабеля

$cosifi; //коэффициент мощности

$result = null; //массив для хранения результатов расчета


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из формы
    $voltageType = $_POST['voltage'] ?? '';
    $methodType = $_POST['method'] ?? '';
    $material = $_POST['material'] ?? '';
    $section = $_POST['section'] ?? '';
    $temperature = $_POST['temperature'] ?? '';
    $length = $_POST['length'] ?? '';
    $cosifi = $_POST['cosifi'] ?? '';
    $voltageValue = $_POST['voltageValue'] ?? '';

    function validateInput(array $formData, array $voltage, array $method, array $materials, array $sections): array
    {
        $errors = [];

        if (!isset($voltage[$formData['voltage'] ?? ''])) {
            dd($voltage[$formData['voltage']]);
            $errors[] = 'Выберите тип тока.';
        }
        if (!isset($method[$formData['method'] ?? ''])) {
            $errors[] = 'Выберите метод расчета.';
        }
        if (!isset($materials[$formData['material'] ?? ''])) {
            $errors[] = 'Выберите материал.';
        }
        if (!isset($sections[$formData['section'] ?? ''])) {
            $errors[] = 'Выберите площадь сечения.';
        }
        if (!is_numeric($formData['temperature'] ?? '') || $formData['temperature'] < -50 || $formData['temperature'] > 100) {
            $errors[] = 'Введите корректную температуру.';
        }
        if (!is_numeric($formData['length'] ?? '') || $formData['length'] <= 0) {
            $errors[] = 'Введите корректную длину кабеля.';
        }
        if (!is_numeric($formData['voltageValue'] ?? '') || $formData['voltageValue'] <= 0) {
            $errors[] = 'Введите корректное напряжение.';
        }
        if (($formData['method'] ?? '') === 'current') {
            if (!isset($formData['current']) || !is_numeric($formData['current']) || $formData['current'] <= 0) {
                $errors[] = 'Введите корректную силу тока.';
            }
        } else {
            if (!isset($formData['power']) || !is_numeric($formData['power']) || $formData['power'] <= 0) {
                $errors[] = 'Введите корректную мощность.';
            }
            if (($formData['voltage'] ?? '') !== 'VOLTAGE_DC' && (!is_numeric($formData['cosifi'] ?? '') || $formData['cosifi'] <= 0 || $formData['cosifi'] > 1)) {
                $errors[] = 'Введите корректный коэффициент мощности.';
            }
        }
        return $errors;
    }

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

        if ($temperature != 20) {
            $resistivity *= (1 + 0.004 * ($temperature - 20));
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

    $result = null;
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        $errors = validateInput($formData, $voltage, $method, $materials, $sections);

        if (!$errors) {
            // Значение напряжения по умолчанию
            if ($formData['voltageValue'] === '') {
                if ($formData['voltage'] === 'VOLTAGE_AC_220') {
                    $formData['voltageValue'] = 220;
                } elseif ($formData['voltage'] === 'VOLTAGE_AC_380') {
                    $formData['voltageValue'] = 380;
                } else {
                    $formData['voltageValue'] = 12;
                }
            }
            $result = calculateResult($formData, $materials, $sections);
        }
    }
}

include 'template.php';
