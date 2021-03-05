<?php
declare(strict_types=1);

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
    protected $dates = [];

    protected $fillable = [
        'from',
        'to',
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
            $downloadedExchangeRate = $this->downloadExchangeRate();
            if ($downloadedExchangeRate['date'] !== $this->date) {
                throw new ApiExchangeException("Exchange rate not found for {$this->date}");
            }

            return $this->insertExchangeRate($downloadedExchangeRate);
        }

        return $exchangeRate;
    }

    public function getExchangeRateJSON(): ExchangeResource
    {
        $exchangeRate = $this->getExchangeRate();
        $exchangeResource = new ExchangeResource($exchangeRate);
        $exchangeResource->additional([
            'from' => $this->from,
            'to' => $this->to,
        ]);

        return $exchangeResource;
    }

    public function getExchangeRates()
    {
        $this->validateExchangeRates();
        if (!($exchangeRates = $this->existExchangeRates())) {
            $downloadedExchangeRates = $this->downloadExchangeRates();

            return $this->insertExchangeRates($downloadedExchangeRates);
        }

        return $exchangeRates;
    }

    public function getExchangeRatesJSON(): ExchangeCollection
    {
        $exchangeRates = $this->getExchangeRates();
        $exchangeCollection = new ExchangeCollection($exchangeRates);

        return $exchangeCollection;
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

    public function downloadExchangeRates()
    {
        $startDate = reset($this->dates);
        $endDate = end($this->dates);

        $requestUrl = "https://api.exchangeratesapi.io/history?symbols={$this->to}&base={$this->from}&start_at={$startDate}&end_at={$endDate}";
        $response = Http::get($requestUrl);
        $responseData = $response->json();
        if ($response->failed()) {
            throw new ApiExchangeException($responseData['error']);
        }

        return $responseData;
    }

    public function insertExchangeRate(array $data): Exchange
    {
        $this->rate = $data['rates'][$this->to];
        $this->save();

        return $this;
    }

    public function insertExchangeRates(array $data)
    {
        if ($data) {
            $insertData = [];
            $exchangeCollection = collect();

            foreach ($data['rates'] as $date => $rate) {
                $insertData[] = [
                    'from' => $this->from,
                    'to' => $this->to,
                    'date' => $date,
                    'rate' => $rate[$this->to],
                ];

                $exchangeCollection->add(new self(end($insertData)));
            }

            foreach (array_chunk($insertData, 100) as $exchangeRate) {
                $this->insertOrIgnore($exchangeRate);
            }

            return $exchangeCollection;
        }

        throw new ApiExchangeException('Failed to get exchange rate');
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

    public function existExchangeRates()
    {
        $dates = $this->getDateArrayFromPeriod();

        $exchangeRates = $this->where('from', $this->from)
            ->where('to', $this->to)
            ->whereIn('date', $dates)
            ->get();

        if ($exchangeRates->count() !== count($dates)) {
            return false;
        }

        return $exchangeRates;
    }

    private function getDateArrayFromPeriod(): array
    {
        $endDate = new \DateTime($this->datePeriodTo);
        $endDate->modify('+1 day');

        $dateInterval = new \DatePeriod(
            new \DateTime($this->datePeriodFrom),
            new \DateInterval('P1D'),
            $endDate,
        );

        foreach ($dateInterval as $date) {
            $this->dates[] = $date->format('Y-m-d');
        }

        return $this->dates;
    }
}
