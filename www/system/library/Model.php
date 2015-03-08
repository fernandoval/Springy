<?php
/**	\file
 *  FVAL PHP Framework for Web Applications
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2015 Fernando Val\n
 *
 *  \brief		Classe Model para acesso a banco de dados
 *  \note		Essa classe extende a classe DB.
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	1.7.11
 *  \author		Fernando Val  - fernando.val@gmail.com
 *  \ingroup	framework
 */

namespace FW;

use FW\Validation\Validator;

/**
 *  \brief Classe Model para acesso a banco de dados
 *
 *  Esta classe extende a classe DB.
 *
 *  Esta classe deve ser utilizada como herança para as classes de acesso a banco.
 *
 *  Utilize-a para diminuir a quantidade de métodos que sua classe precisará ter para consultas e manutenção em bancos de dados.
 */
class Model extends DB implements \Iterator
{
	/**
	 *  Atributos da classe
	 */
	/// Tabela utilizada pela classe
	protected $tableName = '';
	/// Relação de colunas da tabela para a consulta (pode ser uma string separada por vírgula ou um array com nos nomes das colunas)
	protected $tableColumns = '*';
	/// Colunas que determinam a chave primária
	protected $primaryKey = 'id';
	/// Nome da coluna que armazena a data de inclusão do registro (será utilizada pelo método save)
	protected $insertDateColumn = null;
	/// Nome da coluna usada para definir que o registro foi excluído
	protected $deletedColumn = null;
	/// Colunas passíveis de alteração
	protected $writableColumns = array();
	/// Colunas que precisam passar por algum método de alteração
	protected $hookedColumns = array();
	/// Colunas passíveis de ordenação para a busca
	protected $orderColumns = array();
	/// Propriedades do objeto
	protected $rows = array();
	/// Propriedades que sofreram alteração
	protected $changedColumns = array();
	/// Quantidade total de registros localizados no filtro
	protected $dbNumRows = 0;
	/// Flag de carga do banco de dados. Informa que os dados do objeto foram lidos do banco.
	protected $loaded = false;
    /// Container de mensagem de erros de validação.
    protected $validationErrors;
	/// Protege contra carga completa
	protected $abortOnEmptyFilter = true;
	/// Objetos relacionados
	protected $embedding = array();
	/// Colunas para agrupamento de consultas
	protected $groupBy = array();
	/// Cláusula HAVING
	protected $having = array();

    /**
	 *  \brief Método construtor da classe.
	 *
	 *  \param $filtro - Filto de busca, opcional. Deve ser um array de campos ou inteiro com ID do usuário.
	 */
	function __construct($filter=null, $database='default')
	{
		parent::__construct($database);

		if (is_array($filter)) {
			$this->load($filter);
		}
	}

	/**
	 *  \brief Verifica se a chave primária está definida
	 *
	 *  \return Retorna TRUE se todas as colunas da chave primária estão definiadas e FALSE em caso contrário.
	 */
	protected function isPrimaryKeyDefined()
	{
		if (empty($this->primaryKey)) {
			return false;
		}

		$primary = explode(',', $this->primaryKey);
		foreach($primary as $column) {
			if (!isset($this->rows[0][$column])) {
				return false;
			}
		}

		return true;
	}

