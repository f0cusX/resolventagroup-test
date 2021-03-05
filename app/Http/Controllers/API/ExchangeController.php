<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\User;
use Illuminate\Http\Request;

class ExchangeController extends Controller
{
    public function getExchangeRateJSON(Request $request)
    {
        $data = $request->only('from', 'to', 'date');
        $exchange = new Exchange($data);

        return $exchange->getExchangeRateJSON();
    }
}
