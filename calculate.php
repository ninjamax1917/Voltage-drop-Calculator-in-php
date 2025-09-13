<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Расчет кабеля</title>
    <style>
        form label { display: block; margin-bottom: 10px; }
        .hidden { display: none; }
    </style>
    <script>
        function toggleFields() {
            var method = document.getElementById('method').value;
            document.getElementById('current-fields').style.display = method === 'current' ? 'block' : 'none';
            document.getElementById('power-fields').style.display = method === 'power' ? 'block' : 'none';
        }
        window.onload = toggleFields;
    </script>
</head>
<body>
    <form action="index.php" method="post">
        <label>
            Тип тока:
            <select name="voltage">
                <?php foreach ($voltage as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Метод расчета:
            <select name="method" id="method" onchange="toggleFields()">
                <?php foreach ($method as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Материал:
            <select name="p">
                <?php foreach ($materials as $name => $value): ?>
                    <option value="<?= $value ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Длина кабеля (м):
            <input type="number" step="any" name="L">
        </label>
        <label>
            Площадь сечения:
            <select name="S">
                <?php foreach ($sections as $name => $value): ?>
                    <option value="<?= $value ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div id="current-fields">
            <label>
                Сила тока (A):
                <input type="number" step="any" name="I">
            </label>
        </div>
        <div id="power-fields">
            <label>
                Мощность (Вт):
                <input type="number" step="any" name="power">
            </label>
            <label>
                Коэффициент мощности (cos φ):
                <input type="number" step="any" name="cosifi" min="0" max="1">
            </label>
        </div>
        <button type="submit">Рассчитать</button>
    </form>
</body>
</html>