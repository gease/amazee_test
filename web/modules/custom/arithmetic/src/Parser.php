<?php


namespace Drupal\arithmetic;

/**
 * Parse and calculate simple arithmetical expressions.
 *
 * @package Drupal\arithmetic
 */
class Parser implements ParserInterface {

  protected $delimiter = ' ';

  protected $signs = ['-', '+', '/', '*'];

  protected $precedence = [
    '-' => 0,
    '+' => 0,
    '*' => 1,
    '/' => 1,
  ];

  /**
   * {@inheritdoc}
   */
  public function calculatePostfix($string) {
    $stack = $this->parsePostfix($string);
    return $this->reduceStack($stack);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateInfix($string) {
    $stack = $this->parseInfix($string);
    return $this->reduceStack($stack);
  }

  /**
   * Parses string with infix expression.
   *
   * @param string $string
   *    Input string representing arithmetical expression in infix notation.
   *
   * @return array
   *    Parsed stack
   */
  protected function parseInfix($string) {
    $queue = [];
    $op_stack = [];
    $input = preg_split('/([+-\/*\(\)])/', $string, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    for ($i = 0; $i < count($input); $i++) {
      $this->sortSymbol($input[$i], $queue, $op_stack);
    }
    for ($i = count($op_stack); $i > 0; $i--) {
      $queue[] = $op_stack[$i - 1];
    }
    return $queue;
  }

  /**
   * Implements shunting yard algorithm (https://en.wikipedia.org/wiki/Shunting-yard_algorithm).
   *
   * @param string $symbol
   *    Next symbol in expression.
   * @param array $queue
   *    Postfix queue.
   * @param array $op_stack
   *    Operators stack.
   *
   * @throws \Drupal\arithmetic\ArithmeticException
   */
  protected function sortSymbol($symbol, &$queue, &$op_stack) {
    if (ctype_digit($symbol)) {
      $queue[] = $symbol;
      return;
    }
    if (in_array($symbol, $this->signs)) {
      while (!empty($op_stack) && $op_stack[count($op_stack) - 1] != '(' &&
         $this->precedence[$op_stack[count($op_stack) - 1]] >= $this->precedence[$symbol]) {
        $queue[] = array_pop($op_stack);
      }
      $op_stack[] = $symbol;
      return;
    }
    if ($symbol == '(') {
      $op_stack[] = $symbol;
      return;
    }
    if ($symbol == ')') {
      while (isset($op_stack[count($op_stack) - 1]) && $op_stack[count($op_stack) - 1] != '(') {
        $queue[] = array_pop($op_stack);
      }
      array_pop($op_stack);
      return;
    }
    $stack = debug_backtrace(FALSE, 2);
    throw new ArithmeticException("Unallowed character in expression " . $stack[1]['args'][0]);
  }

  /**
   * Parses string with postfix expression.
   *
   * @param string $string
   *    Input string representing arithmetical expression in postfix notation.
   *
   * @return array
   *    Parsed stack.
   *
   * @throws \Drupal\arithmetic\ArithmeticException
   */
  protected function parsePostfix($string) {
    $l = strlen($string);
    $stack = [];
    // Stack index.
    $j = 0;
    for ($i = 0; $i < $l; $i++) {
      if ($string[$i] === $this->delimiter || in_array($string[$i], $this->signs)) {
        if ($string[$i - 1] !== $this->delimiter) {
          $j++;
        }
        if (in_array($string[$i], $this->signs)) {
          $stack[$j] = $string[$i];
        }
        continue;
      }
      if (ctype_digit($string[$i])) {
        $stack[$j] = isset($stack[$j]) ? $stack[$j] . $string[$i] : $string[$i];
        continue;
      }
      throw new ArithmeticException('Unallowed character in expression ' . $string);
    }
    return $stack;
  }

  /**
   * Calculates the value of expression from postfix stack.
   *
   * @param array $stack
   *    Postfix stack.
   *
   * @return string
   *    A number - value of expression.
   *
   * @throws \Drupal\arithmetic\ArithmeticException
   */
  protected function reduceStack($stack) {
    do {
      $count = count($stack);
      $i = 0;
      while (ctype_digit($stack[$i])) {
        $i++;
      }
      if (!isset($stack[$i - 1]) || !isset($stack[$i - 2]) ||
        !ctype_digit($stack[$i - 1]) || !ctype_digit($stack[$i - 2])) {
        $stack = debug_backtrace(FALSE, 2);
        throw new ArithmeticException('Error while reducing stack on expression ' . $stack[1]['args'][0]);
      }
      switch ($stack[$i]) {
        case '+':
          $res = $stack[$i - 2] + $stack[$i - 1];
          break;

        case '-':
          $res = $stack[$i - 2] - $stack[$i - 1];
          break;

        case '*':
          $res = $stack[$i - 2] * $stack[$i - 1];
          break;

        case '/':
          $res = $stack[$i - 2] / $stack[$i - 1];
          break;
      }
      $new = [];
      for ($j = 0; $j < $i - 2; $j++) {
        $new[$j] = $stack[$j];
      }
      $new[$i - 2] = (string) $res;
      for ($j = $i + 1; $j < count($stack); $j++) {
        $new[$j - 2] = $stack[$j];
      }
      if (count($new) != ($count - 2)) {
        $stack = debug_backtrace(FALSE, 2);
        throw new ArithmeticException('Error while reducing stack on expression ' . $stack[1]['args'][0]);
      }
      $stack = $new;
    } while (count($stack) > 1);
    return $stack[0];
  }

}
