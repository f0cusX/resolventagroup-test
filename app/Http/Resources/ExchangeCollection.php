<?php

namespace App\Http\Resources;

use App\Models\Exchange;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ExchangeCollection extends ResourceCollection
{
    public static $wrap = 'rate';

    public $collects = Exchange::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'from' => $request->from,
            'to' => $request->to,
            'rate' => $this->collection,
        ];
    }
}
