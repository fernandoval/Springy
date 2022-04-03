<?php

/**
 * Parent class for models.
 *
 * Extends this class used to create Model classes to access relational database tables.
 *
 * @copyright 2014 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   2.8.1
 */

namespace Springy;

use Exception;
use Springy\DB\Conditions;
use Springy\DB\Where;
use Springy\Validation\Validator;

/**
 * Parent class for models.
 *
 * This class extends the DB class.
 */
class Model extends DB implements \Iterator
{
    /** @var string the name of the table */
    protected $tableName = '';
    /// Relação de colunas da tabela para a consulta (pode ser uma string separada por vírgula ou um array com nos nomes das colunas)
    protected $tableColumns = '*';
    /// Relação de colunas calculadas pela classe
    protected $calculatedColumns = null;
    /// Colunas que determinam a chave primária
    protected $primaryKey = 'id';
    /// Nome da coluna que armazena a data de inclusão do registro (será utilizada pelo método save)
    protected $insertDateColumn = null;
    /// Nome da coluna usada para definir que o registro foi excluído
    protected $deletedColumn = null;
    /** @var array list of columns that can be changed */
    protected $writableColumns = [];
    /** @var array list of special methods to get the columns values */
    protected $getterColumns = [];
    /** @var array list of special methods for set the columns values */
    protected $hookedColumns = [];
    /// Join other tables structured array
    protected $join;
    /// Colunas passíveis de ordenação para a busca
    protected $orderColumns = [];
    /// Propriedades do objeto
    protected $rows = [];
    /// Quantidade total de registros localizados no filtro
    protected $dbNumRows = 0;
    /// Flag de carga do banco de dados. Informa que os dados do objeto foram lidos do banco.
    protected $loaded = false;
    /** @var \Springy\Utils\MessageContainer validation errors container */
    protected $validationErrors;
    /// Protege contra carga completa
    protected $abortOnEmptyFilter = true;
    /// Objetos relacionados
    protected $embeddedObj = [];
    /// Colunas para agrupamento de consultas
    protected $groupBy = [];
    /// Cláusula HAVING
    protected $having = [];
    /** @var bool throws error when get or set undefined or unwritable column */
    protected $throwErrUndefCol = false;
    /// The WHERE conditions
    public $where = null;

    private const CHANGED_COLUMNS = '**CHANGED**';
    private const NEW_ROW = '**NEW**';

    /**
     * Constructor.
     *
     * @param Where|array $filter optional search filter.
     */
    public function __construct($filter = null, $database = 'default')
    {
        parent::__construct($database);

        $this->setJoin([]);
        $this->where = new Where();

        if (is_array($filter) || $filter instanceof Where) {
            $this->load($filter);
        }
    }

    /**
     * Gets the Where object of the filter.
     *
     * @param array|Where $filter
     *
     * @return Where
     */
    private function _fFilter($filter)
    {
        // Filter is a Where object?
        if ($filter instanceof Where) {
            return clone $filter;
        }

        // Filter is a legacy array?
        if (is_array($filter)) {
            $where = new Where();
            $where->filter($filter);

            return $where;
        }

        return clone $this->where;
    }

    /**
     * Gets the Where object of the filter.
     *
     * @param array|Where $filter
     *
     * @return Where
     */
    private function _filter($filter)
    {
        $where = $this->_fFilter($filter);

        if ($this->deletedColumn && !$where->get($this->deletedColumn)
            && !$where->get($this->tableName . '.' . $this->deletedColumn)) {
            $where->condition($this->tableName . '.' . $this->deletedColumn, 0);
        }

        return $where;
    }

    /**
     * Returns a string with column names separated by comma.
     *
     * @return string
     */
    private function _getColumns()
    {
        $columns = [$this->_parseColumns($this->tableName, $this->tableColumns)];
        foreach ($this->join as $table => $join) {
            if (!empty($join['columns'])) {
                $columns[] = $this->_parseColumns($table, $join['columns']);
            }
        }

        return implode(', ', $columns);
    }

    /**
     * Returns a string with table and joins to the query.
     *
     * @return string
     */
    private function _getFrom()
    {
        $from = ' FROM ' . $this->tableName;
        foreach ($this->join as $table => $join) {
            $from .= ' ' . $join['type'] . ' JOIN ' . $table . ' ON ' . $join['on'];
        }

        return $from;
    }

