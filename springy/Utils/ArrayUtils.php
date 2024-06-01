<?php

/**
 * Array manipulation utils.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 *
 * @version 1.1.3
 */

namespace Springy\Utils;

class ArrayUtils
{
    /**
     * Adiciona um valor ao array SOMENTE se já não houver valor na chave passada por parâmetro.
     *
     * @param array $array
     * @param mixed $key
     * @param mixed $value
     *
     * @return mixed
     */
    public function add($array, $key, $value)
    {
        if (!isset($array[$key])) {
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * Cria outro array com os dados filtrados por uma callback passada por parâmetro.
     *
     * @param array    $array    array de origem.
     * @param \Closure $callback Função de filtragem.
     *
     * @return array
     */
    public function make($array, \Closure $callback)
    {
        $results = [];

        foreach ($array as $key => $value) {
            list($filteredKey, $filteredValue) = call_user_func($callback, $key, $value);

            $results[$filteredKey] = $filteredValue;
        }

        return $results;
    }

    /**
     * Cria um array com todos os valures de uma determinada chave de um array associativo multi-dimentcional.
     *
     * @param array $array array de origem.
     * @param mixed $value Chave do valor a ser retirado de todos os elemntos do array.
     * @param mixed $key   Chave do valor a ser retirado de todos os elemntos do
     *                     array e colodo como chave do array criado.
     *
     * @return array
     */
    public function pluck($array, $value, $key = null)
    {
        $results = [];

        foreach ($array as $item) {
            $itemValue = is_object($item) ? $item->{$value} : $item[$value];

            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = is_object($item) ? $item->{$key} : $item[$key];

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Retorna um array com as chaves do array de origem na posição 0 e os
     * valores na posição 1 (ideal pra usar com o list().
     *
     * @param array $array
     *
     * @return void array
     */
    public function split($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Retorna um array somente com os valores das chaves passadas por parâmetro.
     *
     * @param array $array array de origem.
     * @param array $keys  array de chaves.
     *
     * @return array
     */
    public function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Retorna um array com todos os valores, exceto os que possuem as chaves passadas por parâmetro.
     *
     * @param array $array array de origem.
     * @param array $keys  array de chaves.
     *
     * @return array
     */
    public function except($array, $keys)
    {
        return array_diff_key($array, array_flip((array) $keys));
    }

    /**
     * Cria outro array com os dados ordenados por uma callback passado por parâmetro.
     *
     * @param array    $array    array de origem.
     * @param \Closure $callback Função de ordenagem.
     *
     * @return array
     */
    public function sort($array, \Closure $callback)
    {
        uasort($array, $callback);

        return $array;
    }

    /**
     * Retorna o PRIMEIRO valor que passa na função teste passado por parâmetro ou o valor padrão.
     *
     * @param array    $array    array de origem.
     * @param \Closure $callback Função de teste.
     * @param mixed    $default  Valor padrão de retorno.
     *
     * @return mixed valor que passou.
     */
    public function firstThatPasses($array, \Closure $callback, $default = null)
    {
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Retorna o ÚLTIMO valor que passa na função teste passado por parâmetro ou o valor padrão.
     *
     * @param array    $array    array de origem.
     * @param \Closure $callback Função de teste.
     * @param mixed    $default  Valor padrão de retorno.
     *
     * @return mixed valor que passou.
     */
    public function lastThatPasses($array, \Closure $callback, $default = null)
    {
        return $this->firstThatPasses(array_reverse($array), $callback, $default);
    }

    /**
     * Retorna o TODOS os valores que passaram na função teste passado por parâmetro.
     *
     * @param array    $array    array de origem.
     * @param \Closure $callback Função de teste.
     *
     * @return array valores que passaram.
     */
    public function allThatPasses($array, \Closure $callback)
    {
        $filtered = [];

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Retorna um array de somente um nível "achatando" um array multi-dimencional.
     *
     * @param array $array
     *
     * @return array
     */
    public function flatten($array)
    {
        $results = [];

        array_walk_recursive($array, function ($v) use (&$results) {
            $results[] = $v;
        });

        return $results;
    }

    /**
     * Retorna um array de somente um nível "achatando" um array multi-dimencional, mas com a notação de pontos.
     *
     * Exemplo: $array['key1']['key2] --> $array['key1.key2'].
     *
     * @param array  $array
     * @param string $prepend
     *
     * @return array
     */
    public function dottedMake($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $results = array_merge($results, $this->dottedMake($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Retorna o valor de um array utilizando a notação de pontos.
     *
     * @param array $array   array de origem.
     * @param mixed $key     Chave do valor requerido.
     * @param mixed $default Valor padrão de retorno.
     *
     * @return mixed valor requisitado ou valor default.
     */
    public function dottedGet($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Retorna o valor de um array utilizando a notação de pontos, e o retira do array original.
     *
     * @param array $array array de origem.
     * @param mixed $key   Chave do valor requerido.
     *
     * @return mixed valor requisitado.
     */
    public function dottedPull(&$array, $key)
    {
        $value = $this->dottedGet($array, $key);

        $this->dottedUnset($array, $key);

        return $value;
    }

    /**
     * Insere um valor em um array utilizando a notação de pontos.
     *
     * @param array  $array array de origem (por referência).
     * @param string $key   Chave do valor requerido.
     * @param mixed  $value Valor para ser inserido.
     *
     * @return void
     */
    public function dottedSet(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Remove o valor de um array utilizando a notação de pontos.
     *
     * @param array  $array array de origem.
     * @param string $key   Chave do valor a ser removido.
     *
     * @return void
     */
    public function dottedUnset(&$array, $key)
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                return;
            }

            $array = &$array[$key];
        }

        unset($array[array_shift($keys)]);
    }

    /**
     * Retorna um array 'achatado' contendo o elemento selecionado.
     *
     * @param array  $array array de origem.
     * @param string $key   Chave dos valores a serem retornados.
     *
     * @return void
     */
    public function dottedFetch($array, $key)
    {
        foreach (explode('.', $key) as $segment) {
            $results = [];

            foreach ($array as $value) {
                $value = (array) $value;

                $results[] = $value[$segment];
            }

            $array = array_values($results);
        }

        return array_values($results);
    }

    /**
     * Helper para retornar uma nova instância (útil para 'chaining".
     *
     * @return self
     */
    public static function newInstance()
    {
        return new static();
    }
}