	/**
	 *  \brief Monta o filtro para a busca
	 *
	 *  \note Este método deve ser extendido na classe herdeira
	 */
	protected function filter($filter, array &$where, array &$params)
	{
		foreach ($filter as $field => $value) {
			if (is_array($value)) {
				foreach($value as $method => $key) {
					switch (strtolower($method)) {
						case 'eq':
							$where[] = $field.' = ?';
							$params[] = $key;
							break;
						case 'gt':
							$where[] = $field.' > ?';
							$params[] = $key;
							break;
						case 'gte':
							$where[] = $field.' >= ?';
							$params[] = $key;
							break;
						case 'lt':
							$where[] = $field.' < ?';
							$params[] = $key;
							break;
						case 'lte':
							$where[] = $field.' <= ?';
							$params[] = $key;
							break;
						case 'ne':
							$where[] = $field.' != ?';
							$params[] = $key;
							break;
						case 'in':
							$where[] = $field.' in ('.trim(str_repeat('?, ', count($key)), ', ').')';
							foreach ($key as $val)
								$params[] = $val;
							break;
						case 'like':
							$where[] = $field.' LIKE ?';
							$params[] = $key;
							break;
						case 'match':
							$where[] = 'MATCH ('.$field.') AGAINST (?)';
							$params[] = $key;
							break;
						case 'match in boolean mode':
							$where[] = 'MATCH ('.$field.') AGAINST (? IN BOOLEAN MODE)';
							$params[] = $key;
							break;
						default:
							$where[] = $field.' = ?';
							$params[] = $key;
					}
				}
			} else {
				$where[] = $field.' = ?';
				$params[] = $value;
			}
		}
		return true;
	}

    /**
     * \brief Retorna as configurações de regras para validação dos dados do model
     *
     * \note Este método deve ser extendido na classe herdeira
     *
     * \return array
     */
    protected function validationRules()
    {
        return array();
    }

    /**
     * \brief Mensagens de erros customizadas para cada tipo de validação à ser
     *        realizado neste model.
     *
     * \note Este método deve ser extendido na classe herdeira
     *
     * \return array
     */
    protected function validationErrorMessages()
    {
        return array();
    }

    /**
     * \brief Retorna o container de mensagens de errors que guardará as mensagens de erros
     *       vindas do teste de validação
     *
     * \return FW\Utils\MessageContainer
     */
    public function validationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * \brief Realiza uma validação dos dados populados no objeto, passando-os por testes
     *        de acordo com  as configurações regras estipulados no método 'validationRules()'
     *
     * \return bool resultado da validação, true para passou e false para não passou
     */
    public function validate()
    {
        $data = $this->rows[0];

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
	 *  \brief Método de carga do objeto
	 *
	 *  Busca um registro específico e o carrega para as propriedades.
	 *
	 *  Caso mais de um registro seja localizado, descarta a busca e considera com não carregado.
	 *
	 *  \return Retorna TRUE se encontrar um registro que se adeque aos filtros de busca. Retorna FALSE em caso contrário.
	 */
	public function load(array $filter=null)
	{
		if ($this->query($filter) && $this->dbNumRows == 1) {
			$this->loaded = true;
		} else {
			$this->rows = array();
		}

		return ($this->dbNumRows == 1);
	}

	/**
	 *  \brief Informa se o registro foi carregado com dados do banco
	 */
	public function isLoaded() {
		return $this->loaded;
	}

	/**
	 *  \brief Salva o registro carregado no banco de dados
	 *
	 *  \return Retorna TRUE se o dado foi salvo ou FALSE caso nenhum dado tenha sido alterado
	 */
	public function save($onlyIfValidationPasses = true)
	{
        // Se o parametro de salvar o objeto somente se a validação passar,
        // então é feita a validação e se o teste falar, retorna falso sem salvar
        if ( $onlyIfValidationPasses && !$this->validate() ) {
            try {
                // Se houver erros e o objeto de flashdata estiver registrado
                // salvá-los nos dados de flash para estarem disponívels na variavel
                // de template global '$errors' somente durante o próximo request.
                app('session.flashdata')->setErrors( $this->validationErrors() );
            } catch (Exception $e) { }

            return false;
        }

		if (count($this->changedColumns) < 1) {
			return false;
		}

		$columns = array();
		$values = array();

		foreach ($this->changedColumns as $column) {
			$columns[] = $column;
			$values[] = $this->rows[0][$column];
		}

		if ($this->loaded) {
			if ($this->isPrimaryKeyDefined()) {
				$pk = array();
				$primary = explode(',', $this->primaryKey);
				foreach($primary as $column) {
					$pk[] = $column.' = ?';
					$values[] = $this->rows[0][$column];
				}
				$this->execute('UPDATE '.$this->tableName.' SET '.implode(' = ?,', $columns).' = ? WHERE '.implode(' AND ', $pk), $values);
			}
		}
		else {
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
					$cdtFunc = '\''.date('Y-m-d H:i:s').'\'';
			}

			$this->execute('INSERT INTO '.$this->tableName.' ('.implode(', ', $columns).($this->insertDateColumn ? ', '.$this->insertDateColumn : "").') VALUES ('.rtrim(str_repeat('?,', count($values)),',').($this->insertDateColumn ? ', '.$cdtFunc : "").')', $values);

			if ($this->affectedRows() > 0 && $this->lastInsertedId() && !empty($this->primaryKey) && !strpos($this->primaryKey, ',') && empty($this->rows[0][$this->primaryKey])) {
				$this->load(array($this->primaryKey => $this->lastInsertedId()));
			}
		}

		$this->changedColumns = array();

		return ($this->affectedRows() > 0);
	}