    /**
     * When condition for embedded object.
     *
     * @param array $row
     * @param array $attr
     *
     * @return bool
     */
    private function _conditionalWhen($row, $attr)
    {
        if (!isset($attr['when']) || !is_array($attr['when'])) {
            return true;
        }

        $results = [];
        foreach ($attr['when'] as $condition) {
            if (count($condition) < 3) {
                $results[] = false;

                continue;
            }

            $left = $condition[0];
            if (is_string($left) && isset($row[$left])) {
                $left = $row[$left];
            }

            $right = $condition[2];
            if (is_string($right) && isset($row[$right])) {
                $right = $row[$right];
            }

            switch ($condition[1]) {
                case '=':
                    $results[] = ($left == $right);
                    break;
                case '>':
                    $results[] = ($left > $right);
                    break;
                case '<':
                    $results[] = ($left < $right);
                    break;
                case '!=':
                    $results[] = ($left != $right);
                    break;
                case 'in':
                case 'IN':
                    if (is_array($right)) {
                        $results[] = in_array($left, $right);
                    }
                    break;
            }
        }

        $results = array_unique($results);

        return count($results) ? current($results) : false;
    }

    /**
     * Embbeds rows of other tables in each row.
     *
     * @see setEmbeddedObj().
     *
     * Se o parâmetro $embbed for um inteiro maior que zero e o atributo embeddedObj estiver definido,
     * o relacionamento definido pelo atributo será explorado até o enézimo nível definido por $embbed
     *
     * ATENÇÃO: é recomendado cuidado com o valor de $embbed para evitar loops muito grandes ou estouro
     * de memória, pois os objetos podem relacionar-se cruzadamente causando relacionamento reverso
     * infinito.
     *
     * @param int|array $embbed
     *
     * @return void
     */
    private function _queryEmbbed($embbed)
    {
        if (!is_int($embbed) || $embbed == 0 || !count($this->embeddedObj) || !count($this->rows)) {
            return;
        }

        $where = new Where();

        foreach ($this->embeddedObj as $obj => $attr) {
            $modelName = isset($attr['model']) ? $attr['model'] : $obj;
            $attrName = isset($attr['attr_name']) ? $attr['attr_name'] : $obj;
            $foundBy = isset($attr['found_by']) ? $attr['found_by'] : $attr['pk'];
            $relCol = isset($attr['column']) ? $attr['column'] : $attr['fk'];
            $resType = isset($attr['type']) ? $attr['type'] : (isset($attr['attr_type']) ? $attr['attr_type'] : 'list');

            // The array of values to search
            $keys = [];
            foreach ($this->rows as $idx => $row) {
                $this->rows[$idx][$attrName] = [];

                if (!$this->_conditionalWhen($row, $attr)) {
                    continue;
                }

                if (!in_array($row[$relCol], $keys)) {
                    $keys[] = $row[$relCol];
                }
            }
            if (!count($keys)) {
                continue;
            }

            // Filter
            $where->clear();
            $where->condition($foundBy, $keys, Where::OP_IN);
            if (isset($attr['filter']) && is_array($attr['filter'])) {
                $where->filter($attr['filter']);
            }

            // Order
            $order = isset($attr['order']) ? $attr['order'] : [];
            // Offset
            $offset = isset($attr['offset']) ? $attr['offset'] : null;
            // Limit
            $limit = isset($attr['limit']) ? $attr['limit'] : null;

            $embObj = new $modelName();
            if (isset($attr['columns']) && is_array($attr['columns'])) {
                $embObj->setColumns($attr['columns']);
            }
            if (isset($attr['group_by']) && is_array($attr['group_by'])) {
                $embObj->groupBy($attr['group_by']);
            }
            if (isset($attr['embedded_obj'])) {
                $embObj->setEmbeddedObj($attr['embedded_obj']);
            }

            $embObj->query($where, $order, $offset, $limit, $embbed - 1);
            while ($erow = $embObj->next()) {
                foreach ($this->rows as $idx => $row) {
                    if ($erow[$foundBy] == $row[$relCol]) {
                        $embed = $erow;

                        if (
                            isset($attr['returns'])
                            && is_array($attr['returns'])
                            && count($attr['returns'])
                        ) {
                            $embed = [];
                            foreach ($attr['returns'] as $column) {
                                $embed[$column] = $erow[$column] ?? null;
                            }
                        }

                        if ($resType == 'list') {
                            $this->rows[$idx][$attrName][] = $embed;
                        } else {
                            $this->rows[$idx][$attrName] = $embed;
                        }
                    }
                }
            }
            unset($embObj);
            reset($this->rows);
        }
    }

    /**
     * Returns a string with column names separated by comma.
     *
     * @param array|string $columns   an array or a comma separeted string with the columns names.
     * @param string       $tableName the name of the table.
     *
     * @return string
     */
    private function _parseColumns($tableName, $columns)
    {
        if (!is_array($columns)) {
            $columns = explode(',', $columns);
        }

        $line = '';
        foreach ($columns as $column) {
            $line .= ((!strpos($column, '.') && !strpos($column, '(')) ? $tableName . '.' . $column : $column) . ', ';
        }

        return trim($line, ', ');

        // Old code preserved to future reference. Must be removed before end version.
        // if (is_array($this->tableColumns)) {
        //     return $this->tableName.'.'.implode(', '.$this->tableName.'.', $this->tableColumns);
        // }

        // return (!strpos($this->tableColumns, '.') && !strpos($this->tableColumns, '(')) ? $this->tableName.'.'.$this->tableColumns : $this->tableColumns;
    }

