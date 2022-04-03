<?php

/**
 * Valuation class for the user-assigned data.
 *
 * @copyright 2007-2018 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.5.0
 */

namespace Springy\Validation;

use Springy\Core\Input;
use Springy\Kernel;
use Springy\Utils\MessageContainer;
use Springy\Utils\Strings;

/**
 * Valuation class for the user-assigned data.
 */
class Validator
{
    // Validator constants
    public const V_ALPHA = 'Alpha';
    public const V_ALPHANUM = 'AlphaNum';
    public const V_BETWEEN = 'Between';
    public const V_DATE = 'Date';
    public const V_DIFFERENT = 'Different';
    public const V_EMAIL = 'Email';
    public const V_IN = 'In';
    public const V_INTEGER = 'Integer';
    public const V_IP = 'Ip';
    public const V_LENGTH_BETWEEN = 'LengthBetween';
    public const V_MAX = 'Max';
    public const V_MAX_LENGTH = 'MaxLength';
    public const V_MIN = 'Min';
    public const V_MIN_LENGTH = 'MinLength';
    public const V_NOT_IN = 'NotIn';
    public const V_NUMERIC = 'Numeric';
    public const V_REGEX = 'Regex';
    public const V_REQUIRED = 'Required';
    public const V_SAME = 'Same';
    public const V_URL = 'Url';

    /** @var string default charset */
    protected $charset = 'UTF-8';
    /** @var array user-assigned data */
    protected $input;
    /** @var array validation rules */
    protected $rules;
    /** @var array custom error messages */
    protected $messages;
    /** @var MessageContainer errors generated after validation */
    protected $errors;
    /** @var string default error message */
    protected $defaultErrorMessage = 'The field :field is invalid. Please enter a valid value.';

    /**
     * Creates and returns an instance of this validator class.
     *
     * @param array $input    User-assigned data
     * @param array $rules    Validation rules
     * @param array $messages Custom error messages
     *
     * @return Validator
     */
    public static function make($input = [], array $rules = [], $messages = [])
    {
        return new static($input, $rules, $messages);
    }

    /**
     * Constructor.
     *
     * @param array $input    User-assigned data
     * @param array $rules    Validation rules
     * @param array $messages Custom error messages
     */
    public function __construct($input = [], array $rules = [], $messages = [])
    {
        $this->charset = Kernel::charset();
        $this->setInput($input);
        $this->rules = $rules;
        $this->messages = $messages;
        $this->errors = new MessageContainer();
    }

    /**
     * Sets the data provided by the user.
     *
     * @param mixed $input An array or a instance of Springy\Core\Input object.
     */
    public function setInput($input)
    {
        $this->input = ($input instanceof Input) ? $input->all() : $input;
    }

    /**
     * Returns the data provided by the user.
     *
     * @return array
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Sets the validation rules.
     *
     * @param array $rules
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Gets the validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Sets the custom error messages.
     *
     * @param array $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * Gets the custom error messges.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Sets the default error message.
     *
     * @param string $errorMsg
     */
    public function setDefaultErrorMessage($errorMsg)
    {
        $this->defaultErrorMessage = $errorMsg;
    }

    /**
     * Gets the default error message.
     *
     * @return string
     */
    public function getDefaultErrorMessage()
    {
        return $this->defaultErrorMessage;
    }

    /**
     * Gets the generated errors.
     *
     * @return Springy\Utils\MessageContainer
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * An alias for validate method.
     *
     * @see validade
     *
     * @return bool
     */
    public function passes()
    {
        return $this->validate();
    }

    /**
     * An inverted alias to validate.
     *
     * @return bool The inverted value of validate medhot.
     */
    public function fails()
    {
        return !$this->validate();
    }

    /**
     * Run the validation.
     *
     * @return bool Returns true if no errors in validation or false if has errors.
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
     * Applies the rules to each existing field.
     *
     * @param string $field name of the field.
     * @param mixed  $rules an array or a pipe separeted validation rules.
     *
     * @throws \BadMethodCallException in case of an inexistent validation type.
     */
    protected function applyRules($field, $rules)
    {
        foreach ($this->explodeRules($rules) as $rule) {
            if (!method_exists($this, $rule['method'])) {
                throw new \BadMethodCallException(
                    'Validation rule "' . $rule['rule'] . '" has no equivalent method for validation.'
                );
            }

            if (!call_user_func([$this, $rule['method']], $field, $rule['params'])) {
                $this->errors->add($field, $this->parseErrorMessage($field, $rule['rule']));
            }
        }
    }

