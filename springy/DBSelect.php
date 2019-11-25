<?php

namespace Springy;

class DBSelect extends DBExpression
{
    private $colunas = [];
    private $from = '';
    private $inner = '';
    private $conds = '';
    private $values = [];
    private $order = [];
    private $having = '';
    private $group = [];
    private $limit = '';
    private $offset = '';

    public function __construct($tabela = '', $as = '')
    {
        $this->setFrom($tabela, $as);
    }

    public function addColumn($nome, $func = '', $as = '')
    {
        if (is_array($func)) {
            $params = $func;
            $func = $func[0];
            array_shift($params);
        }

        switch ($func) {
            case self::COUNT:
                $col = 'COUNT(' . $nome . ')';
            break;
            case self::SUM:
                $col = 'SUM(' . $nome . ')';
            break;
            case self::DISTINCT:
                $col = 'DISTINCT(' . $nome . ')';
            break;
            case self::CONCAT:
                if (is_array($nome)) {
                    $col = implode(' || ', $nome);
                }
            break;
            case self::ROUND:
                $col = 'ROUND(CAST(' . $nome . ' AS NUMERIC), ' . $params[0] . ')';
            break;
            case self::LOWER:
                $col = sprintf(self::LOWER, $nome);
            break;
            case self::C_CASE:
                $case = '';

                foreach ($nome as $key => $arrValue) {
                    $case .= "\n" . '        '; // os espações correspondem ao \t (no caso são 2x)

                    if ($arrValue[1] == DBExpression::C_ELSE) {
                        $case .= 'ELSE ' . $arrValue[3] . ' ';
                    } elseif (isset($arrValue[3])) {
                        if ($arrValue[2]) {
                            $this->addValues([$arrValue[2]]);
                            $case .= ($case ? 'WHEN ' : '') . $arrValue[0] . ' ' . $arrValue[1] . ' ? THEN ' . $arrValue[3];
                        } else {
                            $case .= ($case ? 'WHEN ' : '') . $arrValue[0] . ' ' . $arrValue[1] . ' THEN ' . $arrValue[3];
                        }
                    } else {
                        $case .= 'WHEN ' . $arrValue[0] . ' THEN ' . $arrValue[1];
                    }
                }

                $col = '(CASE ' . $case . "\n" . '    END)';
            break;
            default:
                $col = (is_array($nome) ? implode(', ', $nome) : $nome);
            break;
        }

        /*if ($func == self::DISTINCT) {
            $col = 'DISTINCT(' . $col . ')';
        } else if ($func == self::CONCAT && is_array($nome)) {
            $col = implode(' || ', $nome);
        }*/

        $this->colunas[] = $col . ($as ? ' AS ' . $as : '');
    }

    public function setFrom($from, $as = '')
    {
        $this->from = (($from instanceof self) ? '(' . $from . ') ' . $as : $from . ' ' . $as);
        if ($from instanceof self && $from->getAllValues()) {
            $this->addValues($from->getAllValues());
        }
    }

    public function innerJoin($tabela, DBWhere $where, $as = '')
    {
        $this->inner .= '    INNER JOIN ' . ($tabela instanceof self ? '(' . "\n" . '        ' . $tabela . '    ) ' . $as : $tabela) . ' ON ' . $where . "\n";

        if ($tabela instanceof self && $tabela->getAllValues()) {
            $this->addValues($tabela->getAllValues());
        }

        if (!count($where->getValue())) {
            return;
        }

        $this->addValues($where->getValue());
    }

    public function leftJoin($tabela, DBWhere $where, $as = '')
    {
        $this->inner .= '    LEFT OUTER JOIN ' . ($tabela instanceof self ? '(' . "\n" . '        ' . $tabela . '    ) ' . $as : $tabela) . ' ON ' . $where . "\n";

        if ($tabela instanceof self && $tabela->getAllValues()) {
            $this->addValues($tabela->getAllValues());
        }

        if (!count($where->getValue())) {
            return;
        }

        $this->addValues($where->getValue());
    }

    public function addWhere(DBWhere $where)
    {
        $this->conds = $where;

        if (!count($where->getValue())) {
            return;
        }
        $this->addValues($where->getValue());
    }

    private function addValues($arrValues)
    {
        foreach ($arrValues as $valor) {
            $this->values[] = $valor;
        }
        /*$this->values = $this->values + $arrValues;*/
    }

    public function addHaving($where)
    {
        $this->having = $where;
    }

    public function addGroup($group)
    {
        $this->group[] = $group;
    }

    public function addOrder($order)
    {
        $this->order[] = $order;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /* ---- */

    public function getAllValues()
    {
        return $this->values;
    }

    public function __toString()
    {
        $sql = 'SELECT' . "\n";
        $sql .= '    ' . implode(',' . "\n" . '    ', $this->colunas) . "\n";
        $sql .= 'FROM ' . $this->from . "\n";

        $sql .= ($this->inner ? $this->inner : '');

        $sql .= (strlen($this->conds) ? 'WHERE ' . $this->conds . "\n" : '');

        $sql .= ($this->group ? 'GROUP BY ' . implode(', ', $this->group) . "\n" : '');

        $sql .= ($this->having ? 'HAVING ' . $this->having . "\n" : '');

        $sql .= ($this->order ? 'ORDER BY ' . implode(', ', $this->order) . "\n" : '');

        $sql .= ($this->limit ? 'LIMIT ' . $this->limit . "\n" : '');

        $sql .= ($this->offset ? 'OFFSET ' . $this->offset : '');

        return $sql;
    }
}