    /**
     * Checks whether the primary key is set.
     *
     * @return bool true if all columns of the primary key are set or false otherwise.
     */
    protected function isPrimaryKeyDefined()
    {
        if (empty($this->primaryKey)) {
            return false;
        }

        $primary = $this->getPKColumns();
        foreach ($primary as $column) {
            if (!isset($this->rows[key($this->rows)][$column])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the data validation rules configuration.
     *
     * Extends this method to defines the validation rules.
     *
     * @return array
     */
    protected function validationRules()
    {
        return [];
    }

    /**
     * Returns the customized error messages to the validation rules.
     *
     * Extends the method to defines customizes error messages.
     *
     * @return array
     */
    protected function validationErrorMessages()
    {
        return [];
    }

    /**
     * A trigger which will be called by delete method on Model object before do the exclusion.
     *
     * This method exists to be optionally extended in the child class
     * if any treatment needs to be done prior to the deletion of a registry.
     *
     * @return bool True if all is ok or false if has an error.
     */
    protected function triggerBeforeDelete()
    {
        return true;
    }

    /**
     * A trigger which will be called by save method on Model object before insert data.
     *
     * This method exists to be optionally extended in the child class
     * if any treatment needs to be done prior to the insertion of a registry.
     *
     * @return bool True if all is ok or false if has an error.
     */
    protected function triggerBeforeInsert()
    {
        return true;
    }

    /**
     * A trigger which will be called by save method on Model object before update data.
     *
     * This method exists to be optionally extended in the child class
     * if any treatment needs to be done prior to the update of a registry.
     *
     * @return bool True if all is ok or false if has an error.
     */
    protected function triggerBeforeUpdate()
    {
        return true;
    }

    /**
     * A trigger which will be called by delete method on Model object after the exclusion done.
     *
     * This method exists to be optionally extended in the child class
     * if any treatment needs to be done after the exclusion of the registry.
     *
     * @return void
     */
    protected function triggerAfterDelete()
    {
        return true;
    }

    /**
     * A trigger which will be called by save method on Model object after insert data.
     *
     * This method exists to be optionally extended in the child class
     * if any treatment needs to be done after the insertion of a registry.
     *
     * @return void
     */
    protected function triggerAfterInsert()
    {
        return true;
    }

    /**
     * A trigger which will be called by save method on Model object after update data.
     *
     * This method exists to be optionally extended in the child class
     * if any treatment needs to be done after the update of a registry.
     *
     * @return void
     */
    protected function triggerAfterUpdate()
    {
        return true;
    }

    /**
     * Returns an array with the column(s) of the primary key.
     *
     * @return array the array with column(s) of the primary key or false.
     */
    public function getPKColumns()
    {
        if (empty($this->primaryKey)) {
            return false;
        }

        if (is_array($this->primaryKey)) {
            return $this->primaryKey;
        }

        return explode(',', $this->primaryKey);
    }

    /**
     * Returns the table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Returns the container of error messages.
     *
     * @return \Springy\Utils\MessageContainer
     */
    public function validationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * Performs the validation of the data in the object.
     *
     * This method uses rules set in the validationRules() method.
     *
     * @return bool true or false according to the validation result.
     */
    public function validate()
    {
        if (!$this->valid()) {
            return false;
        }

        $data = $this->current();

        $validation = Validator::make(
            $data,
            $this->validationRules(),
            $this->validationErrorMessages()
        );

        $result = $validation->validate();

        $this->validationErrors = $validation->errors();

        return $result;
    }

    /**
     * Loads the record.
     *
     * This method query for received filter and set loaded property to true if one
     * and only one row was found.
     *
     * @param mixed $filter a Where object or an array with the conditions.
     * @param mixed $embed  the embed count or join array. Default false to no embed or join.
     *
     * @return bool True if one and only one row was found or false in other case.
     */
    public function load($filter, $embed = false)
    {
        $this->rows = [];
        $this->loaded = ($this->query($filter, [], 0, 0, $embed) && $this->dbNumRows == 1);

        return $this->loaded;
    }

    /**
     * Returns an array of the changed columns in the current record.
     *
     * @return array
     */
    public function changedColumns()
    {
        if ($this->valid()) {
            return $this->rows[key($this->rows)][self::CHANGED_COLUMNS] ?? [];
        }

        return [];
    }

    /**
     * Clears the list of changed columns.
     *
     * @return void
     */
    public function clearChangedColumns()
    {
        if ($this->valid()) {
            $this->rows[key($this->rows)][self::CHANGED_COLUMNS] = [];
        }
    }

    /**
     * Returns whether the desired record has been loaded.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * Fills calculated columns of a row.
     *
     * @param int $row number of the row to be processed. If null given, process current row.
     */
    public function calculeteColumnsRow($row = null)
    {
        if (!is_array($this->calculatedColumns) || !count($this->calculatedColumns)) {
            return;
        }

        if ($row === null) {
            $row = key($this->rows);
        }

        foreach ($this->calculatedColumns as $column => $method) {
            if (method_exists($this, $method)) {
                $this->rows[$row][$column] = $this->$method($this->rows[$row]);
                continue;
            }

            $this->rows[$row][$column] = null;
        }
    }

    /**
     * Sets the values of the calculated columns.
     *
     * @return void
     */
    public function calculateColumns()
    {
        if (!is_array($this->calculatedColumns) || !count($this->calculatedColumns)) {
            return;
        }

        foreach ($this->rows as $idx => $row) {
            $this->calculeteColumnsRow($idx);
        }

        reset($this->rows);
    }

    /**
     * Mount the array with changed values to be saved.
     *
     * @return array
     */
    private function _values()
    {
        $values = [];
        foreach ($this->changedColumns() as $column) {
            $values[] = $this->get($column);
        }

        return $values;
    }

    /**
     * Performs an INSERT command.
     *
     * @return bool true if any record was inserted.
     */
    private function _insert()
    {
        // Call before insert trigger
        if (!$this->triggerBeforeInsert()) {
            return false;
        }

        // Database function to populate created at column
        switch ($this->driverName()) {
            case 'oci':
            case 'oracle':
            case 'mysql':
            case 'pgsql':
                $cdtFunc = 'NOW()';
                break;
            case 'mssql':
            case 'sqlsrv':
                $cdtFunc = 'GETDATE()';
                break;
            case 'db2':
            case 'ibm':
            case 'ibm-db2':
            case 'firebird':
                $cdtFunc = 'CURRENT_TIMESTAMP';
                break;
            case 'informix':
                $cdtFunc = 'CURRENT';
                break;
            case 'sqlite':
                $cdtFunc = 'datetime(\'now\')';
                break;
            default:
                $cdtFunc = '\'' . date('Y-m-d H:i:s') . '\'';
        }

        $values = $this->_values();

        $command = 'INSERT INTO ' . $this->tableName
            . ' (' . implode(', ', $this->changedColumns());

        if (
            $this->insertDateColumn
            && !in_array($this->insertDateColumn, $this->changedColumns())
        ) {
            $command .= ', ' . $this->insertDateColumn;
        }

        $command .= ') VALUES (' . rtrim(str_repeat('?,', count($values)), ',');

        if (
            $this->insertDateColumn
            && !in_array($this->insertDateColumn, $this->changedColumns())
        ) {
            $command .= ', ' . $cdtFunc;
        }

        $command .= ')';

        $this->execute($command, $values);

        // Load the inserted row
        if ($this->affectedRows() == 1) {
            if ($this->lastInsertedId() && !empty($this->primaryKey) && !strpos($this->primaryKey, ',') && !$this->get($this->primaryKey)) {
                $this->load([$this->primaryKey => $this->lastInsertedId()]);
            } elseif ($this->isPrimaryKeyDefined()) {
                $where = new Where();
                foreach ($this->getPKColumns() as $column) {
                    $where->condition($column, $this->get($column));
                }
                $this->load($where);
            }
        }

        // Call after insert trigger
        $this->triggerAfterInsert();

        $this->clearChangedColumns();
        $this->calculeteColumnsRow();

        return $this->affectedRows() > 0;
    }

    /**
     * Performs an UPDATE command.
     *
     * @return bool true if any record was updated.
     */
    private function _update()
    {
        // There is no primary key to build condition, do nothing.
        if (!$this->isPrimaryKeyDefined()) {
            return false;
        }

        // Call before update trigger
        if (!$this->triggerBeforeUpdate()) {
            return false;
        }

        $where = new Where();
        foreach ($this->getPKColumns() as $column) {
            $where->condition($column, $this->get($column));
        }

        $this->execute(
            'UPDATE ' . $this->tableName . ' SET ' .
            implode(' = ?,', $this->changedColumns()) .
            ' = ?' . $where,
            array_merge($this->_values(), $where->params())
        );

        // Call after update trigger
        $this->triggerAfterUpdate();

        $this->clearChangedColumns();
        $this->calculeteColumnsRow();

        return $this->affectedRows() > 0;
    }

    /**
     * Saves the changes in current row into the database.
     *
     * @return bool true if any record was saved or false otherwise.
     */
    public function save($onlyIfValidationPasses = true)
    {
        // Se o parametro de salvar o objeto somente se a validação passar,
        // então é feita a validação e se o teste falar, retorna falso sem salvar
        if ($onlyIfValidationPasses && !$this->validate()) {
            try {
                // Se houver erros e o objeto de flashdata estiver registrado
                // salvá-los nos dados de flash para estarem disponívels na variavel
                // de template global '$errors' somente durante o próximo request.
                app('session.flashdata')->setErrors($this->validationErrors());
            } catch (Exception $e) {
            }

            return false;
        }

        // If there is no change, do nothing.
        if (!count($this->changedColumns())) {
            return false;
        }

        if (!isset($this->rows[key($this->rows)][self::NEW_ROW])) {
            return $this->_update();
        }

        return $this->_insert();
    }

    /**
     *  \brief Delete one or more rows.
     *
     *  This method deletes the curret row or many rows if the $filter is given.
     *
     *  \param $filter is an array or Where object with a match criteria.
     *      If ommited (null) deletes the current row selected.
     *  \return Returns the number of affected rows or false.
     */
    public function delete($filter = null)
    {
        if (!is_null($filter) || $this->where->count()) {
            /*
             *  Delete rows with a filter.
             *
             *  In this method triggers will not be called.
             */

            // Build the condition
            if (is_array($filter)) {
                $where = new Where();
                $where->filter($filter);
            } elseif ($filter instanceof Where) {
                $where = clone $filter;
            } else {
                $where = clone $this->where;
            }

            if (!empty($this->deletedColumn)) {
                // If table has a deleted column flag, update the rows
                $where->condition($this->deletedColumn, 0);
                $this->execute('UPDATE ' . $this->tableName . ' SET ' . $this->deletedColumn . ' = 1' . $where, $where->params());
            } else {
                // Otherwise delete the row
                $this->execute('DELETE FROM ' . $this->tableName . $where, $where->params());
            }

            // Clear any conditions
            $this->where->clear();
        } elseif ($this->valid()) {
            /*
             *  Delete de current row.
             */

            // Do nothing if there is no primary key defined
            if (!$this->isPrimaryKeyDefined()) {
                return false;
            }

            // Build the primary key to define the row to be deleted
            $where = new Where();
            foreach (explode(',', $this->primaryKey) as $column) {
                $where->condition($column, $this->get($column));
            }

            // Call before delete trigger
            if (!$this->triggerBeforeDelete()) {
                return false;
            }

            if (!empty($this->deletedColumn)) {
                // If table has a deleted column flag, update the row
                $where->condition($this->deletedColumn, 0);
                $this->execute('UPDATE ' . $this->tableName . ' SET ' . $this->deletedColumn . ' = 1' . $where, $where->params());
            } else {
                // Otherwise delete the row
                $this->execute('DELETE FROM ' . $this->tableName . $where, $where->params());
            }
            // Call after delete trigger
            $this->triggerAfterDelete();
        } else {
            return false;
        }

        // Clear conditions avoid bug
        $this->where->clear();

        return $this->affectedRows();
    }

    /**
     *  \brief Faz alteração em lote.
     *
     *  EXPERIMENTAL!
     *
     *  Permite fazer atualização de registros em lote (UPDATE)
     */
    public function update(array $values, $conditions = null)
    {
        if (is_null($conditions)) {
            return false;
        }

        $data = [];
        $params = [];

        foreach ($values as $column => $value) {
            if (in_array($column, $this->writableColumns)) {
                if (is_callable($value)) {
                    if (isset($this->hookedColumns[$column]) && method_exists($this, $this->hookedColumns[$column])) {
                        $data[] = $column . ' = ' . call_user_func_array([$this, $this->hookedColumns[$column]], [$value()]);
                    } else {
                        $data[] = $column . ' = ' . $value();
                    }
                } else {
                    $data[] = $column . ' = ?';
                    if (isset($this->hookedColumns[$column]) && method_exists($this, $this->hookedColumns[$column])) {
                        $params[] = call_user_func_array([$this, $this->hookedColumns[$column]], [$value]);
                    } else {
                        $params[] = $value;
                    }
                }
            }
        }

        // The WHERE clause
        if ($conditions instanceof Where) {
            $where = clone $conditions;
        } elseif (is_array($conditions)) {
            $where = new Where();
            $where->filter($conditions);
        } else {
            throw new \Exception('Invalid condition type.', 500);
        }

        if (!empty($this->deletedColumn) && !$where->get($this->deletedColumn)) {
            $where->condition($this->deletedColumn, 0);
        }
        $this->execute('UPDATE ' . $this->tableName . ' SET ' . implode(', ', $data) . $where, array_merge($params, $where->params()));

        // Clear conditions avoid bug
        $this->where->clear();

        return $this->affectedRows();
    }

    /**
     * Gets a column or a record from the resultset.
     *
     * @param string|null $column the name of the desired column.
     *                            if null then returns an array with all columns of the current record.
     *
     * @return mixed the valur of the column or an array with all columns data.
     */
    public function get($column = null)
    {
        $columns = current($this->rows);

        // All columns?
        if (is_null($column)) {
            if ($columns === false) {
                return false;
            }

            $row = [];

            foreach (array_keys($columns) as $key) {
                $row[$key] = $this->get($key);
            }

            return $row;
        }

        // Specific column
        if (!isset($columns[$column]) && $this->throwErrUndefCol) {
            throw new Exception(
                'Column "' . $column . '" is not exists in the context.',
                E_USER_WARNING
            );
        }

        $value = $columns[$column] ?? null;

        return (
            isset($this->getterColumns[$column])
            && method_exists($this, $this->getterColumns[$column])
        ) ? call_user_func(
            [$this, $this->getterColumns[$column]],
            $value
        ) : $value;
    }

    /**
     * Sets the value of a column.
     *
     * @param string $column the name of the column.
     * @param mixed  $value  the value of the column.
     *
     * @return void
     */
    public function set($column, $value = null)
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->set($key, $val);
            }

            return;
        }

        $key = key($this->rows);
        $newrow = isset($this->rows[$key][self::NEW_ROW]);

        if (!in_array($column, $this->writableColumns)) {
            if ($this->throwErrUndefCol) {
                throw new Exception(
                    'Column "' . $column . '" is not writable.',
                    E_USER_WARNING
                );
            }

            return;
        } elseif (empty($this->rows)) {
            $this->rows[] = [
                self::NEW_ROW => true,
                self::CHANGED_COLUMNS => [],
            ];
            $key = key($this->rows);
            $newrow = true;
        }

        $oldvalue = $this->rows[$key][$column] ?? null;

        $this->rows[$key][$column] = (
            isset($this->hookedColumns[$column])
            && method_exists($this, $this->hookedColumns[$column])
        ) ? call_user_func(
            [$this, $this->hookedColumns[$column]],
            $value
        ) : $value;

        if (
            $newrow
            || ($oldvalue != $value)
            || (is_null($oldvalue) && !is_null($value))
            || (!is_null($oldvalue) && is_null($value))
        ) {
            $this->rows[$key][self::CHANGED_COLUMNS] = array_unique(
                array_merge(
                    $this->rows[$key][self::CHANGED_COLUMNS] ?? [],
                    [$column]
                )
            );
        }
    }

