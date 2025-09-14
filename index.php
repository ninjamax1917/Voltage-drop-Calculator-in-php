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

$results = []; //массив для хранения результатов расчета

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из формы
    $voltageType = $_POST['voltage'];
    $methodType = $_POST['method'];
    $material = $_POST['material'];
    $section = $_POST['section'];
    $temperature = floatval($_POST['temperature']);
    $length = floatval($_POST['length']);
    $cosifi = floatval($_POST['cosifi']);

    if ($voltageType === 'VOLTAGE_AC_220') {
        $voltageValue = 220;
    } elseif ($voltageType === 'VOLTAGE_AC_380') {
        $voltageValue = 380;
    } else {
        $voltageValue = 12; //для постоянного тока берем стандартное значение 12В
    }

    if ($methodType === 'current') {
        $current = floatval($_POST['current']);
        $power = ($voltageValue * $current * $cosifi) / 1000; //мощность в кВт
    } else {
        $power = floatval($_POST['power']);
        $current = ($power * 1000) / ($voltageValue * $cosifi); //ток в А
    }

    // Расчет падения напряжения
    $resistivity = $materials[$material]; //удельное сопротивление материала
    $sectionValue = $sections[$section]; //площадь сечения кабеля

    // Корректировка удельного сопротивления в зависимости от температуры
    if ($temperature != 20) {
        $resistivity *= (1 + 0.004 * ($temperature - 20));
    }

    // Формула для расчета падения напряжения: ΔU = (2 * L * I * ρ) / S
    // Для трехфазного тока формула будет: ΔU = (√3 * L * I * ρ) / S
    if ($voltageType === 'VOLTAGE_AC_380') {
        $voltageDrop = (sqrt(3) * $length * $current * $resistivity) / $sectionValue;
    } else {
        $voltageDrop = (2 * $length * $current * $resistivity) / $sectionValue;
    }
}
