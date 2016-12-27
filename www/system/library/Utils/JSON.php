<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe de construção e tratamento de objetos JSON.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \version    1.1.0.9
 *  \ingroup    framework
 */

namespace Springy\Utils;

use Springy\Configuration;
use Springy\Core\Debug;
use Springy\Kernel;

/**
 *  \brief Classe de construção e tratamento de objetos JSON.
 */
class JSON
{
    private $data = [];
    private $statusCode = 200;

    /**
     *  \brief Constructor method.
     */
    public function __construct($data = null, $status = 200)
    {
        Configuration::set('system', 'ajax', true);

        if ($data) {
            $this->add($data);
        }

        $this->statusCode = $status;
    }

    /**
     *  \brief Add data to JSON array.
     */
    public function add($data)
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     *  \brief Get the array of the JSON.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     *  \brief Pega todos os dados do JSON.
     *  \note This method will be deprecated in future version.
     *  \deprecated.
     */
    public function getDados()
    {
        return $this->getData();
    }

    /**
     *  \brief Change the header status code.
     */
    public function setHeaderStatus($status)
    {
        $this->statusCode = $status;
    }

    /**
     *  \brief Parse the array of data in a JSON object.
     */
    public function fetch()
    {
        // Add static default variables to the json data if is not defined dinamicly
        foreach (JSON_Static::getDefaultVars() as $name => $value) {
            if (!isset($this->data[$name])) {
                $this->data[$name] = $value;
            }
        }

        return json_encode($this->data);
    }

    /**
     *  \brief Print the JSON to the standard output.
     */
    public function output($andDie = true)
    {
        if (Configuration::get('system', 'debug')) {
            $this->data['debug'] = Debug::get();
        }

        // Send the header
        header('Content-type: application/json; charset='.Kernel::charset(), true, $this->statusCode);
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        echo $this->fetch();

        if ($andDie) {
            exit;
        }
    }

    /**
     *  \brief Back compatibility method. Is deprecated. Use the output method.
     *  \note This method is deprecated and will be removed in future version.
     *  \deprecated.
     */
    public function printJ($andDie = true)
    {
        $this->output($andDie);
    }

    /**
     *  \brief Converte o objeto JSON para String.
     */
    public function __toString()
    {
        return $this->fetch();
    }
}
