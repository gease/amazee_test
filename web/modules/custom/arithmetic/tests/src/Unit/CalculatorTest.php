<?php


namespace Drupal\Tests\arithmetic\Unit;


use Drupal\Tests\UnitTestCase;
use Drupal\arithmetic\Calculator;
use Drupal\arithmetic\ArithmeticException;

/**
 * Class ParserTest.
 *
 * @group arithmetic
 *
 * @coversDefaultClass \Drupal\arithmetic\Calculator
 */
class CalculatorTest extends UnitTestCase {

  /* @var \Drupal\arithmetic\CalculatorInterface $calculator */
  protected $calculator;

  /* @var \ReflectionMethod $parseInfix */
  protected $parseInfix;

  /**
   * {inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->calculator = new Calculator();
    $reflection_calculator = new \ReflectionClass($this->calculator);
    $this->parseInfix = $reflection_calculator->getMethod('parseInfix');
    $this->parseInfix->setAccessible(TRUE);
  }

  /**
   * @covers ::calculateInfix
   * @dataProvider stringsProvider
   */
  public function testCalculateInfix($string, $expected) {
    $this->assertEquals($expected, $this->calculator->calculateInfix($string));
  }

  /**
   * @covers ::calculateInfix
   * @dataProvider stringsExceptionProvider
   */
  public function testCalculateInfixExceptions($string) {
    $this->expectException(ArithmeticException::class);
    $this->calculator->calculateInfix($string);
  }

  /**
   * @covers ::parseInfix
   * @dataProvider stringsParseProvider
   */
  public function testParseInfix($string, $expected) {
    $this->assertEquals($expected, $this->parseInfix->invokeArgs($this->calculator, [$string]));
  }

  /**
   * Data provider to test infix calculation.
   */
  public function stringsProvider() {
    return [
      ['10+5', '15'],
      ['12-(4*3)', '0'],
      ['15/3-(2+(6-4))', '1'],
    ];
  }

  /**
   * Data provider of incorrect strings, that should throw exceptions.
   */
  public function stringsExceptionProvider() {
    return [
      ['10++4*'],
      ['A+12'],
      ['((7+2)*3'],
    ];
  }

  /**
   * Data provider for parsing infix string to postfix stack.
   */
  public function stringsParseProvider() {
    return [
      ['10+5', ['10', '5', '+']],
      ['(12+4)*3', ['12', '4', '+', '3', '*']],
    ];
  }
}
