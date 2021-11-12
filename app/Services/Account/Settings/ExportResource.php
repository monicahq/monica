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
            : $this->getOneData($this->columns, $this->properties, $this->data());
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
     * @param  string  $tableName
     * @param  array  $foreignKey
     * @param  array  $columns
     * @return array|null
     */
    protected function getOneData(array $columns, array $properties = null, array $data = null): ?array
    {
        $result = [];

        if (! $this->resource->exists()) {
            return null;
        }

        foreach ($columns as $column) {
            $result[$column] = $this->{$column} ?? $this->resource->getAttributeValue($column);
        }

        if ($properties !== null) {
            $result['properties'] = [];
            foreach ($properties as $property) {
                $result['properties'][$property] = $this->{$property};
                // $this->setSimpleProperty($result, $property, $this->resource);
            }
        }

        // $result[] = $this->mergeWhen($data != null, $data);
        if ($data !== null) {
            // if (! isset($result->properties)) {
            //     $result->properties = [];
            // }

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

    // protected function setSimpleProperty(object $obj, string $name, ?string $prop = null): bool
    // {
    //     if ($this->resource === null || ! $this->resource->exists()) {
    //         return false;
    //     }

    //     $prop = $prop ?? $name;

    //     $value = $this->{$prop} ?? $this->resource->getAttributeValue($prop);
    //     if ($value === null) {
    //         return false;
    //     }

    //     $obj->properties[$name] = $value;

    //     return true;
    // }

    // protected function setComplexProperty(string $name, array $values, ?string $type = 'array', ?string $prop = null)
    // {
    //     if ($this->resource === null) {
    //         return null;
    //     }

    //     $prop = $prop ?? $name;

    //     $result = new class {};
    //     $result->type = $type ?? 'array';
    //     $result->properties = [];

    //     $data = $this->resource->{$prop} ?? $this->resource->getAttributeValue($prop);
    //     if ($data === null) {
    //         return null;
    //     }

    //     foreach ($values as $value => $type) {
    //         if (is_int($value)) {
    //             $value = $type;
    //             $type = null;
    //         }
    //         $this->setSimpleProperty($result, $value, $data, $type);
    //     }

    //     return $result;
    // }
}
