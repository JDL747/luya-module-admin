<?php
namespace admin\ngrest;

use yii\helpers\ArrayHelper;

/**
 *
 * ['list',
 *     [
 *         'firstname' => [
 *             'name' => 'firstname',
 *             'alias' => 'Vorname',
 *             'plugins' => [
 *                 'class' => '\\luya\\ngrest\\plugins\\Dropdown',
 *                 'args' => ['arg1' => 'arg1_value', 'arg2' => 'arg2_value']
 *             ]
 *         ]
 *     }
 * ];
 *
 * @author nadar
 *
 */
class Config implements ConfigInterface
{
    private $config = [];

    private $pointer = [];

    private $options = [];

    private $restUrlPrefix = 'admin/'; /* could be: http://www.yourdomain.com/admin/; */

    public function __construct($restUrl, $restPrimaryKey, $options = [])
    {
        $this->restUrl = $this->restUrlPrefix.$restUrl;
        $this->restPrimaryKey = $restPrimaryKey;
        $this->options = $options;
        $this->list->field($restPrimaryKey, "ID")->text();
    }

    public function __set($key, $value)
    {
        if (!array_key_exists($key, $this->config)) {
            $this->config[$key] = $value;
        }
    }

    public function __get($key)
    {
        $this->$key = [];
        $this->pointer['key'] = $key;

        return $this;
    }

    public function __call($name, $args)
    {
        $this->config[$this->pointer['key']][$this->pointer['field']]['plugins'][] = [
            'class' => '\\admin\\ngrest\\plugins\\'.ucfirst($name), 'args' => $args,
        ];

        return $this;
    }

    public function field($name, $alias)
    {
        $this->config[$this->pointer['key']][$name] = [
            'name' => $name, 'alias' => $alias, 'plugins' => [],
        ];
        $this->pointer['field'] = $name;

        return $this;
    }

    public function copyFrom($key, $removeFields = [])
    {
        $temp = $this->config[$key];
        foreach ($removeFields as $name) {
            if (!array_key_exists($name, $temp)) {
                throw new \Exception("Error"); // @todo create exception class
            }
            unset($temp[$name]);
        }
        $this->config[$this->pointer['key']] = $temp;
    }

    public function register($strapObject, $alias)
    {
        $strapClass = get_class($strapObject);
        $strapHash = sha1($this->getNgRestConfigHash().$strapClass);
        $this->config[$this->pointer['key']][$strapHash] = [
            'object' => $strapObject,
            'strapHash' => $strapHash,
            'class' => $strapClass,
            'alias' => $alias,
            'on' => [], // remove fully
        ];
        $this->pointer['register'] = $strapHash;

        return $this;
    }

    public function on($field, $strapMapName)
    {
        $config = $this->config[$this->pointer['key']][$this->pointer['register']];
        $on = ArrayHelper::merge([$field => $strapMapName], $config['on']);
        $config = $this->config[$this->pointer['key']][$this->pointer['register']]['on'] = $on;

        return $this;
    }

    public function get()
    {
        return $this->config;
    }

    public function getOption($key, $defaultValue = '')
    {
        return (isset($this->options[$key])) ? $this->options[$key] : $defaultValue;
    }

    public function getKey($key)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        return [];
    }

    public function getRestUrl()
    {
        return $this->config['restUrl'];
    }

    public function getRestPrimaryKey()
    {
        return $this->config['restPrimaryKey'];
    }

    public function getNgRestConfigHash()
    {
        return ucfirst(sha1($this->config['restUrl'].$this->config['restPrimaryKey']));
    }
}