    /**
     * Parse the rules.
     *
     * @param string|array $rules An array or a string with the rules delimited by pipe char '|'.
     *
     * @return array an array with parsed rules.
     */
    protected function explodeRules($rules)
    {
        $explodedRules = [];

        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $key => $rule) {
            $ruleAndParams = is_int($key)
                ? (array) explode(':', $rule)
                : [$key, $rule];
            $params = [];

            if (isset($ruleAndParams[1])) {
                $params = is_string($ruleAndParams[1])
                    ? (array) explode(',', $ruleAndParams[1])
                    : $ruleAndParams[1];
            }

            $explodedRules[] = [
                'rule'   => $ruleAndParams[0],
                'method' => $this->parseMethod($ruleAndParams[0]),
                'params' => $params,
            ];
        }

        return $explodedRules;
    }

    /**
     * Generates the method name equivalent to the rule.
     *
     * @param string $rule the name of the rule.
     *
     * @return string
     */
    protected function parseMethod($rule)
    {
        return 'validate' . str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $rule))));
    }

    /**
     * Generates the error message for the current field and message.
     *
     * @param string $field the field.
     * @param string $rule  the rule.
     *
     * @return string
     */
    protected function parseErrorMessage($field, $rule)
    {
        $message = isset($this->messages[$field][$rule]) ? $this->messages[$field][$rule]
                                                         : $this->defaultErrorMessage;

        return str_replace([':field', ':rule'], [$field, $rule], $message);
    }

    /**
     * Validates if the field exists and has some value.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateRequired($field, $params)
    {
        return isset($this->input[$field]) && $this->input[$field] !== '';
    }

    /**
     * Validates if the value meets the minimum required.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateMin($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return $this->input[$field] >= $params[0];
    }

    /**
     * Validates if the value meets the maximum allowed.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateMax($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return $this->input[$field] <= $params[0];
    }

    /**
     * Validates if the value is between the minimum and maximum range.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateBetween($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return ($this->input[$field] >= $params[0]) && ($this->input[$field] <= $params[1]);
    }

    /**
     * Validates if the text has the shortest required length.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return bool Returns true is case of success or false if has no minimum size.
     */
    public function validateMinLength($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return mb_strlen($this->input[$field], $this->charset) >= $params[0];
    }

    /**
     * Validates if text matches the longest length allowed.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateMaxLength($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return mb_strlen($this->input[$field], $this->charset) <= $params[0];
    }

    /**
     * Validates if the text length is within the allowed range.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateLengthBetween($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        $length = mb_strlen($this->input[$field], $this->charset);

        return ($length >= $params[0]) && ($length <= $params[1]);
    }

    /**
     * Validates whether the value is numeric.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateNumeric($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return is_numeric($this->input[$field]);
    }

    /**
     * Validates whether the value is an email address.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateEmail($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return Strings::validateEmailAddress($this->input[$field]);
    }

    /**
     * Validates whether the value has only letters.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateAlpha($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return preg_match('/^\pL+$/u', $this->input[$field]);
    }

    /**
     * Validates whether the value has only letters and numbers.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateAlphaNum($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return preg_match('/^[\pL\pN]+$/u', $this->input[$field]);
    }

    /**
     * Validates whether the value is the same as that of another field.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateSame($field, $params)
    {
        if (!isset($this->input[$field]) || !isset($this->input[$params[0]])) {
            return false;
        }

        return $this->input[$field] === $this->input[$params[0]];
    }

    /**
     * Validates if the value differs from that of another field.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateDifferent($field, $params)
    {
        if (!isset($this->input[$field]) || !isset($this->input[$params[0]])) {
            return true;
        }

        return $this->input[$field] !== $this->input[$params[0]];
    }

    /**
     * Validates if the value is a valid date.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
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
     * Validates whether the value is an integer.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateInteger($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return filter_var($this->input[$field], FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate value using regular expression.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateRegex($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return preg_match(implode(',', $params), $this->input[$field]);
    }

    /**
     * Validates if the value is a URL.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateUrl($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return filter_var($this->input[$field], FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validates if the value is in a list.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateIn($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return in_array($this->input[$field], $params);
    }

    /**
     * Validates if the value is not in a list.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateNotIn($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return !in_array($this->input[$field], $params);
    }

    /**
     * Validates whether the value is an IP address.
     *
     * @param string $field  the name of the field.
     * @param array  $params an array with parameters.
     *
     * @return boolan
     */
    public function validateIp($field, $params)
    {
        if (!isset($this->input[$field])) {
            return true;
        }

        return filter_var($this->input[$field], FILTER_VALIDATE_IP) !== false;
    }
}