    /**
     * Sets the columns list for queries.
     *
     * @param array $columns an array with the columns names.
     *
     * @return void
     */
    public function setColumns(array $columns)
    {
        $cols = [];
        foreach ($columns as $column) {
            if (!strpos($column, '.') && !strpos($column, '(')) {
                $column = $this->tableName . '.' . $column;
            }
            $cols[] = $column;
        }

        $this->tableColumns = $cols;
    }

    /**
     *  \brief Define o array de objetos embutidos.
     *
     *  O array de objetos embutidos é uma estrutura que permite a consulta a execução de consultas em outros objetos e embutir
     *  seu resultado dentro de um atributo do registro.
     *
     *  O índice de cada item do array de objetos embutidos será inserido no registro como uma coluna que pode ser um array de
     *  registros ou a estrutura de dados da Model embutida.
     *
     *  O valor de cada item do array deve ser um array com a seguinte extrutura:
     *
     *  'model' => (string) nome do atributo a ser criado no registro
     *  'type' => (constant)'list'|'data' determina como o atributo deve ser.
     *      - 'list' (default) define que o atributo é uma lista (array) de registros;
     *      - 'data' define que o atributo é um único registro do objeto embutido (array de colunas).
     *  'found_by' => (string) nome da coluna do objeto embutido que será usada como chave de busca.
     *  'column' => (string) nome da coluna que será usada para relacionamento com o objeto embutido.
     *  'columns' => (array) um array de colunas, opcional, a serem aplicados ao objeto embutido, no mesmo formato usados no método setColumns.
     *  'filter' => (array) um array de filtros, opcional, a serem aplicados ao objeto embutido, no mesmo formato usados no método query.
     *  'group_by' => (array) um array de agrupamento, opcional, a serem aplicados ao objeto embutido, no mesmo formato usados no método groupBy.
     *  'order' => (array) um array de ordenação, opcional, a ser aplicado ao objeto embutido, no mesmo formato usados no método query.
     *  'offset' => (int) o offset de registros, opcional, a ser aplicado ao objeto embutido, no mesmo formato usados no método query.
     *  'limit' => (int) o limite de registros, opcional, a ser aplicado ao objeto embutido, no mesmo formato usados no método query.
     *  'embbeded_obj' => (array) um array estrutura, opcional, para embutir outro objeto no objeto embutido.
     *
     *  Exemplo de array aceito:
     *
     *  array('parent' => array('model' => 'Parent_Table', 'type' => 'data', 'found_by' => 'id', 'column' => 'parent_id'))
     */
    public function setEmbeddedObj(array $embeddedObj)
    {
        $this->embeddedObj = $embeddedObj;
    }

