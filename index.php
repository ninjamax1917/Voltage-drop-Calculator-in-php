<?php

include_once 'vendor/autoload.php';

$voltage = [
    'VOLTAGE_AC_220' => '220 В (AC)',
    'VOLTAGE_AC_380' => '380 В (AC)',
    'VOLTAGE_DC' => 'Постоянный ток (DC)'
]; //тип тока: однофазный ток, трехфазный ток, постоянный ток

$method = [
    'current' => 'По току',
    'power' => 'По мощности'
]; //метод расчета: по току или по мощности

$voltageValue; //значение напряжения

$materials = [
    'Медь' => 0.0175,
    'Алюминий' => 0.0282
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



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voltage_key = $_POST['voltage'] ?? 'VOLTAGE_AC_220';
    $voltage_label = $voltage[$voltage_key] ?? '220 В (AC)';
    $method_key = $_POST['method'] ?? 'current';
    $method_label = $method[$method_key] ?? 'По току';
    $materials = floatval($_POST['materials']);
    $length = floatval($_POST['length']);
    $sections = floatval($_POST['sections']);
}
    if ($method_key === 'current') {
        $current = floatval($_POST['current']);
    } elseif ($method_key === 'power') {
        $power = floatval($_POST['power']);
        $cosifi = floatval($_POST['cosifi']);
    }

    // Определение напряжения на основе выбора пользователя
    switch ($voltage_key) {
        case 'VOLTAGE_AC_220':
            $voltageValue = 220;
            break;
        case 'VOLTAGE_AC_380':
            $voltageValue = 380;
            break;
        case 'VOLTAGE_DC':
            $voltageValue = 220; // Предполагаем стандартное значение для DC
            break;
        default:
            $voltageValue = 220; // Значение по умолчанию
    }

    // Расчет силы тока, если выбран метод по мощности
    if ($method_key === 'power' && isset($power) && isset($cosifi) && $cosifi > 0) {
        if ($voltage_key === 'VOLTAGE_AC_380') {
            // Для трехфазного тока
            $current = $power / (sqrt(3) * $voltageValue * $cosifi);
        } else {
            // Для однофазного тока и постоянного тока
            $current = $power / ($voltageValue * $cosifi);
        }
    }
