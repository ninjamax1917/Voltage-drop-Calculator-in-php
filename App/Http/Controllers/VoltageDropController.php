<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VoltageDropCalculator;

class VoltageDropController extends Controller
{
    public function showForm()
    {
        return view('voltage_drop.form', [
            'voltage' => VoltageDropCalculator::$voltage,
            'method' => VoltageDropCalculator::$method,
            'materials' => VoltageDropCalculator::$materials,
            'sections' => VoltageDropCalculator::$sections,
        ]);
    }

    public function calculate(Request $request)
    {
        $calculator = new VoltageDropCalculator();
        $formData = $request->all();
        $rules = $calculator->getValidationRules($formData);

        $errors = $calculator->validateInput($formData, $rules);

        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        if (empty($formData['voltageValue'])) {
            $formData['voltageValue'] = $calculator->getDefaultVoltage($formData['voltage']);
        }

        $result = $calculator->calculateResult($formData);

        return view('voltage_drop.result', compact('result'));
    }
}
