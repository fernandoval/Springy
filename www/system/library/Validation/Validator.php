<?php
/**	\file
 *  Springy.
 *
 *  \brief      Validador de dados de input do usuário.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.2.1
 *  \ingroup    framework
 */
namespace Springy\Validation;

use Springy\Core\Input;
use Springy\Utils\MessageContainer;
use Springy\Utils\Strings;

/**
 * \brief		Validador de dados de input do usuário.
 */
class Validator
{
    /// Dados de input do usuário
    protected $input;
    /// Regras de validação
    protected $rules;
    /// Mensagens de erro
    protected $messages;
    /// Erros gerados após a validação
    protected $errors;
    /// Mensagem de erro padrão
    protected $defaultErrorMessage = 'The field :field is invalid. Please enter a valid value.';

    /**
     *  \brief Cria e retorna uma instancia desta classe de validador.
     *  \param [in] (array) $input - Dados de input do usuário.
     *  \param [in] (array) $rules - Regras de validação.
     *  \param [in] (array) $messages - Mensagens de erros para cada campo.
     *  \return \static.
     */
    public static function make($input = [], array $rules = [], $messages = [])
    {
        return new static($input, $rules, $messages);
    }

    /**
     *  \brief Construtor da classe.
     *  \param [in] (array) $input - Dados de input do usuário.
     *  \param [in] (array) $rules - Regras de validação.
     *  \param [in] (array) $messages - Mensagens de erros para cada campo.
     */
    public function __construct($input = [], array $rules = [], $messages = [])
    {
        $this->setInput($input);
        $this->rules = $rules;
        $this->messages = $messages;
        $this->errors = new MessageContainer();
    }

    /**
     *  \brief Seta os dados de input do usuário.
     *  \param [in] (variant) $input.
     */
    public function setInput($input)
    {
        $this->input = ($input instanceof Input) ? $input->all() : $input;
    }

    /**
     *  \brief Retorna os dados de input do usuário.
     *  \return (array).
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     *  \brief Seta as regras de validação.
     *  \param [in] (array) $rules.
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     *  \brief Retorna as regras de validação.
     *  \return (array).
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     *  \brief Seta as mensagens de erro para cada campo.
     *  \param [in] (array) $messages.
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     *  \brief Retorna as mensagens de erro para cada campo.
     *  \return (array).
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     *  \brief Seta a mensagem de erro padrão.
     *  \param [in] (string) $errorMsg.
     */
    public function setDefaultErrorMessage($errorMsg)
    {
        $this->defaultErrorMessage = $errorMsg;
    }

    /**
     *  \brief Seta a mensagem de erro padrão.
     *  \return string.
     */
    public function getDefaultErrorMessage()
    {
        return $this->defaultErrorMessage;
    }

    /**
     *  \brief Retorna as mensagens de erros geradas pela última validação.
     *  \return Springy\Utils\MessageContainer.
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     *  \brief Alias de 'validate()'.
     *  \return (boolean).
     */
    public function passes()
    {
        return $this->validate();
    }

    /**
     *  \brief Alias 'inverso' de 'validate()'.
     *  \return (boolean).
     */
    public function fails()
    {
        return !$this->validate();
    }

    /**
     *  \brief Roda a validação dos campos de acordo com as regras
     *         e retorna verdadeiro se passou e falso caso contrário.
     *  \return (boolean).
     */
    public function validate()
    {
        if ($this->errors->hasAny()) {
            $this->errors = new MessageContainer();
        }

        foreach ($this->rules as $field => $rules) {
            $this->applyRules($field, $rules);
        }

        return !$this->errors->hasAny();
    }

    /**
     *  \brief Aplica as regras para cada campo existente.
     *  \param [in] (string) $field - Campo sendo validado atualmente.
     *  \param [in] (string) $rules - Regras à serem aplicadas neste campo.
     *  \throws \BadMethodCallException.
     */
    protected function applyRules($field, $rules)
    {
        foreach ($this->explodeRules($rules) as $rule) {
            if (!method_exists($this, $rule['method'])) {
                throw new \BadMethodCallException('Validation rule "'.$rule['rule'].'" has no equivalent method for validation.');
            }

            if (!call_user_func([$this, $rule['method']], $field, $rule['params'])) {
                $this->errors->add($field, $this->parseErrorMessage($field, $rule['rule']));
            }
        }
    }

    /**
     *  \brief Parse the rules.
     *  \param [in] (string|array) $rules - An array or a string with the rules delimiter by pipe char '|'.
     *  \return (array) an array with parsed rules.
     */
    protected function explodeRules($rules)
    {
        $explodedRules = [];

        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $rule) {
            $explodedRules[] = $this->parseRule($rule);
        }

