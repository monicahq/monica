<?php

namespace App\ExportResources;

use Illuminate\Support\Arr;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Http\Resources\Json\JsonResource;

class ExportResource extends JsonResource
{
    /**
     * The resource instance.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * The resource instance.
     *
     * @var array
     */
    protected $properties = null;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    final public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return CountResourceCollection|MissingValue
     */
    public static function countCollection($resource)
    {
        if ($resource->count() === 0) {
            return new MissingValue();
        }

        return tap(new CountResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return MapUuidResourceCollection|MissingValue
     */
    public static function uuidCollection($resource)
    {
        if ($resource->count() === 0) {
            return new MissingValue();
        }

        return tap(new MapUuidResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if (is_null($this->resource)) {
            return [];
        }

        return is_array($this->resource)
            ? $this->resource
            : $this->export($this->columns, $this->properties, $this->data());
    }

    /**
     * @return array|null
     */
    public function data(): ?array
    {
        return null;
    }

    /**
     * Create the Insert query for the given table.
     *
     * @param  array  $columns
     * @param  array  $properties
     * @param  array  $data
     * @return array|null
     */
    protected function export(array $columns, array $properties = null, array $data = null): ?array
    {
        $result = [];

        if (! $this->resource->exists()) {
            return null;
        }

        foreach ($columns as $column) {
            $result[$column] = $this->{$column};
        }

        if ($data !== null) {
            foreach ($data as $key => $value) {
                if (isset($result[$key]) && is_array($result[$key])) {
                    $result[$key] = array_merge($result[$key], $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        if ($properties !== null) {
            $result['properties'] = array_merge(collect($properties)->mapWithKeys(function ($item, $key) {
                return ($value = $this->{$item}) !== null ? [$item => $value] : new MissingValue();
            })->toArray(), Arr::get($result, 'properties', []));
        }

        return $result;
    }
}
