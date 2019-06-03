<?php


namespace Drupal\arithmetic;


interface ParserInterface {

  /**
   * Parse and calculate the expression in postfix notation.
   *
   * @param string $string
   *    Expression in postfix notation.
   */
  public function calculatePostfix($string);

  /**
   * Parse and calculate the expression in infix notation.
   *
   * @param string $string
   *    Expression in postfix notation.
   */
  public function calculateInfix($string);

}