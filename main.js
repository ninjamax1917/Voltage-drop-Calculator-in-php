function toggleFields() {
  var method = document.getElementById("method").value;
  var voltageType = document.getElementById("voltage").value;
  var isDC = voltageType === "VOLTAGE_DC";
  document.getElementById("current-fields").style.display =
    method === "current" ? "block" : "none";
  document.getElementById("power-fields").style.display =
    method === "power" ? "block" : "none";
  var cosifiLabel = document.querySelector(
    '#power-fields label:has([name="cosifi"])'
  );
  if (cosifiLabel) {
    cosifiLabel.style.display = isDC
      ? "none"
      : method === "power"
      ? "block"
      : "none";
  }
}

function setDefaultVoltage() {
  var voltageType = document.getElementById("voltage").value;
  var voltageValueField = document.getElementById("voltageValue");
  if (voltageType === "VOLTAGE_AC_220") {
    voltageValueField.value = 220;
  } else if (voltageType === "VOLTAGE_AC_380") {
    voltageValueField.value = 380;
  } else {
    voltageValueField.value = 12;
  }
}
window.onload = function () {
  toggleFields();
  setDefaultVoltage();
};

// AJAX отправка формы
function submitForm(event) {
  event.preventDefault();
  var form = document.getElementById("calcForm");
  var formData = new FormData(form);

  fetch("", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.text())
    .then((html) => {
      // Получаем только блок результата из ответа
      var parser = new DOMParser();
      var doc = parser.parseFromString(html, "text/html");
      var resultBlock = doc.querySelector(".result");
      document.getElementById("result-block").innerHTML = resultBlock
        ? resultBlock.outerHTML
        : "";
    });
}