    /**
     * Sets joins to other tables.
     *
     * @param array $join an structured array of tables do join.
     *
     * Cada item do array de JOINs deve ser um array, cujo índice representa
     * o nome da tabela e contendo as seguintes chaves em seu interior: 'columns', 'join' e 'on'.
     *
     * Cada chave do sub-array representa o seguinte:
     *     'join' determina o tipo de JOIN. Exemplos: 'INNER', 'LEFT OUTER'.
     *     'columns' define lista de campos, separada por vírgulas, a serem acrescidos ao SELECT.
     *          Recomenda-se preceder cada coluna com o nome da tabela para evitar ambiguidade.
     *     'on' é a cláusula ON para junção das tabelas.
     *
     * Example of parameter $embbed as array to be used as JOIN:
     *
     * [
     *     'table_name' => [
     *         'join'    => 'INNER',
     *         'on'      => 'table_name.id = fk_id',
     *         'columns' => 'table_name.column1 AS table_name_column1, table_name.column2',
     *     ],
     * ]
     *
     * or:
     *
     * [
     *     [
     *         'join'    => 'INNER',
     *         'table'   => 'table_name',
     *         'on'      => 'table_name.id = fk_id',
     *         'columns' => 'table_name.column1 AS table_name_column1, table_name.column2',
     *     ],
     * ]
     *
     * @return void
     */
    public function setJoin($join)
    {
        if (!is_array($join)) {
            return;
        }

        $this->join = [];

        foreach ($join as $table => $meta) {
            if (isset($meta['table'])) {
                $table = $meta['table'];
            }

            $this->join[$table] = [
                'columns' => isset($meta['columns']) ? $meta['columns'] : (isset($meta['fields']) ? $meta['fields'] : ''),
                'type'    => isset($meta['join']) ? $meta['join'] : (isset($meta['type']) ? $meta['type'] : 'INNER'),
                'on'      => $meta['on'],
            ];
        }
    }

