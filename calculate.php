<?php

include_once 'vendor/autoload.php';

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
    $voltageType = $_POST['voltage'];
    if (isset($_POST['voltageValue']) && $_POST['voltageValue'] !== '') {
        $voltageValue = floatval($_POST['voltageValue']);
    } elseif ($voltageType === 'VOLTAGE_AC_220') {
        $voltageValue = 220;
    } elseif ($voltageType === 'VOLTAGE_AC_380') {
        $voltageValue = 380;
    } else {
        $voltageValue = 12;
    }
    $methodType = $_POST['method'];
    $material = $_POST['material'];
    $section = $_POST['section'];
    $temperature = floatval($_POST['temperature']);
    $length = floatval($_POST['length']);
    $cosifi = floatval($_POST['cosifi']);

    if ($methodType === 'current') {
        $current = floatval($_POST['current']);
        $power = ($voltageValue * $current) / 1000; //мощность в кВт, без cos φ
    } else {
        $power = floatval($_POST['power']);
        if ($voltageType === 'VOLTAGE_DC') {
            $current = $power / $voltageValue; // для DC cos φ не нужен
        } else {
            $current = $power / ($voltageValue * $cosifi); // для AC учитываем cos φ
        }
    }

    // Расчет падения напряжения
    $resistivity = $materials[$material]; //удельное сопротивление материала
    $sectionValue = $sections[$section]; //площадь сечения кабеля

    // Корректировка удельного сопротивления в зависимости от температуры
    if ($temperature != 20) {
        $resistivity *= (1 + 0.004 * ($temperature - 20));
    }

    // Формула для расчета падения напряжения: ΔU = (2 * L * I * ρ) / S × cos φ
    // Для трехфазного тока формула будет: ΔU = (√3 * L * I * ρ) / S × cos φ
    if ($voltageType === 'VOLTAGE_AC_380') {
        $voltageDrop = (sqrt(3) * $length * $current * $resistivity) / $sectionValue;
    } else {
        $voltageDrop = (2 * $length * $current * $resistivity) / $sectionValue;
    }

    // Расчет процента падения напряжения
    $voltageDropPercent = ($voltageDrop / $voltageValue) * 100;

    $result = [
        'voltageDrop_number' => round($voltageDrop, 2),
        'voltageDrop_percent' => round($voltageDropPercent, 2),
    ];
}
?>


<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Расчет кабеля</title>
    <style>
        form label {
            display: block;
            margin-bottom: 10px;
        }

        .result {
            margin-top: 20px;
            padding: 10px;
            background: #f0f0f0;
        }
    </style>
    <script>
        function toggleFields() {
            var method = document.getElementById('method').value;
            var voltageType = document.getElementById('voltage').value;
            var isDC = voltageType === 'VOLTAGE_DC';
            document.getElementById('current-fields').style.display = method === 'current' ? 'block' : 'none';
            document.getElementById('power-fields').style.display = method === 'power' ? 'block' : 'none';
            var cosifiLabel = document.querySelector('#power-fields label:has([name="cosifi"])');
            if (cosifiLabel) {
                cosifiLabel.style.display = isDC ? 'none' : (method === 'power' ? 'block' : 'none');
            }
        }

        function setDefaultVoltage() {
            var voltageType = document.getElementById('voltage').value;
            var voltageValueField = document.getElementById('voltageValue');
            if (voltageType === 'VOLTAGE_AC_220') {
                voltageValueField.value = 220;
            } else if (voltageType === 'VOLTAGE_AC_380') {
                voltageValueField.value = 380;
            } else {
                voltageValueField.value = 12;
            }
        }
        window.onload = function() {
            toggleFields();
            setDefaultVoltage();
        };

        // AJAX отправка формы
        function submitForm(event) {
            event.preventDefault();
            var form = document.getElementById('calcForm');
            var formData = new FormData(form);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Получаем только блок результата из ответа
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var resultBlock = doc.querySelector('.result');
                    document.getElementById('result-block').innerHTML = resultBlock ? resultBlock.outerHTML : '';
                });
        }
    </script>
</head>

<body>
    <form id="calcForm" action="" method="post" onsubmit="submitForm(event)">
        <label>
            Тип тока:
            <select name="voltage" id="voltage" onchange="setDefaultVoltage(); toggleFields();">
                <?php foreach ($voltage as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"
                        <?= (isset($_POST['voltage']) && $_POST['voltage'] === $key) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Напряжение сети, В:
            <input type="number" step="any" name="voltageValue" id="voltageValue"
                value="<?= isset($_POST['voltageValue']) ? htmlspecialchars($_POST['voltageValue']) : '220' ?>">
        </label>
        <label>
            Метод расчета:
            <select name="method" id="method" onchange="toggleFields()">
                <?php foreach ($method as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"
                        <?= (isset($_POST['method']) && $_POST['method'] === $key) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Материал:
            <select name="material">
                <?php foreach ($materials as $name => $value): ?>
                    <option value="<?= htmlspecialchars($name) ?>"
                        <?= (isset($_POST['material']) && $_POST['material'] === $name) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Длина кабеля (м):
            <input type="number" step="any" name="length"
                value="<?= isset($_POST['length']) ? htmlspecialchars($_POST['length']) : '' ?>">
        </label>
        <label>
            Площадь сечения:
            <select name="section">
                <?php foreach ($sections as $name => $value): ?>
                    <option value="<?= htmlspecialchars($name) ?>"
                        <?= (isset($_POST['section']) && $_POST['section'] === $name) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div id="current-fields">
            <label>
                Сила тока (A):
                <input type="number" step="any" name="current"
                    value="<?= isset($_POST['current']) ? htmlspecialchars($_POST['current']) : '' ?>">
            </label>
        </div>
        <div id="power-fields">
            <label>
                Мощность (Вт):
                <input type="number" step="any" name="power"
                    value="<?= isset($_POST['power']) ? htmlspecialchars($_POST['power']) : '' ?>">
            </label>
            <label>
                Коэффициент мощности (cos φ):
                <input type="number" step="any" name="cosifi" min="0" max="1"
                    value="<?= isset($_POST['cosifi']) ? htmlspecialchars($_POST['cosifi']) : '' ?>">
            </label>
        </div>
        <label>
            Температура кабеля (°C):
            <input type="number" step="any" name="temperature"
                value="<?= isset($_POST['temperature']) ? htmlspecialchars($_POST['temperature']) : '20' ?>">
        </label>
        <button type="submit">Рассчитать</button>
    </form>
    <div id="result-block">
        <?php if ($result): ?>
            <div class="result">
                <strong>Результаты расчета:</strong><br>
                Падение напряжения: <?= $result['voltageDrop_number'] ?> В
                (<?= $result['voltageDrop_percent'] ?> %)<br>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>