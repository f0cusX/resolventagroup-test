<?php

namespace App\Http\Resources;

use App\Models\Exchange;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeResource extends JsonResource
{

    public $resource = Exchange::class;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'date' => $this->date,
            'rate' => $this->rate,
        ];
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return tap(response()->json(
            array_merge_recursive(
                $this->resolve($request),
                $this->with($request),
                $this->additional
            )
        ), function ($response) use ($request) {
            $response->original = $this->resource;

            $this->withResponse($request, $response);
        });

    }
}
