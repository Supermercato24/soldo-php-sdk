<?php

namespace Soldo\Resources;

use Soldo\Exceptions\SoldoInvalidPathException;
use Soldo\Exceptions\SoldoInvalidRelationshipException;
use Soldo\Exceptions\SoldoInvalidResourceException;
use Soldo\Validators\ResourceValidatorTrait;

//use Soldo\Utils\Validator;

/**
 * Class Resource
 * @package Soldo\Resources
 */
abstract class Resource
{
    use ResourceValidatorTrait;

    /**
     * Remote path of resource list
     *
     * @var string
     */
    protected static $basePath;

    /**
     * Remote path of the single resources
     *
     * @var string
     */
    protected $path;

    /**
     * List of resource attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * List of attributes that can be updated
     *
     * @var array
     */
    protected $whiteListed = [];

    /**
     * An array containing a map of the resource relationships
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * An array containing the list of attributes that need to be casted into a Resource
     *
     * @var array
     */
    protected $cast = [];

    /**
     * Resource constructor.
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->fill($data);
    }

    /**
     * Populate resource attribute with the array provided
     *
     * @param array $data
     * @return $this
     */
    public function fill($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException(
                'Trying to fill resource with malformed data'
            );
        }

        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * Return true if the attribute need to be casted
     *
     * @param $attributeName
     * @return bool
     */
    private function hasToCast($attributeName)
    {
        return array_key_exists($attributeName, $this->cast);
    }

    /**
     * Set attribute with the name and value provided
     * Attributes cast happens here
     *
     * @param $name
     * @param $value
     * @throws SoldoInvalidResourceException
     */
    public function __set($name, $value)
    {
        if ($this->hasToCast($name)) {
            $className = $this->cast[$name];
            $this->validateClassName($className);

            $this->attributes[$name] = new $className($value);

            return;
        }

        $this->attributes[$name] = $value;
    }

    /**
     * Return a given attribute
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * Build an array representation of the resource
     * Also call toArray on casted attributes
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = [];
        foreach ($this->attributes as $key => $value) {
            /** @var resource $value */
            if (array_key_exists($key, $this->cast)) {
                $attributes[$key] = $value->toArray();
                continue;
            }
            $attributes[$key] = $value;
        }

        return $attributes;
    }

    /**
     * Get full remote path of the single resource
     *
     * @throws SoldoInvalidPathException
     * @return string
     */
    final public function getRemotePath()
    {
        $basePath = self::getBasePath();

        if ($this->path === null) {
            return $basePath;
        }

        $resourcePath = $this->getResourcePath();

        return $basePath . $resourcePath;
    }

    /**
     * Extract an array of resources data from the rawData returned by the API
     *
     * @param $rawData
     * @param $relationshipName
     * @throws SoldoInvalidRelationshipException
     * @return mixed
     */
    private function getRelationshipData($rawData, $relationshipName)
    {
        if (!is_array($rawData) || !array_key_exists($relationshipName, $rawData)) {
            throw new SoldoInvalidRelationshipException(
                'Trying to build a relationship with invalid data'
            );
        }

        return $rawData[$relationshipName];
    }

    /**
     * Build and return an array of Resource
     *
     * @param $relationshipName
     * @param $rawData
     * @return array
     */
    public function buildRelationship($relationshipName, $rawData)
    {
        $className = $this->getRelationshipClass($relationshipName);
        $this->validateClassName($className);

        $data = $this->getRelationshipData($rawData, $relationshipName);

        $relationship = [];
        foreach ($data as $relationshipData) {
            $relationship[] = new $className($relationshipData);
        }

        return $relationship;
    }

    /**
     * Get relationship class given the relationship name
     *
     * @param $relationshipName
     * @throws SoldoInvalidRelationshipException
     * @return mixed
     */
    private function getRelationshipClass($relationshipName)
    {
        if (!array_key_exists($relationshipName, $this->relationships)) {
            throw new SoldoInvalidRelationshipException(
                'Relationship ' . $relationshipName . ' is not defined'
            );
        }

        $class = $this->relationships[$relationshipName];

        return $class;
    }

    /**
     * Get relationship remote path
     *
     * @param string $relationshipName
     * @throws SoldoInvalidRelationshipException
     * @return string
     */
    public function getRelationshipRemotePath($relationshipName)
    {
        $className = $this->getRelationshipClass($relationshipName);
        $this->validateClassName($className);

        $relationshipPath = call_user_func([$className, 'getBasePath']);

        return $this->getRemotePath() . $relationshipPath;
    }

    /**
     * Remove all not whitelisted key from the array
     *
     * @param array $data
     * @return array
     */
    public function filterWhiteList($data)
    {
        return array_intersect_key($data, array_flip($this->whiteListed));
    }

    /**
     * Build a full qualified path replacing {string} occurrence
     * with $this->{string} attribute
     *
     * @throws SoldoInvalidPathException
     * @return mixed
     */
    private function getResourcePath()
    {
        if (@preg_match('/^\/[\S]+$/', $this->path) !== 1) {
            throw new SoldoInvalidPathException(
                static::class . ' basePath seems to be invalid'
            );
        }

        $remotePath = $this->path;
        preg_match_all('/\{(\S+?)\}/', $this->path, $parts);
        foreach ($parts[1] as $key => $attributeName) {
            if ($this->{$attributeName} === null) {
                throw new SoldoInvalidPathException(
                    static::class . ' ' . $attributeName . ' is not defined'
                );
            }

            $remotePath = str_replace(
                $parts[0][$key],
                urlencode($this->{$attributeName}),
                $remotePath
            );
        }

        return $remotePath;
    }

    /**
     * Get base path
     *
     * @throws SoldoInvalidPathException
     * @return string
     */
    final public static function getBasePath()
    {
        if (static::$basePath === null ||
            @preg_match('/^\/[\S]+$/', static::$basePath) !== 1) {
            throw new SoldoInvalidPathException(
                static::class . ' basePath seems to be invalid'
            );
        }

        return static::$basePath;
    }
}
