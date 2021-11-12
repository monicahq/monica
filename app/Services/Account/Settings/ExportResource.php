<?php

namespace App\Services\Account\Settings;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Resources\DelegatesToResource;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\MissingValue;

class ExportResource implements ArrayAccess, JsonSerializable, UrlRoutable
{
    use ConditionallyLoadsAttributes, DelegatesToResource;

    /**
     * The resource instance.
     *
     * @var mixed
     */
    public $resource;

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
    protected $properties;

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    // public static $wrap = null;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public static function collection($resource)
    {
        return tap(new AnonymousResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Resolve the resource to an array.
     *
     * @return array
     */
    public function resolve()
    {
        $data = $this->toArray();

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array) $data);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray()
    {
        if (is_null($this->resource)) {
            return [];
        }

        return is_array($this->resource)
            ? $this->resource
            : $this->transform($this->columns, $this->properties, $this->data());
    }

    /**
     * @return array|null
     */
    public function data(): ?array
    {
        return null;
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forResource($this, json_last_error_msg());
        }

        return $json;
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->resolve();
    }

    /**
     * Create the Insert query for the given table.
     *
     * @param  array  $columns
     * @param  array  $properties
     * @param  array  $data
     * @return array|null
     */
    protected function transform(array $columns, array $properties = null, array $data = null): ?array
    {
        $result = [];

        if (! $this->resource->exists()) {
            return null;
        }

        foreach ($columns as $column) {
            if (($value = $this->{$column}) !== null) {
                $result[$column] = $value;
            }
        }

        if ($properties !== null) {
            $result['properties'] = collect($properties)->mapWithKeys(function ($item, $key) {
                return ($value = $this->{$item}) !== null ? [$item => $value] : new MissingValue();
            })->toArray();
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

        return $result;
    }
}
