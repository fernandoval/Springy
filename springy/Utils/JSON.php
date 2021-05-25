<?php
/**
 * JSON treatment and output.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.1.1.10
 */

namespace Springy\Utils;

use Springy\Configuration;
use Springy\Core\Debug;
use Springy\Kernel;

/**
 * JSON treatment and output class.
 */
class JSON
{
    private $data = [];
    private $statusCode = 200;

    /**
     * Constructor.
     *
     * @param array $data
     * @param int   $status
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
     * Adds data to JSON array.
     *
     * @param string $data
     *
     * @return void
     */
    public function add($data)
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Gets the array of the JSON.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Pega todos os dados do JSON.
     *
     * This method will be deprecated in future version.
     *
     * @deprecated 4.4.0
     */
    public function getDados()
    {
        return $this->getData();
    }

    /**
     * Changes the header status code.
     *
     * @param int $status
     *
     * @return void
     */
    public function setHeaderStatus($status)
    {
        $this->statusCode = $status;
    }

    /**
     * Parses the array of data in a JSON object string.
     *
     * @return string
     */
    public function fetch(): string
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
     * Prints the JSON to the standard output.
     *
     * @param bool $andDie
     *
     * @return void
     */
    public function output($andDie = true)
    {
        if (Configuration::get('system', 'debug')) {
            $this->data['debug'] = Debug::get();
        }

        // Send the header
        header('Content-type: application/json; charset=' . Kernel::charset(), true, $this->statusCode);
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        if (Kernel::isCGIMode()) {
            $lineFeed = "\n";
            echo 'Status: ' . $this->statusCode . $lineFeed;
            echo 'Content-type: application/json; charset=' . Kernel::charset() . $lineFeed;
            echo 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' . $lineFeed;
            echo 'Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT' . $lineFeed;
            echo 'Cache-Control: no-store, no-cache, must-revalidate' . $lineFeed;
            echo 'Cache-Control: post-check=0, pre-check=0' . $lineFeed;
            echo 'Pragma: no-cache' . $lineFeed . $lineFeed;
        }

        echo $this->fetch();

        if ($andDie) {
            exit;
        }
    }

    /**
     * Back compatibility method. Is deprecated. Use the output method.
     *
     * This method is deprecated and will be removed in future version.
     *
     * @deprecated 4.4.0
     */
    public function printJ($andDie = true)
    {
        $this->output($andDie);
    }

    /**
     * Converts the JSON object to String.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->fetch();
    }
}