	/**
	 *  \brief Deleta o registro
	 *
	 *  Promove a exclusão física ou lógica de registros
	 *
	 *  \param (array)$filter - filtro dos registros a serem deletados.
	 *  	Se omitido (ou null), default, deleta o registro carregado na classe
	 *  \return Retorna o número de linhas afetadas ou FALSE em caso contrário.
	 */
	public function delete(array $filter=null)
	{
		// Se está carregado e não foi passado um filtro, exclui o registro corrente
		if ($this->loaded && is_null($filter)) {
			// Abandona se a chave primária não estiver definida
			if (!$this->isPrimaryKeyDefined()) {
				return false;
			}

			// Monta a chave primária
			$pk = array();
			$primary = explode(',', $this->primaryKey);
			foreach($primary as $column) {
				$pk[] = $column.' = ?';
				$values[] = $this->rows[0][$column];
			}

			// Faz a exclusão lógica ou física do registro
			if (!empty($this->deletedColumn)) {
				array_unshift($values, 1);
				$this->execute('UPDATE '.$this->tableName.' SET '.$this->deletedColumn.' = ? WHERE '.implode(' AND ', $pk), $values);
			} else {
				$this->execute('DELETE FROM '.$this->tableName.' WHERE '.implode(' AND ', $pk), $values);
			}
		}
		else {
			$where = array();
			$params = array();

			// Monta o conjunto de filtros personalizado da classe herdeira
			if (!$this->filter($filter, $where, $params)) {
				return false;
			}

			// Abandona caso não hajam filtros
			if (empty($where)) {
				return false;
			}

			// Se há uma coluna de exclusão lógica definida, adiciona-a ao conjunto de filtros
			if ($this->deletedColumn) {
				$where[] = $this->deletedColumn.' = ?';
				$params[] = 0;
			}

			// Faz a exclusão lógica ou física do(s) registro(s)
			if (!empty($this->deletedColumn)) {
				array_unshift($params, 1);
				$this->execute('UPDATE '.$this->tableName.' SET '.$this->deletedColumn.' = ? WHERE '.implode(' AND ', $where), $params);
			} else {
				$this->execute('DELETE FROM '.$this->tableName.' WHERE '.implode(' AND ', $where), $params);
			}
		}

		return $this->affectedRows();
	}

	/**
	 *  \brief Pega uma coluna ou um registro dos atributos de dados
	 *
	 *  \param (string)$column - Nome da coluna desejada ou null caso queira o array contendo a linha atual
	 *
	 *  \return Retorna o conteúdo da coluna passada, um array com as colunas do registro atual ou NULL
	 */
	public function get($column=NULL)
	{
		if (is_null($column)) {
			return current($this->rows);
		}
		else {
			$columns = current($this->rows);
			if (isset($columns[$column])) {
				return $columns[$column];
			}
		}

		return null;
	}

