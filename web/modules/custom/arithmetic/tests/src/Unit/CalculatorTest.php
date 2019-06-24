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
  /* @var \ReflectionMethod $parsePostfix */
  protected $parsePostfix;

  /**
   * {inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->calculator = new Calculator();
    $reflection_calculator = new \ReflectionClass($this->calculator);
    $this->parseInfix = $reflection_calculator->getMethod('parseInfix');
    $this->parseInfix->setAccessible(TRUE);
    $this->parsePostfix = $reflection_calculator->getMethod('parsePostfix');
    $this->parsePostfix->setAccessible(TRUE);
  }

  /**
   * @covers ::calculateInfix
   * @dataProvider stringsInfixProvider
   */
  public function testCalculateInfix($string, $expected) {
    $this->assertEquals($expected, $this->calculator->calculateInfix($string));
  }

  /**
   * @covers ::calculatePostfix
   * @dataProvider stringsPostfixProvider
   */
  public function testCalculatePostfix($string, $expected) {
    $this->assertEquals($expected, $this->calculator->calculatePostfix($string));
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
   * @dataProvider stringsParseInfixProvider
   */
  public function testParseInfix($string, $expected) {
    $this->assertEquals($expected, $this->parseInfix->invokeArgs($this->calculator, [$string]));
  }

  /**
   * @covers ::parsePostfix
   * @dataProvider stringsParsePostfixProvider
   */
  public function testParsePostfix($string, $expected) {
    $this->assertEquals($expected, $this->parsePostfix->invokeArgs($this->calculator, [$string]));
  }

  /**
   * Data provider to test infix calculation.
   */
  public function stringsInfixProvider() {
    return [
      ['10+5', '15'],
      ['12-(4*3)', '0'],
      ['15/3-(2+(6-4))', '1'],
    ];
  }

  /**
   * Data provider to test postfix calculation.
   */
  public function stringsPostfixProvider() {
    return [
      ['10 5 +', '15'],
      ['12 4 + 3 *', '48'],
      ['10 5 3 * +', '25'],
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
   * Data provider for parsing infix string to postfix queue.
   */
  public function stringsParseInfixProvider() {
    return [
      ['10+5', ['10', '5', '+']],
      ['(12+4)*3', ['12', '4', '+', '3', '*']],
    ];
  }

  /**
   * Data provider for parsing postfix string to postfix queue.
   */
  public function stringsParsePostfixProvider() {
    return [
      ['10 5 +', ['10', '5', '+']],
      ['12 4 + 3 *', ['12', '4', '+', '3', '*']],
      ['10 5 3 * +', ['10', '5', '3', '*', '+']],
    ];
  }

}