    /**
     *  \brief Define colunas para agrupamento do resultado.
     *
     *  Este método permite definir a relação de colunas para a cláusula GROUP BY da consulta com o método query
     *
     *  \params (array)$columns - array contendo a relação de colunas para a cláusula GROUP BY
     *  \note ESTE MÉTODO AINDA É EXPERIMENTAL
     */
    public function groupBy(array $columns)
    {
        $cols = [];
        foreach ($columns as $column) {
            if (!strpos($column, '.') && !strpos($column, '(')) {
                $column = $this->tableName . '.' . $column;
            }
            $cols[] = $column;
        }

        $this->groupBy = $cols;
    }

    /**
     *  \brief Define atributos para a cláusula HAVING.
     *
     *  Este método permite definir a cláusula HAVING para agrupamento
     *
     *  \params (array)$columns - array contendo a relação de colunas para a cláusula GROUP BY
     *  \note ESTE MÉTODO AINDA É EXPERIMENTAL
     */
    public function having(array $conditions)
    {
        $this->having = $conditions;
    }

    /**
     * Performs a query.
     *
     * @param Where $filter  the object Where with the conditions to the query.
     * @param array $orderby a multidimentional array with column => 'ASC|DESC' pairs.
     * @param int   $offset  specifies the offset of the first row to return.
     * @param int   $limit   specifies the maximum number of rows to return.
     * @param mixed $embbed  can be a structured array to apply a join (see setJoin function),
     *                       or an integer to determines the penetration leval in
     *                       embedded object defined by setEmbeddedObj() function.
     *                       If omited will be ignored.
     *
     * @return bool true if the query was executed or false if has no filter and the
     *              property abortOnEmptyFilter if true.
     *
     * Observação: Mesmo que o método retorne TRUE, não significa que algum dado tenha sido encontrado.
     * Isso representa apenas que a consulta foi efetuado com sucesso. Para saber o resultado da
     * consulta, utilize os métodos de recuperação de dados.
     */
    public function query($filter = null, array $orderby = [], $offset = 0, $limit = 0, $embbed = null)
    {
        $where = $this->_filter($filter);
        if ($this->abortOnEmptyFilter && !$where->count()) {
            return false;
        }

        // Saves the join array and set new join.
        $join = $this->join;
        $this->setJoin($embbed);

        $select = 'SELECT ' .
            (
                $this->driverName() == 'mysql' && $limit > 0
                    ? 'SQL_CALC_FOUND_ROWS '
                    : ''
            ) . $this->_getColumns() . $this->_getFrom();

        $sql = $select . $where .
            (
                count($this->groupBy)
                    ? ' GROUP BY ' . $this->_parseColumns($this->tableName, $this->groupBy)
                    : ''
            );
        $params = $where->params();

        // Monta a cláusula HAVING de condicionamento
        if (!empty($this->having)) {
            $conditions = new Conditions();
            $conditions->filter($this->having);
            $sql .= ' HAVING ' . $conditions;
            $params = array_merge($params, $conditions->params());
            unset($conditions);
        }

        // Order by
        $order = [];
        foreach ($orderby as $column => $direction) {
            $order[] = $column . ' ' . strtoupper($direction);
        }
        if (count($order)) {
            $sql .= ' ORDER BY ' . implode(', ', $order);
        }

        // Monta o limitador de registros
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $offset . ', ' . $limit;
        }

