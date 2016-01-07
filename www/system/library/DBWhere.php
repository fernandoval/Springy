<?php

namespace FW;

class DBWhere extends DBExpression
{
    private $where = '';
    private $values = [];

    public function __construct()
    {
        $this->where = '';
    }

    public function add(DBExpression $filtro, $conf = self::COND_AND)
    {
        $this->where .= (!empty($this->where) ? ' '.$conf.' ' : '').'('.$filtro.')';

        $valores = $filtro->getValue(); // o método pode retornar NULL, sendo assim, po PHP o considera não setado gerando um E_NOTICE

        if (isset($valores)) {
            if (is_array($valores)) {
                foreach ($valores as $valor) {
                    $this->values[] = $valor;
                }
            } else {
                $this->values[] = $valores;
            }
        }
    }

    public function getValue()
    {
        return $this->values;
    }

    public function __toString()
    {
        return $this->where;
    }
}
