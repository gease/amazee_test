<?php


namespace Drupal\arithmetic;

/**
 * Parse and calculate simple arithmetical expressions.
 *
 * @package Drupal\arithmetic
 */
class Calculator implements CalculatorInterface {

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
    $queue = $this->parsePostfix($string);
    return $this->reduceQueue($queue);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateInfix($string) {
    $queue = $this->parseInfix($string);
    return $this->reduceQueue($queue);
  }

  /**
   * Parses string with infix expression.
   *
   * @param string $string
   *    Input string representing arithmetical expression in infix notation.
   *
   * @return array
   *    Parsed queue
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
    $queue = [];
    // Queue index.
    $j = 0;
    for ($i = 0; $i < $l; $i++) {
      if ($string[$i] === $this->delimiter || in_array($string[$i], $this->signs)) {
        if ($string[$i - 1] !== $this->delimiter) {
          $j++;
        }
        if (in_array($string[$i], $this->signs)) {
          $queue[$j] = $string[$i];
        }
        continue;
      }
      if (ctype_digit($string[$i])) {
        $queue[$j] = isset($queue[$j]) ? $queue[$j] . $string[$i] : $string[$i];
        continue;
      }
      throw new ArithmeticException('Unallowed character in expression ' . $string);
    }
    return $queue;
  }

  /**
   * Calculates the value of expression from postfix queue.
   *
   * @param array $queue
   *    Postfix queue.
   *
   * @return string
   *    A number - value of expression.
   *
   * @throws \Drupal\arithmetic\ArithmeticException
   */
  protected function reduceQueue($queue) {
    do {
      $count = count($queue);
      $i = 0;
      while (ctype_digit($queue[$i])) {
        $i++;
      }
      if (!isset($queue[$i - 1]) || !isset($queue[$i - 2]) ||
        !ctype_digit($queue[$i - 1]) || !ctype_digit($queue[$i - 2])) {
        $stack = debug_backtrace(FALSE, 2);
        throw new ArithmeticException('Error while reducing queue on expression ' . $stack[1]['args'][0]);
      }
      switch ($queue[$i]) {
        case '+':
          $res = $queue[$i - 2] + $queue[$i - 1];
          break;

        case '-':
          $res = $queue[$i - 2] - $queue[$i - 1];
          break;

        case '*':
          $res = $queue[$i - 2] * $queue[$i - 1];
          break;

        case '/':
          $res = $queue[$i - 2] / $queue[$i - 1];
          break;
      }
      $new = [];
      for ($j = 0; $j < $i - 2; $j++) {
        $new[$j] = $queue[$j];
      }
      $new[$i - 2] = (string) $res;
      for ($j = $i + 1; $j < count($queue); $j++) {
        $new[$j - 2] = $queue[$j];
      }
      if (count($new) != ($count - 2)) {
        $stack = debug_backtrace(FALSE, 2);
        throw new ArithmeticException('Error while reducing stack on expression ' . $stack[1]['args'][0]);
      }
      $queue = $new;
    } while (count($queue) > 1);
    return $queue[0];
  }

}