	/**
	 *  \brief Altera o valor de uma coluna
	 *
	 *  \return Retorna TRUE se alterou o valor da coluna ou FALSE caso a coluna não exista ou não haja registro carregado
	 */
	public function set($column, $value = null)
    {
        if ( is_array($column) ) {
            foreach ($column as $key => $val) {
                $this->set($key, $val);
            }
            return true;
        }

		if (in_array($column, $this->writableColumns)) {
			if (empty($this->rows)) {
				$this->rows[] = array();
			}

			$oldvalue = isset($this->rows[key($this->rows)][$column]) ? $this->rows[key($this->rows)][$column] : null;
			if (isset($this->hookedColumns[$column]) && method_exists($this, $this->hookedColumns[$column])) {
				$this->rows[key($this->rows)][$column] = call_user_func_array(array($this, $this->hookedColumns[$column]), array($value));
			} else {
				$this->rows[key($this->rows)][$column] = $value;
			}

			if (key($this->rows) == 0 && $oldvalue != $value) {
				if (!in_array($column, $this->changedColumns)) {
					$this->changedColumns[] = $column;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 *  \brief Define a relação de colunas para consultas
	 *
	 *  Este método permite alterar a relação padrão de colunas a serem listadas em consultas com o método query
	 *
	 *  \params (array)$columns - array contendo a relação de colunas para o comando SELECT
	 */
	public function setColumns(array $columns)
	{
		$cols = array();
		foreach($columns as $column) {
			if (!strpos($column, '.') && !strpos($column, '(')) {
				$column = $this->tableName.'.'.$column;
			}
			$cols[] = $column;
		}

		$this->tableColumns = implode(',', $cols);
	}

	/**
	 *  \brief Define colunas para agrupamento do resultado
	 *
	 *  Este método permite definir a relação de colunas para a cláusula GROUP BY da consulta com o método query
	 *
	 *  \params (array)$columns - array contendo a relação de colunas para a cláusula GROUP BY
	 *  \note ESTE MÉTODO AINDA É EXPERIMENTAL
	 */
	public function groupBy(array $columns)
	{
		$cols = array();
		foreach($columns as $column) {
			if (!strpos($column, '.') && !strpos($column, '(')) {
				$column = $this->tableName.'.'.$column;
			}
			$cols[] = $column;
		}

		$this->groupBy = $cols;
	}

	/**
	 *  \brief Define atributos para a cláusula HAVING
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
	 *  \brief Método de consulta ao banco de dados
	 *
	 *  \param (array)$filter - array contendo o filtro de registros no formato 'coluna' => valor
	 *
	 *  \return Retorna TRUE caso tenha efetuado a busca ou FALSE caso não tenha recebido filtros válidos.
	 */
	public function query(array $filter=null, array $orderby=array(), $offset=NULL, $limit=NULL, $embbed=false)
	{
		// Se nenhuma chave de busca foi passada, utiliza dados do objeto
		if (is_null($filter) && !empty($this->rows)) {
			$filter = $this->rows[0];
		}

		// Abandona caso não nenhum filtro tenha sido definido (evita retornar toda a tabela como resultado)
		if (empty($filter) && $this->abortOnEmptyFilter) {
			return false;
		}

		// Monta o conjunto de colunas da busca
		if (is_array($this->tableColumns)) {
			$columns = $this->tableName.'.'.implode(', '.$this->tableName.'.', $this->tableColumns);
		} else {
			$columns = (!strpos($this->tableColumns, '.') && !strpos($this->tableColumns, '(')) ? $this->tableName.'.'.$this->tableColumns : $this->tableColumns;
		}

		$select = 'SELECT '.($this->driverName() == 'mysql' ? 'SQL_CALC_FOUND_ROWS ' : "").$columns;
		$from = ' FROM '.$this->tableName;
		unset($columns);
		$where = array();
		$order = array();
		$params = array();

		// Monta o conjunto de filtros personalizado da classe herdeira
		if (!$this->filter($filter, $where, $params)) {
			return false;
		}

		// Abandona caso não hajam filtros
		if (empty($where) && $this->abortOnEmptyFilter) {
			return false;
		}

		// Se há uma coluna de exclusão lógica definida, adiciona-a ao conjunto de filtros
		if ($this->deletedColumn) {
			$where[] = $this->tableName.'.'.$this->deletedColumn.' = ?';
			if (isset($filter[$this->deletedColumn])) {
				$params[] = (int)$filter[$this->deletedColumn];
			} elseif (isset($filter[$this->tableName.'.'.$this->deletedColumn])) {
				$params[] = (int)$filter[$this->tableName.'.'.$this->deletedColumn];
			} else {
				$params[] = 0;
			}
		}

		// Monta os JOINs caso um array seja fornecido
		if (is_array($embbed)) {
			// Cada item do array de JOINs deve ser um array com os índices 'fields', 'type', 'table', 'on'
			// 'type' determina o timpo de JOIN. Exemplos: 'INNER', 'LEFT OUTER'
			// 'fields' deve ser a lista de campos da junção a ser acrescentada ao select e deve conter o nome da tabela para evitar ambiguidade
			// 'table' é o nome da tabela
			// 'on' é a cláusula ON para junção das tabelas
			foreach ($embbed as $join) {
				if (!empty($join['fields'])) {
					$select .= ', '.$join['fields'];
				}
				if (!isset($join['type'])) {
					$join['type'] = 'LEFT INNER';
				}
				$from .= ' '.$join['type'].' JOIN '.$join['table'].' ON '.$join['on'];
			}
		}

		$sql = $select.$from;

		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		// Monta o agrupamento
		if (!empty($this->groupBy)) {
			$sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
		}

		// Monta a cláusula HAVING de condicionamento
		if (!empty($this->having)) {
			$where = array();
			$this->filter($this->having, $where, $params);
			$sql .= ' HAVING ' . implode(' AND ', $where);
		}

		// Monta a ordenação do resultado de busca
		if (!empty($orderby)) {
			foreach($orderby as $column => $direction) {
				if (!strpos($column, '.')) {
					$column = $this->tableName.'.'.$column;
				}
				$order[] = "$column $direction";
			}

			if (!empty($order)) {
				$sql .= ' ORDER BY ' . implode(', ', $order);
			}
		}

		// Monta o limitador de registros
		if ($limit) {
			$sql .= ' LIMIT ?, ?';
			$params[] = $offset;
			$params[] = $limit;
		}

		// Limpa as propriedades da classe
		$this->changedColumns = array();
		$this->loaded = false;

		// Efetua a busca
		$this->execute($sql, $params);
		$this->rows = $this->fetchAll();
		unset($sql);

		// Faz a contagem de registros do filtro apenas se foi definido um limitador de resultador
		if ($limit) {
			if ($this->driverName() == 'mysql') {
				$this->execute('SELECT FOUND_ROWS() AS found_rows');
			} else {
				array_pop($params);
				array_pop($params);

				$sql = 'SELECT COUNT(0) AS found_rows FROM '.$this->tableName;
				if (!empty($where)) {
					$sql .= ' WHERE ' . implode(' AND ', $where);
				}

				$this->execute($sql, $params);
			}
			$columns = $this->fetchNext();
			$this->dbNumRows = (int)$columns['found_rows'];
		} else {
			$this->dbNumRows = count($this->rows);
		}
		unset($where, $params);

		// if ($embbed === true && count($this->embedding)) {
			// foreach ($this->embedding as $obj => $attr) {
				// $keys = array();
				// foreach ($this->rows as $row) {
					// if (!in_array($row[$attr[1]], $keys)) {
						// $keys[] = $row[$attr[1]];
					// }
				// }
				// $embObj = new $attr[0];
				// $embObj->query(array('id' => $keys));

				// if (isset($doc[$attr[1]])) {
					// $row[$obj] = $embObj->findOne(array('_id' => $doc[$attr[1]]));
					// unset($embObj);
				// } else {
					// $row[$obj] = null;
				// }
			// }
		// }

		return true;
	}

	/**
	 *  \brief Todos os registros
	 *
	 *  \return Retorna um array com todas as linhas do resultset
	 */
	public function all()
	{
		return $this->rows;
	}

	/**
	 *  \brief Move o ponteiro para o primeiro registro e retorna o registro
	 *
	 *  \return Retorna o primeiro registro ou FALSE caso não haja registros.
	 */
	public function reset()
	{
		return reset($this->rows);
	}

	/**
	 *  \brief Move o ponteiro para o registro anteior e retorna o registro
	 *
	 *  \return Retorna o registro anterior ou FALSE caso não haja mais registros.
	 */
	public function prev()
	{
		return prev($this->rows);
	}

	/**
	 *  \brief Retorna o registro corrente e move o ponteiro para o próximo registro.
	 *
	 *  \return Retorna o próximo registro da fila ou FALSE caso não haja mais registros.
	 */
	public function next()
	{
		if ($r = each($this->rows)) {
			return $r['value'];
		}
		return false;
	}

	/**
	 *  \brief Move o ponteiro para o último registro e retorna o registro
	 *
	 *  \return Retorna o último registro ou FALSE caso não haja registros.
	 */
	public function end()
	{
		return end($this->rows);
	}

	/**
	 *  \brief Retorna todos os dados de uma determinada coluna
	 *
	 *  \return Retorna um array de valores de uma determinada coluna do resultset.
	 */
	public function getAllColumn($column)
	{
		return array_column($this->rows, $column);
	}

	/**
	 *  \brief Quantidade de linhas encontradas para uma determinada condição
	 *
	 *  \return Retorna a quantidade de registros encontrados para uma determinada condição
	 */
	public function count(array $filter=null, $embbed=false)
	{
		$select = 'SELECT COUNT(0) AS rowscount';
		$from = ' FROM '.$this->tableName;
		$where = array();
		$params = array();

		// Monta o conjunto de filtros personalizado da classe herdeira
		if (!$this->filter($filter, $where, $params)) {
			return false;
		}

		// Se há uma coluna de exclusão lógica definida, adiciona-a ao conjunto de filtros
		if ($this->deletedColumn) {
			$where[] = $this->deletedColumn.' = ?';
			if (isset($filter[$this->deletedColumn])) {
				$params[] = (int)$filter[$this->deletedColumn];
			} else {
				$params[] = 0;
			}
		}

		// Monta os JOINs caso um array seja fornecido
		if (is_array($embbed)) {
			foreach ($embbed as $join) {
				if (!isset($join['type'])) {
					$join['type'] = 'INNER';
				}
				$from .= ' '.$join['type'].' JOIN '.$join['table'].' ON '.$join['on'];
			}
		}

		$sql = $select.$from;

		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		// Executa o comando de contagem
		$this->execute($sql, $params);
		$row = $this->fetchNext();
		return (int)$row['rowscount'];
	}

	/**
	 *  \brief Quantidade de linhas do resultset
	 *
	 *  \return Retorna a quantidade de registros contidos no resultset da última consulta
	 */
	public function rows()
	{
		return count($this->rows);
	}

	/**
	 *  \brief Dá o número de linhas encotnradas no banco de dados
	 *
	 *  \return Retorna a quantidade de registros encntrados no banco de dados para a última busca efetuada.
	 */
	public function foundRows()
	{
		return $this->dbNumRows;
	}

    /**
     * \brief Alis de get(), para retornar columns como se fossem propriedades
     * \param variant $name
     * \return variant
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * \brief Alias de set(), para setar columns como se fossem propriedades
     * \param string $name
     * \param variant $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function current()
    {
        return current($this->rows);
    }

    public function key()
    {
        return key($this->rows);
    }

    public function rewind()
    {
        reset($this->rows);
    }

    public function valid()
    {
        return $this->current() !== false;
    }

}