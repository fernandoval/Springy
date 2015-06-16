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
 *  \version	1.13.17
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
	/// Relação de colunas calculadas pela classe
	protected $calculatedColumns = null;
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
	protected $embeddedObj = array();
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

		$primary = $this->getPKColumns();
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
	 *  \brief Gatilho que será executado antes de um DELETE
	 *  
	 *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
	 *  caso algum tratamento precise ser feito antes da exclusão de um registro
	 */
	protected function triggerBeforeDelete()
	{
		return true;
	}
	
	/**
	 *  \brief Gatilho que será executado antes de um INSERT
	 *  
	 *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
	 *  caso algum tratamento precise ser feito antes da enclusão de um registro
	 */
	protected function triggerBeforeInsert()
	{
		return true;
	}
	
	/**
	 *  \brief Gatilho que será executado antes de um UPDATE
	 *  
	 *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
	 *  caso algum tratamento precise ser feito antes da alteração de um registro
	 */
	protected function triggerBeforeUpdate()
	{
		return true;
	}
	
	/**
	 *  \brief Gatilho que será executado depois de um DELETE
	 *  
	 *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
	 *  caso algum tratamento precise ser feito depois da exclusão de um registro
	 */
	protected function triggerAfterDelete()
	{
		return true;
	}
	
	/**
	 *  \brief Gatilho que será executado depois de um INSERT
	 *  
	 *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
	 *  caso algum tratamento precise ser feito depois da inclusão de um registro
	 */
	protected function triggerAfterInsert()
	{
		return true;
	}
	
	/**
	 *  \brief Gatilho que será executado depois de um UPDATE
	 *  
	 *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
	 *  caso algum tratamento precise ser feito depois da alteração de um registro
	 */
	protected function triggerAfterUpdate()
	{
		return true;
	}
	
	/**
	 *  \brief Retorna um array com a(s) coluna(s) da chave primária
	 */
	public function getPKColumns()
	{
		if (empty($this->primaryKey)) {
			return false;
		}
		elseif (is_array($this->primaryKey)) {
			$pk = $this->primaryKey;
		}
		else {
			$pk = explode(',', $this->primaryKey);
		}
		
		return $pk;
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
	 *  \brief Limpa a relação de colunas alteradas
	 */
	public function clearChangedColumns()
	{
		$this->changedColumns = array();
	}
	
	/**
	 *  \brief Informa se o registro foi carregado com dados do banco
	 */
	public function isLoaded()
	{
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
				$primary = $this->getPKColumns();
				foreach($primary as $column) {
					$pk[] = $column.' = ?';
					$values[] = $this->rows[0][$column];
				}
				if ( !$this->triggerBeforeUpdate() )
					return false;
				$this->execute('UPDATE '.$this->tableName.' SET '.implode(' = ?,', $columns).' = ? WHERE '.implode(' AND ', $pk), $values);
				$this->triggerAfterUpdate();
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

			if ( !$this->triggerBeforeInsert() )
				return false;
			
			$this->execute('INSERT INTO '.$this->tableName.' ('.implode(', ', $columns).($this->insertDateColumn ? ', '.$this->insertDateColumn : "").') VALUES ('.rtrim(str_repeat('?,', count($values)),',').($this->insertDateColumn ? ', '.$cdtFunc : "").')', $values);

			if ($this->affectedRows() == 1) {
				if ($this->lastInsertedId() && !empty($this->primaryKey) && !strpos($this->primaryKey, ',') && empty($this->rows[0][$this->primaryKey])) {
					$this->load(array($this->primaryKey => $this->lastInsertedId()));
				} elseif ( $this->isPrimaryKeyDefined() ) {
					$k = array();
					foreach ($this->getPKColumns() as $col) {
						$k[$col] = $this->rows[0][$col];
					}
					$this->load($k);
					unset($k);
				}
			}
			$this->triggerAfterInsert();
		}

		$this->clearChangedColumns();

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
			if ( !$this->triggerBeforeDelete() )
				return false;
			if (!empty($this->deletedColumn)) {
				array_unshift($values, 1);
				$this->execute('UPDATE '.$this->tableName.' SET '.$this->deletedColumn.' = ? WHERE '.implode(' AND ', $pk), $values);
			} else {
				$this->execute('DELETE FROM '.$this->tableName.' WHERE '.implode(' AND ', $pk), $values);
			}
			$this->triggerAfterDelete();
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
			// $this->triggerBeforeDelete();
			if (!empty($this->deletedColumn)) {
				array_unshift($params, 1);
				$this->execute('UPDATE '.$this->tableName.' SET '.$this->deletedColumn.' = ? WHERE '.implode(' AND ', $where), $params);
			} else {
				$this->execute('DELETE FROM '.$this->tableName.' WHERE '.implode(' AND ', $where), $params);
			}
			// $this->triggerAfterDelete();
		}

		return $this->affectedRows();
	}
	
	/**
	 *  \brief Faz alteração em lote
	 *  
	 *  EXPERIMENTAL!
	 *  
	 *  Permite fazer atualização de registros em lote (UPDATE)
	 */
	public function update(array $values, array $conditions)
	{
		$data = array();
		$where = array();
		$params = array();
		
		foreach ($values as $column => $value) {
			if (in_array($column, $this->writableColumns)) {
				if (is_callable($value)) {
					if (isset($this->hookedColumns[$column]) && method_exists($this, $this->hookedColumns[$column])) {
						$data[] = $column . ' = ' . call_user_func_array(array($this, $this->hookedColumns[$column]), array($value()));
					} else {
						$data[] = $column . ' = ' . $value();
					}
				} else {
					$data[] = $column . ' = ?';
					if (isset($this->hookedColumns[$column]) && method_exists($this, $this->hookedColumns[$column])) {
						$params[] = call_user_func_array(array($this, $this->hookedColumns[$column]), array($value));
					} else {
						$params[] = $value;
					}
				}
			}
		}

		// Monta o conjunto de filtros personalizado da classe herdeira
		if (!$this->filter($conditions, $where, $params)) {
			return false;
		}
		
		$this->execute('UPDATE '.$this->tableName.' SET '.implode(', ', $data).' WHERE '.implode(' AND ', $where), $params);
		
		return ($this->affectedRows() > 0);
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
	 *  \brief Define o array de objetos embutidos
	 *  
	 *  O array de objetos embutidos deve obedecer a estrutura definida para esse atributo.
	 *  
	 *  Cara item do array de objetos embutidos deve ter o nome da classe do objeto como índice e
	 *  ter como valor um array com os seguintes pares chave => valor:
	 *  
	 *  'attr_name' => (string) nome do atributo a ser criado como coluna no registro
	 *  'pk' => (string) nome da coluna que é a chave primária do objeto a ser embutido
	 *  'fk' => (string) nome da coluna no objeto atual a ser usada como chave estrangeira para busca no objeto embutido.
	 *  
	 *  Exemplo de array aceito:
	 *  
	 *  array('Parent_Table' => array('attr_name' => 'parent', 'pk' => 'id', 'fk' => 'parent_id'))
	 */
	public function setEmbeddedObj(array $embeddedObj)
	{
		$this->embeddedObj = $embeddedObj;
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
	 *  \param (array)$orderby - array contendo o filtro de registros no formato 'coluna' => 'ASC'/'DESC'
	 *  \param (int)$offset - inteiro que define o offset de registros
	 *  \param (int)$limit - inteiro que define o limite de registros a serem retornados
	 *  \param (variant)$embbed - esse parâmetro pode ser um array contendo uma estrutura para montagem
	 *    de cláusulas JOIN para a query ou um inteiro. Se omitido, nada será feito com ele.
	 *    Se receber um valor inteiro, fará com que a pesquisa utilize o atributo $this->embeddedObj
	 *    para alimentar as linhas com os dados dos objetos relacionados até a o nível definido por seu valor.
	 *
	 *  \return Retorna TRUE caso tenha efetuado a busca ou FALSE caso não tenha recebido filtros válidos.
	 *  \note Mesmo que o método retorne TRUE, não significa que algum dado tenha sido encontrado.
	 *    Isso representa apenas que a consulta foi efetuado com sucesso. Para saber o resultado da
	 *    consulta, utilize os métodos de recuperação de dados.
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
		// Example of parameter $embbed as array to be used as JOIN
		// array(
			// 'table_name' => array(
				// 'join' => 'INNER',
				// 'on' => 'table_name.id = fk_id',
				// 'columns' => 'table_name.column1 AS table_name_column1, table_name.column2'
			// )
		// )
		// or
		// array(
			// array(
				// 'join' => 'INNER',
				// 'table' => 'table_name',
				// 'on' => 'table_name.id = fk_id',
				// 'columns' => 'table_name.column1 AS table_name_column1, table_name.column2'
			// )
		// )
		if (is_array($embbed)) {
			// Cada item do array de JOINs deve ser um array, cujo índice representa o nome da tabela e contendo
			// as seguintes chaves em seu interior: 'columns', 'join' e 'on'.
			// Cada chave do sub-array representa o seguinte:
			//   'join' determina o tipo de JOIN. Exemplos: 'INNER', 'LEFT OUTER'.
			//   'columns' define lista de campos, separada por vírgulas, a serem acrescidos ao SELECT.
			//     Recomenda-se preceder cada coluna com o nome da tabela para evitar ambiguidade.
			//   'on' é a cláusula ON para junção das tabelas.
			foreach ($embbed as $table => $join) {
				if (!empty($join['columns'])) {
					$select .= ', '.$join['columns'];
				} elseif (!empty($join['fields'])) {
					$select .= ', '.$join['fields'];
				}
				if (!isset($join['join'])) {
					if (!isset($join['type'])) {
						$join['join'] = 'INNER';
					} else {
						$join['join'] = $join['type'];
					}
				}
				$from .= ' '.$join['join'].' JOIN '.(isset($join['table'])?$join['table']:$table).' ON '.$join['on'];
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
				if (!strpos($column, '.') && !strpos($column, '(')) {
					$column = $this->tableName.'.'.$column;
				}
				$order[] = "$column $direction";
			}

			if (!empty($order)) {
				$sql .= ' ORDER BY ' . implode(', ', $order);
			}
		}

		// Monta o limitador de registros
		if ($limit > 0) {
			$sql .= ' LIMIT ?, ?';
			$params[] = $offset;
			$params[] = $limit;
		}

		// Limpa as propriedades da classe
		$this->clearChangedColumns();
		$this->loaded = false;

		// Efetua a busca
		$this->execute($sql, $params);
		$this->rows = $this->fetchAll();
		unset($sql);

		// Faz a contagem de registros do filtro apenas se foi definido um limitador de resultador
		if ($limit > 0) {
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

		// Se o parâmetro $embbed for um inteiro maior que zero e o atributo embeddedObj estiver
		// definido, o relacionamento definido pelo atributo será explorado até o enézimo nível definido por $embbed
		// ATENÇÃO: é recomendado cuidado com o valor de $embbed para evitar loops muito grandes ou estouro de memória,
		// pois os objetos podem relacionar-se cruzadamente causando relacionamento reverso infinito.
		//
		// Example embeddedObj attribute array
		// protected $embeddedObj = array(
		// 	'Table_Class_Child_Obj' => array(
		//		'attr_name' => 'child_or_parent', // name of the attribute to be created on parent row
		//		'pk' => 'id', // Name of the primary key column in this embedded object
		//		'fk' => 'column_id', // Name of the column in parent table user to link to this embedded object
		//	)
		// );
		if ( is_int($embbed) && $embbed > 0 && count($this->embeddedObj) && count($this->rows) > 0 ) {
			foreach ($this->embeddedObj as $obj => $attr) {
				$keys = array();
				foreach ($this->rows as $idx => $row) {
					$this->rows[$idx][ $attr['attr_name'] ] = array();
					if ( !in_array($row[ $attr['fk'] ], $keys) ) {
						$keys[] = $row[ $attr['fk'] ];
					}
				}
				
				$embObj = new $obj;
				$embObj->query(
					array(
						$attr['pk'] => array('in' => $keys)
					),
					array(), 0, 0, $embbed - 1
				);
				while ($er = $embObj->next()) {
					foreach ($this->rows as $idx => $row) {
						if ($er[ $attr['pk'] ] == $row[ $attr['fk'] ]) {
							$this->rows[$idx][ $attr['attr_name'] ][] = $er;
						}
					}
				}
				unset($embObj);
				reset($this->rows);
			}
		}

		// Populate de calculated columns
		if (is_array($this->calculatedColumns) && count($this->calculatedColumns)) {
			foreach ($this->rows as $idx => $row) {
				foreach ($this->calculatedColumns as $column => $method) {
					if (method_exists($this, $method)) {
						$this->rows[$idx][$column] = $this->$method($row);
					} else {
						$this->rows[$idx][$column] = null;
					}
				}
			}
			reset($this->rows);
		}
		
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
     *  \brief Alias de get(), para retornar columns como se fossem propriedades
     *  \param variant $name
     *  \return variant
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     *  \brief Alias de set(), para setar columns como se fossem propriedades
     *  \param string $name
     *  \param variant $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

	/**
	 *  \brief Retorna o registro atual
	 */
    public function current()
    {
        return current($this->rows);
    }

	/**
	 *  \brief Retorna os nomes das colunas
	 */
    public function key()
    {
        return key($this->rows);
    }

	/**
	 *  \brief Alias para reset
	 *  \see reset
	 */
    public function rewind()
    {
        $this->reset();
    }

	/**
	 *  \brief Verifica se o registro atual existe
	 */
    public function valid()
    {
        return $this->current() !== false;
    }

}