        return $explodedRules;
    }

    /**
     *  \brief Compila a regra atual para um array amigável para tratamento.
     *  \param [in] (string) $rule - Regra atual.
     *  \return (array).
     */
    protected function parseRule($rule)
    {
        $ruleAndParams = (array) explode(':', $rule);

        return [
            'rule'   => $ruleAndParams[0],
            'method' => $this->parseMethod($ruleAndParams[0]),
            'params' => isset($ruleAndParams[1]) ? (array) explode(',', $ruleAndParams[1]) : [],
        ];
    }

    /**
     *  \brief Gera o nome do método equivalente à regra.
     *  \param [in] (string) $rule - Nome da Regra.
     *  \return (string).
     */
    protected function parseMethod($rule)
    {
        return 'validate'.str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $rule))));
    }

    /**
     *  \brief Gera a mensagem de erro para o campo e mensagem atual.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (string) $rule - Regra atual.
     *  \return (string).
     */
    protected function parseErrorMessage($field, $rule)
    {
        $message = isset($this->messages[$field][$rule]) ? $this->messages[$field][$rule]
                                                         : $this->defaultErrorMessage;

        return str_replace([':field', ':rule'], [$field, $rule], $message);
    }

    /**
     *  \brief Valida se o campo está presente e tem algum valor.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateRequired($field, $params)
    {
        return isset($this->input[$field]) && $this->input[$field] != '';
    }

    /**
     *  \brief Valida se o valor entrado pelo usuário tem no mínimo o valor do parâmetro da regra.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateMin($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return $this->input[$field] >= $params[0];
    }

    /**
     *  \brief Valida se o valor entrado pelo usuário tem no máximo o valor do parâmetro da regra.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateMax($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return $this->input[$field] <= $params[0];
    }

    /**
     *  \brief Valida se o valor entrado pelo usuário possui valor entre os parâmetros da regra.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateBetween($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return ($this->input[$field] >= $params[0]) && ($this->input[$field] <= $params[1]);
    }

    /**
     *  \brief Valida se o texto entrado pelo usuário possui a qtd de caracteres
     *         de pelo menos o valor passado por parâmetro pela regra.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateMinLength($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return strlen($this->input[$field]) >= $params[0];
    }

    /**
     *  \brief Valida se o texto entrado pelo usuário possui a qtd de caracteres
     *         de no máximo o valor passado por parâmetro pela regra.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateMaxLength($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return strlen($this->input[$field]) <= $params[0];
    }

    /**
     *  \brief Valida se o texto entrado pelo usuário possui a qtd de caracteres
     *         entre os valores passados por parâmetro pela regra.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateLengthBetween($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        $length = strlen($this->input[$field]);

        return ($length >= $params[0]) && ($length <= $params[1]);
    }

    /**
     *  \brief Valida se é um numérico.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateNumeric($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return is_numeric($this->input[$field]);
    }

    /**
     *  \brief Valida se é um email.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateEmail($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return Strings::validateEmailAddress($this->input[$field]);
    }

    /**
     *  \brief Valida se são caracteres somente alfabéticos.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateAlpha($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return preg_match('/^\pL+$/u', $this->input[$field]);
    }

    /**
     *  \brief Valida se são caracteres somente alfa-numericos.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateAlphaNum($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return preg_match('/^[\pL\pN]+$/u', $this->input[$field]);
    }

    /**
     *  \brief Valida se tem o mesmo valor que outro campo passado por parâmetro.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateSame($field, $params)
    {
        if (!isset($this->input[$field]) || !isset($this->input[$params[0]])) {
            return false;
        }

        return $this->input[$field] === $this->input[$params[0]];
    }

    /**
     *  \brief Valida se tem o valor diferente de outro campo passado por parâmetro.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateDifferent($field, $params)
    {
        if (!isset($this->input[$field]) || !isset($this->input[$params[0]])) {
            return true;
        }

        return $this->input[$field] !== $this->input[$params[0]];
    }

    /**
     *  \brief Valida se é uma data válida.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateDate($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        $date = date_parse($this->input[$field]);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     *  \brief Valida se é um inteiro.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateInteger($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return filter_var($this->input[$field], FILTER_VALIDATE_INT) !== false;
    }

    /**
     *  \brief Valida se o valor passa pela expressão regular.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateRegex($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return preg_match($params[0], $this->input[$field]);
    }

    /**
     *  \brief Valida se é uma url válida.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateUrl($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return filter_var($this->input[$field], FILTER_VALIDATE_URL) !== false;
    }

    /**
     *  \brief Valida valor é igual à um dos da lista passada por parâmetro.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateIn($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return in_array($this->input[$field], $params);
    }

    /**
     *  \brief Valida valor é diferentes de todos da lista passada por parâmetro.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateNotIn($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return !in_array($this->input[$field], $params);
    }

    /**
     *  \brief Valida se é um IP Válido.
     *  \param [in] (string) $field - Campo atual.
     *  \param [in] (array) $params - Parâmetros da regra.
     *  \return (boolan).
     */
    public function validateIp($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return filter_var($this->input[$field], FILTER_VALIDATE_IP) !== false;
    }
}
