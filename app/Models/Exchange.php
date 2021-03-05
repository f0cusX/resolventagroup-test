<?php

namespace App\Models;

use App\Exceptions\ApiExchangeException;
use App\Exceptions\InvalidExchangeException;
use App\Http\Resources\ExchangeCollection;
use App\Http\Resources\ExchangeResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class Exchange extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'to',
        'from',
        'date',
        'rate',
        'datePeriodFrom',
        'datePeriodTo'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (is_null($this->date)) {
            $this->date = date('Y-m-d');
        }
    }

    public function getExchangeRate()
    {
        $this->validateExchangeRate();

        if (!($exchangeRate = $this->existExchangeRate())) {
            if ($downloadedExchangeRate = $this->downloadExchangeRate()) {
                if ($downloadedExchangeRate['date'] !== $this->date) {
                    throw new ApiExchangeException("Exchange rate not found for {$this->date}");
                }

                return $this->insertExchangeRate($downloadedExchangeRate);
            }

            throw new ApiExchangeException('Failed to get exchange rate');
        }

        return $exchangeRate;
    }

    public function getExchangeRateJSON():ExchangeResource
    {
        $exchangeRate = $this->getExchangeRate();
        $exchangeResource = new ExchangeResource($exchangeRate);
        $exchangeResource->additional([
            'from' => $this->from,
            'to' => $this->to,
        ]);

        return $exchangeResource;
    }



    public function downloadExchangeRate()
    {
        $requestUrl = "https://api.exchangeratesapi.io/{$this->date}?symbols={$this->to}&base={$this->from}";
        $response = Http::get($requestUrl);
        $responseData = $response->json();
        if ($response->failed()) {
            throw new ApiExchangeException($responseData['error']);
        }

        return $responseData;
    }

    public function insertExchangeRate(array $data)
    {
        $this->rate = $data['rates'][$this->to];
        $this->save();

        return $this;
    }

    public function validateExchangeRate()
    {
        $validator = Validator::make($this->attributes, [
            'from' => 'required|min:3|max:3',
            'to' => 'required|min:3|max:3',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            throw new InvalidExchangeException($validator->errors()->toJson());
        }

        return true;
    }

    public function validateExchangeRates()
    {
        $validator = Validator::make($this->attributes, [
            'from' => 'required|min:3|max:3',
            'to' => 'required|min:3|max:3',
            'datePeriodFrom' => 'required|date_format:Y-m-d',
            'datePeriodTo' => 'required|date_format:Y-m-d|after_or_equal:datePeriodFrom',
        ]);

        if ($validator->fails()) {
            throw new InvalidExchangeException($validator->errors()->toJson());
        }

        return true;
    }

    public function existExchangeRate()
    {
        $exchangeRate = $this->where('from', $this->from)
            ->where('to', $this->to)
            ->where('date', $this->date)
            ->first();

        if (is_null($exchangeRate)) {
            return false;
        }

        return $exchangeRate;
    }
}
