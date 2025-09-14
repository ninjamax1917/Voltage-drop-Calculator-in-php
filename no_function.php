<?php

declare(strict_types=1);

include_once 'vendor/autoload.php';

$devMode = true; // или false для продакшена

$voltage = [
    'VOLTAGE_AC_220' => 'Однофазный переменный ток',
    'VOLTAGE_AC_380' => 'Трехфазный переменный ток',
    'VOLTAGE_DC' => 'Постоянный ток'
];

$method = [
    'current' => 'По току',
    'power' => 'По мощности'
];

$voltageValue;

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

$current;
$power;
$temperature;
$length;
$cosifi;
$result = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voltageType = $_POST['voltage'] ?? '';
    $methodType = $_POST['method'] ?? '';
    $material = $_POST['material'] ?? '';
    $section = $_POST['section'] ?? '';
    $temperature = $_POST['temperature'] ?? '';
    $length = $_POST['length'] ?? '';
    $cosifi = $_POST['cosifi'] ?? '';
    $voltageValue = $_POST['voltageValue'] ?? '';
    $current = $_POST['current'] ?? '';
    $power = $_POST['power'] ?? '';

    // Валидация
    if (!isset($voltage[$voltageType])) {
        $errors[] = 'Выберите тип тока.';
    }
    if (!isset($method[$methodType])) {
        $errors[] = 'Выберите метод расчета.';
    }
    if (!isset($materials[$material])) {
        $errors[] = 'Выберите материал.';
    }
    if (!isset($sections[$section])) {
        $errors[] = 'Выберите площадь сечения.';
    }
    if (!is_numeric($temperature) || $temperature < -50 || $temperature > 100) {
        $errors[] = 'Введите корректную температуру.';
    }
    if (!is_numeric($length) || $length <= 0) {
        $errors[] = 'Введите корректную длину кабеля.';
    }
    if (!is_numeric($voltageValue) || $voltageValue <= 0) {
        $errors[] = 'Введите корректное напряжение.';
    }
    if ($methodType === 'current') {
        if (!isset($current) || !is_numeric($current) || $current <= 0) {
            $errors[] = 'Введите корректную силу тока.';
        }
    } else {
        if (!isset($power) || !is_numeric($power) || $power <= 0) {
            $errors[] = 'Введите корректную мощность.';
        }
        if ($voltageType !== 'VOLTAGE_DC' && (!is_numeric($cosifi) || $cosifi <= 0 || $cosifi > 1)) {
            $errors[] = 'Введите корректный коэффициент мощности.';
        }
    }

    // Расчёт
    if (!$errors) {
        if ($voltageValue === '') {
            if ($voltageType === 'VOLTAGE_AC_220') {
                $voltageValue = 220;
            } elseif ($voltageType === 'VOLTAGE_AC_380') {
                $voltageValue = 380;
            } else {
                $voltageValue = 12;
            }
        }
        $temperature = floatval($temperature);
        $length = floatval($length);
        $cosifi = floatval($cosifi);
        $voltageValue = floatval($voltageValue);

        if ($methodType === 'current') {
            $current = floatval($current);
            $power = ($voltageValue * $current) / 1000;
        } else {
            $power = floatval($power);
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

        $result = [
            'voltageDrop_number' => round($voltageDrop, 2),
            'voltageDrop_percent' => round($voltageDropPercent, 2),
            'current' => round($current, 2),
            'power' => round($power, 2),
        ];
    }
}

include 'template.php';
