<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe de Utilidades para Manipulação de Arrays.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    1.1.3
 *  \ingroup    framework
 */

namespace Springy\Utils;

/**
 *  \brief Classe de manipulação de arrays.
 */
class ArrayUtils
{
    /**
     *  \brief Adiciona um valor ao array SOMENTE se já não houver valor na chave passada por parâmetro.
     *  \param[in] (array) $array - array no qual o valor será adicionado
     *  \param[in] (variant) $key - chave da posição na qual o valor será adicionado
     *  \param[in] (variant) $value - valor a ser adicionado
     *  \return void.
     */
    public function add($array, $key, $value)
    {
        if (!isset($array[$key])) {
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     *  \brief Cria outro array com os dados filtrados por uma callback passada por parâmetro.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (\Closure) $callback - Função de filtragem
     *  \return (array) array criado.
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
     *  \brief Cria um array com todos os valures de uma determinada chave de um array associativo multi-dimentcional.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (variant) $value - Chave do valor a ser retirado de todos os elemntos do array
     *  \param[in] (variant) $key - Chave do valor a ser retirado de todos os elemntos do array e colodo como chave do array criado
     *  \return (array) array criado.
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
     *  \brief Retorna um array com as chaves do array de origem na posição 0 e os valores na posição 1 (ideal pra usar com o list().
     *  \param[in] (array) $array - array de origem
     *  \return (array) array criado.
     */
    public function split($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     *  \brief Retorna um array somente com os valores das chaves passadas por parâmetro.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (array) $array - array de chaves
     *  \return (array) array criado.
     */
    public function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     *  \brief Retorna um array com todos os valores, exceto os que possuem as chaves passadas por parâmetro.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (array) $array - array de chaves
     *  \return (array) array criado.
     */
    public function except($array, $keys)
    {
        return array_diff_key($array, array_flip((array) $keys));
    }

    /**
     *  \brief Cria outro array com os dados ordenados por uma callback passado por parâmetro.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (\Closure) $callback - Função de ordenagem
     *  \return (array) array criado.
     */
    public function sort($array, \Closure $callback)
    {
        uasort($array, $callback);

        return $array;
    }

    /**
     *  \brief Retorna o PRIMEIRO valor que passa na função teste passado por parâmetro ou o valor padrão.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (\Closure) $callback - Função de teste
     *  \param[in] (variant) $default - Valor padrão de retorno
     *  \return (variant) valor que passou.
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
     *  \brief Retorna o ÚLTIMO valor que passa na função teste passado por parâmetro ou o valor padrão.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (\Closure) $callback - Função de teste
     *  \param[in] (variant) $default - Valor padrão de retorno
     *  \return (variant) valor que passou.
     */
    public function lastThatPasses($array, \Closure $callback, $default = null)
    {
        return $this->firstThatPasses(array_reverse($array), $callback, $default);
    }

    /**
     *  \brief Retorna o TODOS os valores que passaram na função teste passado por parâmetro.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (\Closure) $callback - Função de teste
     *  \return (array) valores que passaram.
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
     *  \brief Retorna um array de somente um nível "achatando" um array multi-dimencional.
     *  \param[in] (array) $array - array de origem
     *  \return (array) array criado.
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
     *  \brief Retorna um array de somente um nível "achatando" um array multi-dimencional, mas com a notação de pontos,
     *         Exemplo: $array['key1']['key2] --> $array['key1.key2'].
     *  \param[in] (array) $array - array de origem
     *  \return (array) array criado.
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
     *  \brief Retorna o valor de um array utilizando a notação de pontos.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (string) $key - Chave do valor requerido
     *  \param[in] (variant) $default - Valor padrão de retorno
     *  \return (variant) valor requisitado ou valor default.
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
     *  \brief Retorna o valor de um array utilizando a notação de pontos, e o retira do array original.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (string) $key - Chave do valor requerido
     *  \return (variant) valor requisitadot.
     */
    public function dottedPull(&$array, $key)
    {
        $value = $this->dottedGet($array, $key);

        $this->dottedUnset($array, $key);

        return $value;
    }

    /**
     *  \brief Insere um valor deme um array utilizando a notação de pontos.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (string) $key - Chave do valor requerido
     *  \param[in] (variant) $valuet - Valor para ser inserido
     *  \return (void).
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
     *  \brief Remove o valor de um array utilizando a notação de pontos.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (string) $key - Chave do valor a ser removido
     *  \return (void).
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
     *  \brief Retorna um array 'achatado' contendo o elemento selecionado.
     *  \param[in] (array) $array - array de origem
     *  \param[in] (string) $key - Chave dos valores a serem retornados
     *  \return (void).
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
     *  \brief Helper para retornar uma nova instância (útil para 'chaining"
     *  \return (ArrayUtils).
     */
    public static function newInstance()
    {
        return new static();
    }
}
