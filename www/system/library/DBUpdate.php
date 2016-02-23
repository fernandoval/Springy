<?php

namespace Springy;

class DBUpdate
{
    private $tabela = null;
    private $columns = [];
    private $conds = null;
    private $from = null;
    private $values = [];

    public function __construct($tabela)
    {
        $this->tabela = $tabela;
    }

    public function add($valores)
    {
        foreach ($valores as $key => $value) {
            $this->columns[] = $key;
            $this->values[] = $value;
        }
    }

    public function addWhere(DBWhere $where)
    {
        $this->conds = $where;

        if (!count($where->getValue())) {
            return;
        }

        $this->addValues($where->getValue());
    }

    public function setFrom(DBSelect $from, $apelido = 'x')
    {
        $this->from = '('.$from.') '.$apelido;
        $this->addValues($from->getAllValues());
    }

    private function addValues($arrValues)
    {
        foreach ($arrValues as $value) {
            $this->values[] = $value;
        }
    }

    public function getAllValues()
    {
        return $this->values;
    }

    public function __toString()
    {
        return 'UPDATE '.$this->tabela.' SET'."\n"
             .'    '.implode(' = ?, '."\n    ", $this->columns).' = ?'."\n"
             .($this->from  ? 'FROM '.$this->from."\n" : '')
             .($this->conds ? 'WHERE '.$this->conds : '-- sem conds');
    }
}
