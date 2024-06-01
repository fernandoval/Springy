<?php

/**
 * Test case for Validation\Validator class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2015 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.3
 */

use PHPUnit\Framework\TestCase;
use Springy\Validation\Validator;

class ValidatorTest extends TestCase
{
    public function testBasicValidationPassing()
    {
        $input = [
            'required' => 'hasSomething',
            'min' => '7',
            'between' => '10',
            'numeric' => '6',
            'email' => 'allan.marques@ymail.com',
            'alpha' => 'testeteste',
            'alpha_num' => 'teste123',
            'same' => 'confirmation',
            'same_confirmation' => 'confirmation',
            'different' => 'blabla1',
            'different_comparison' => 'blabla2',
            'date' => '27/09/2009',
            'integer' => '12',
            'max' => '50',
            'regex' => 'teste',
            'url' => 'http://www.mydomain.com',
            'in' => 'test2',
            'not_in' => 'test4',
            'ip' => '127.0.0.1',
            'min_length' => '12345678901',
            'max_length' => '12345',
            'length_between' => '1234567',
        ];

        $validation = new Validator($input, $this->rules());

        $this->assertTrue($validation->passes());
        $this->assertFalse($validation->fails());
    }

    private function rules()
    {
        return [
            'required' => 'required',
            'min' => 'min:5',
            'between' => 'between:5,12',
            'numeric' => 'numeric',
            'email' => 'email',
            'alpha' => 'alpha',
            'alpha_num' => 'alpha_num',
            'same' => 'same:same_confirmation',
            'same_confirmation' => 'required',
            'different' => 'different:different_comparison',
            'different_comparison' => 'required',
            'date' => 'date',
            'integer' => 'integer',
            'max' => 'max:100',
            'regex' => 'regex:/^[a-z]+/i',
            'url' => 'url',
            'in' => 'in:test1,test2,test3',
            'not_in' => 'not_in:test1,test2,test3',
            'ip' => 'ip',
            'min_length' => 'min_length:10',
            'max_length' => 'max_length:10',
            'length_between' => 'length_between:5,10',
        ];
    }
}
