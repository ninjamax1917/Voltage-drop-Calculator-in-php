<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Расчет кабеля</title>
    <script src="main.js"></script>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <form id="calcForm" action="calculate_procedural_style.php" method="post" <?php if (!$devMode): ?>onsubmit="submitForm(event)" <?php endif; ?>>
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
        <?php if (!empty($errors)): ?>
            <div class="result" style="color:red;">
                <?php foreach ($errors as $error): ?>
                    <?= htmlspecialchars($error) ?><br>
                <?php endforeach; ?>
            </div>
        <?php elseif ($result): ?>
            <div class="result">
                <strong>Результаты расчета:</strong><br>
                Падение напряжения (ΔU, В(%)): <?= $result['voltageDrop_number'] ?> В
                (<?= $result['voltageDrop_percent'] ?> %)<br>
                <?php if ($_POST['method'] === 'power'): ?>
                    Сила тока (I, А): <?= $result['current'] ?> А<br>
                <?php elseif ($_POST['method'] === 'current'): ?>
                    Мощность (P, кВт): <?= $result['power'] ?> кВт<br>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>