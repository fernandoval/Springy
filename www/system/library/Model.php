<?php
/**	\file
 *  FVAL PHP Framework for Web Applications
 *
 *  \copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \copyright Copyright (c) 2007-2013 Fernando Val\n
 *
 *  \brief		Classe Model para acesso a banco de dados
 *  \note		Essa classe extende a classe DB.
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	1.0.0
 *  \author		Fernando Val  - fernando.val@gmail.com
 *  \ingroup	framework
 *  
 *  Esta classe deve ser utilizada como herança para as classes de acesso a banco.
 *  
 *  Utilize-a para diminuir a quantidade de métodos que sua classe precisará ter para consultas e manutenção em bancos de dados.
 */

class Model extends DB {
	/**
	 *  Atributos da classe
	 */
	/// Tabela utilizada pela classe
	protected $tableName = '';
	/// Relação de colunas da tabela para a consulta (pode ser uma string separada por vírgula ou um array com nos nomes das colunas)
	protected $tableColumns = '*';
	/// Colunas que determinam a chave primária
	protected $primaryKey = array();
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

	/**
	 *  \brief Método construtor da classe.
	 *
	 *  \param $filtro - Filto de busca, opcional. Deve ser um array de campos ou inteiro com ID do usuário.
	 */
	function __construct($database=null, $filter=null) {
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
	protected function isPrimaryKeyDefined() {
		if (empty($this->primaryKey)) {
			return false;
		}

		foreach($this->primaryKey as $column) {
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
	protected function filter($filter, array &$where, array &$params) {
		return false;
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
	public function load(array $filter=null) {
		if ($this->query($filter) && $this->dbNumRows == 1) {
			$this->loaded = true;
		} else {
			$this->rows = array();
		}

		return ($this->dbNumRows == 1);
	}

	/**
	 *  \brief Salva o registro carregado no banco de dados
	 *
	 *  \return Retorna TRUE se o dado foi salvo ou FALSE caso nenhum dado tenha sido alterado
	 */
	public function save() {
		if (count($this->changedColumns) < 1) {
			return false;
		}

		$columns = array();
		$values = array();

		foreach ($this->changedColumns as $column) {
			$columns[] = '`'.$column.'`';
			$values[] = $this->rows[0][$column];
		}

		// $db = new DB;
		if ($this->loaded) {
			if ($this->isPrimaryKeyDefined()) {
				$pk = array();
				foreach($this->primaryKey as $column) {
					$pk[] = '`'.$column.'` = ?';
					$values[] = $this->rows[0][$column];
				}
				$this->execute('UPDATE `'.$this->tableName.'` SET '.implode(' = ?,', $columns).' = ? WHERE '.implode(' AND ', $pk), $values);
			}
		}
		else {
			$this->execute('INSERT INTO `'.$this->tableName.'`('.implode(', ', $columns).($this->insertDateColumn ? ', `'.$this->insertDateColumn.'`' : "").') VALUES ('.rtrim(str_repeat('?,', count($values)),',').($this->insertDateColumn ? ', NOW()' : "").')', $values);
			
			if (!empty($this->primaryKey) && count($this->primaryKey) == 1 && empty($this->rows[0][$this->primaryKey[0]])) {
				$this->rows[0][$this->primaryKey[0]] = $this->get_inserted_id();
			}
		}
		
		$this->changedColumns = array();
		
		return ($this->affected_rows() > 0);
	}
	
	/**
	 *  \brief Deleta o registro
	 *  
	 *  Promove a exclusão física ou lógica do registro
	 *  
	 *  \return Retorna TRUE se o registro foi deletado ou FALSE em caso contrário.
	 */
	public function delete() {
		if (!$this->loaded) {
			return false;
		}
		
		if (!$this->isPrimaryKeyDefined()) {
			return false;
		}
		
		$pk = array();
		foreach($this->primaryKey as $column) {
			$pk[] = '`'.$column.'` = ?';
			$values[] = $this->rows[0][$column];
		}
		
		if (!empty($this->deletedColumn)) {
			array_unshift($values, 1);
			$this->execute('UPDATE `'.$this->tableName.'` SET `'.$this->deletedColumn.'` = ? WHERE '.implode(' AND ', $pk), $values);
		} else {
			$this->execute('DELETE FROM `'.$this->tableName.'` WHERE '.implode(' AND ', $pk), $values);
		}
		
		return ($this->affected_rows() > 0);
	}

	/**
	 *  \brief Pega uma coluna ou um registro dos atributos de dados
	 *
	 *  \param (string)$column - Nome da coluna desejada ou null caso queira o array contendo a linha atual
	 *
	 *  \return Retorna o conteúdo da coluna passada, um array com as colunas do registro atual ou FALSE
	 */
	public function get(string $column=NULL) {
		if (is_null($column)) {
			return current($this->rows);
		}
		else {
			$columns = current($this->rows);
			if (isset($columns[$column])) {
				return $columns[$column];
			}
		}

		return false;
	}

	/**
	 *  \brief Altera o valor de uma coluna
	 *
	 *  \return Retorna TRUE se alterou o valor da coluna ou FALSE caso a coluna não exista ou não haja registro carregado
	 */
	public function set($column, $value) {
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
	 *  \brief Método de consulta ao banco de dados
	 *
	 *  \return Retorna TRUE caso tenha efetuado a busca ou FALSE caso não tenha recebido filtros válidos.
	 */
	public function query(array $filter=null, array $order=array(), $offset=NULL, $limit=NULL) {
		// Se nenhuma chave de busca foi passada, utiliza dados do objeto
		if (is_null($filter) && !empty($this->rows)) {
			$filter = $this->rows[0];
		}

		if (empty($filter)) {
			return false;
		}
		
		if (is_array($this->tableColumns)) {
			$columns = implode(', ', $this->tableColumns);
		} else {
			$columns = $this->tableColumns;
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS '.$columns.' FROM `'.$this->tableName.'` WHERE ';
		unset($columns);
		$where = array();
		$orderby = array();
		$params = array();

		if (!$this->filter($filter, $where, $params)) {
			return false;
		}

		if (empty($where)) {
			return false;
		}

		if ($this->deletedColumn) {
			$where[] = '`'.$this->deletedColumn.'` = ?';
			if (isset($filter[$this->deletedColumn])) {
				$params[] = (int)$filter[$this->deletedColumn];
			} else {
				$params[] = 0;
			}
		}

		$sql .= implode(' AND ', $where);
		unset($where);

		if (!empty($orderby)) {
			foreach($orderby as $column => $direction) {
				if (in_array($column, array($this->orderColumns)) && in_array($direction, array('ASC', 'DESC'))) {
					$orderby[] = "$column $direction";
				}
			}

			if (!empty($orderby)) {
				$sql .= ' ORDER BY ' . implode(', ', $orderby);
			}
		}

		if ($limit) {
			$sql .= ' LIMIT ?, ?';
			$params[] = $offset;
			$params[] = $limit;
		}

		$this->changedColumns = array();
		$this->loaded = false;
		
		// $db = new DB;
		$this->execute($sql, $params);
		unset($sql, $params);
		$this->rows = $this->get_all();

		$this->execute('SELECT FOUND_ROWS() AS total');
		$columns = $this->fetch_next();
		$this->dbNumRows = (int)$columns['total'];

		return true;
	}

	/**
	 *  \brief Move o ponteiro para o primeiro registro e retorna o registro
	 *
	 *  \return Retorna o primeiro registro ou FALSE caso não haja registros.
	 */
	public function reset() {
		return reset($this->rows);
	}

	/**
	 *  \brief Move o ponteiro para o registro anteior e retorna o registro
	 *
	 *  \return Retorna o registro anterior ou FALSE caso não haja mais registros.
	 */
	public function prev() {
		return prev($this->rows);
	}

	/**
	 *  \brief Move o ponteiro para o próximo registro e retorna o registro
	 *
	 *  \return Retorna o próximo registro ou FALSE caso não haja mais registros.
	 */
	public function next() {
		return next($this->rows);
	}

	/**
	 *  \brief Move o ponteiro para o último registro e retorna o registro
	 *
	 *  \return Retorna o último registro ou FALSE caso não haja registros.
	 */
	public function end() {
		return end($this->rows);
	}

	/**
	 *  \brief Dá o número de registros carregados em memória
	 *
	 *  \return Retorna a quantidade de registros contidos do offset de dados do objeto
	 */
	public function count() {
		return count($this->rows);
	}

	/**
	 *  \brief Dá o número de linhas encotnradas no banco de dados
	 *
	 *  \return Retorna a quantidade de registros encntrados no banco de dados para a última busca efetuada.
	 */
	public function found_rows() {
		return $this->dbNumRows;
	}
}