<?php
/**	\file
 *  FVAL PHP Framework for Web Applications
 *
 *  \copyright Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.
 *  \copyright Copyright (c) 2007-2014 Fernando Val
 *
 *  \brief		Classe Model para acesso a banco de dados
 *  \note		Essa classe extende a classe DB.
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	1.4.7
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
    /// Container de mensagem de erros de validação.
    protected $validationErrors;

    /**
	 *  \brief Método construtor da classe.
	 *
	 *  \param $filtro - Filto de busca, opcional. Deve ser um array de campos ou inteiro com ID do usuário.
	 */
	function __construct($database='default', $filter=null)
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
	protected function filter($filter, array &$where, array &$params)
	{
		return false;
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

			if ($this->affectedRows() > 0 && $this->lastInsertedId() && !empty($this->primaryKey) && count($this->primaryKey) == 1 && empty($this->rows[0][$this->primaryKey[0]])) {
				$this->rows[0][$this->primaryKey[0]] = $this->lastInsertedId();
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
			foreach($this->primaryKey as $column) {
				$pk[] = '`'.$column.'` = ?';
				$values[] = $this->rows[0][$column];
			}

			// Faz a exclusão lógica ou física do registro
			if (!empty($this->deletedColumn)) {
				array_unshift($values, 1);
				$this->execute('UPDATE `'.$this->tableName.'` SET `'.$this->deletedColumn.'` = ? WHERE '.implode(' AND ', $pk), $values);
			} else {
				$this->execute('DELETE FROM `'.$this->tableName.'` WHERE '.implode(' AND ', $pk), $values);
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
				$where[] = '`'.$this->deletedColumn.'` = ?';
				$params[] = 0;
			}

			// Faz a exclusão lógica ou física do(s) registro(s)
			if (!empty($this->deletedColumn)) {
				array_unshift($params, 1);
				$this->execute('UPDATE `'.$this->tableName.'` SET `'.$this->deletedColumn.'` = ? WHERE '.implode(' AND ', $where), $params);
			} else {
				$this->execute('DELETE FROM `'.$this->tableName.'` WHERE '.implode(' AND ', $where), $params);
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
	 *  \brief Método de consulta ao banco de dados
	 *
	 *  \return Retorna TRUE caso tenha efetuado a busca ou FALSE caso não tenha recebido filtros válidos.
	 */
	public function query(array $filter=null, array $orderby=array(), $offset=NULL, $limit=NULL)
	{
		// Se nenhuma chave de busca foi passada, utiliza dados do objeto
		if (is_null($filter) && !empty($this->rows)) {
			$filter = $this->rows[0];
		}

		// Abandona caso não nenhum filtro tenha sido definido (evita retornar toda a tabela como resultado)
		if (empty($filter)) {
			return false;
		}

		// Monta o conjunto de colunas da busca
		if (is_array($this->tableColumns)) {
			$columns = implode(', ', $this->tableColumns);
		} else {
			$columns = $this->tableColumns;
		}

		$sql = 'SELECT '.($this->driverName() == 'mysql' ? 'SQL_CALC_FOUND_ROWS ' : "").$columns.' FROM `'.$this->tableName.'` WHERE ';
		unset($columns);
		$where = array();
		$orderby = array();
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
			$where[] = '`'.$this->deletedColumn.'` = ?';
			if (isset($filter[$this->deletedColumn])) {
				$params[] = (int)$filter[$this->deletedColumn];
			} else {
				$params[] = 0;
			}
		}

		$sql .= implode(' AND ', $where);

		// Monta a ordenação do resultado de busca
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
				$this->execute('SELECT FOUND_ROWS() AS `found_rows`');
			} else {
				array_pop($params);
				array_pop($params);
				$this->execute('SELECT COUNT(0) AS `found_rows` FROM `'.$this->tableName.'` WHERE '.implode(' AND ', $where), $params);
			}
			$columns = $this->fetchNext();
			$this->dbNumRows = (int)$columns['found_rows'];
		} else {
			$this->dbNumRows = count($this->rows);
		}
		unset($where, $params);

		return true;
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
	 *  \brief Dá o número de registros carregados em memória
	 *
	 *  \return Retorna a quantidade de registros contidos do offset de dados do objeto
	 */
	public function count()
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