        // Limpa as propriedades da classe
        $this->loaded = false;

        // Efetua a busca
        $this->execute($sql, $params);
        $this->rows = $this->fetchAll();
        unset($sql);

        // Faz a contagem de registros do filtro apenas se foi definido um limitador de resultados
        if ($limit > 0) {
            if ($this->driverName() == 'mysql') {
                $this->execute('SELECT FOUND_ROWS() AS found_rows');
            } else {
                $sql = 'SELECT COUNT(0) AS found_rows FROM ' . $this->tableName . $where;

                $this->execute($sql, $where->params());
            }
            $columns = $this->fetchNext();
            $this->dbNumRows = (int) $columns['found_rows'];
        } else {
            $this->dbNumRows = count($this->rows);
        }
        unset($where, $params);

        $this->_queryEmbbed($embbed);

        // Set the values of the calculated columns
        $this->calculateColumns();

        // Clears conditions avoid bug
        $this->where->clear();
        // Restores the saved join
        $this->join = $join;

        return true;
    }

    /**
     * Returns all found records.
     *
     * @return array
     */
    public function all()
    {
        $rows = [];
        reset($this->rows);

        while ($this->valid()) {
            $rows[] = $this->get();
            $this->next();
        }

        return $rows;
    }

    /**
     * Moves the pointer to the first record and returns the record.
     *
     * @return array|bool the first record or false if there are no records.
     */
    public function reset()
    {
        $reset = reset($this->rows);

        return ($reset === false) ? false : $this->get();
    }

    /**
     * Moves the pointer to the previous record and returns the record.
     *
     * @return array|bool the previous record or false if there are no more records.
     */
    public function prev()
    {
        $prev = prev($this->rows);

        return ($prev === false) ? false : $this->get();
    }

    /**
     * Returns the current record and moves the pointer to the next record.
     *
     * @return array|bool the next row record or false if there are no more records.
     */
    public function next()
    {
        $row = $this->get();

        if ($row !== false) {
            next($this->rows);
        }

        return $row;
    }

    /**
     * Moves the pointer to the last record and returns the record.
     *
     * @return array|bool the last record or false if there are no records.
     */
    public function end()
    {
        $end = end($this->rows);

        return ($end === false) ? false : $this->get();
    }

    /**
     * Returns all data in a given column.
     *
     * @return array an array of values from a given resultset column.
     */
    public function getAllColumn($column)
    {
        return array_column($this->rows, $column);
    }

    /**
     * Performs a row count for a given condition.
     *
     * @return int
     */
    public function count($filter = null, ?array $distinct = null)
    {
        $where = $this->_filter($filter);
        $columns = '0';

        if (is_array($distinct) && count($distinct)) {
            $columns = 'DISTINCT ' . implode(', ', $distinct);
        }

        $this->execute(
            'SELECT COUNT(' . $columns . ') AS rowscount' . $this->_getFrom() . $where,
            $where->params()
        );
        $row = $this->fetchNext();

        return (int) $row['rowscount'];
    }

    /**
     * Returns the number of lines of the resultset.
     *
     * @return int
     */
    public function rows()
    {
        return count($this->rows);
    }

    /**
     * Returns the number of records found by the last query performed.
     *
     * @return int
     */
    public function foundRows()
    {
        return $this->dbNumRows;
    }

    /**
     * Magic method to get value from columns as if they were properties.
     *
     * This method is an alias to the get() method.
     *
     * @param string|null $name the name of the column.
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Magic method to set value in columns as if they were properties..
     *
     * This method is an alias to the set() method.
     *
     * @param string $name  the column name.
     * @param mixed  $value the value.
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Gets the current record.
     *
     * This method is an alias to the get() method.
     *
     * @return array
     */
    public function current()
    {
        return $this->get();
    }

    /**
     * Gets the names of the columns.
     *
     * @return array
     */
    public function key()
    {
        return key($this->rows);
    }

    /**
     * An alias for reset.
     *
     * @see reset
     */
    public function rewind()
    {
        return $this->reset();
    }

    /**
     * Checks whether the current record exists.
     *
     * @return bool
     */
    public function valid()
    {
        return current($this->rows) !== false;
    }
}
