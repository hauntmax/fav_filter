<?php

namespace CP\Filter\tests;

use PHPUnit\Framework\TestCase;
use CP\Filter\Filter;
use CP\Filter\Tokens as T;

class FilterTest extends TestCase
{
    /** @var Filter $filter */
    protected $filter;

    protected $join_id;
    protected $field_id;
    protected $history_table;
    protected $sensor_table;

    public function setUp(): void
    {
        $this->filter = new Filter();
    }

    public function testCmp()
    {
        $expect = new T\EqExpr(
            new T\IntVal( '1' ),
            new T\IntVal( '2' ),
        );

        $ast = $this->filter->getAst('eq(1, 2)');
        $ast2 = $this->filter->getAst('eq(1,2)');

        $this->assertEquals( $expect, $ast, 'Not valid eq ast' );
        $this->assertEquals( $expect, $ast2, 'Not valid eq ast (whitespace)' );
    }

    public function testCmpFld()
    {
        $expect = new T\EqExpr(
            new T\FldVal('Id'),
            new T\StrVal( '"str"' ), // constructor will remove ""
        );

        $ast = $this->filter->getAst('eq(Id, "str")');

        $this->assertEquals( $expect, $ast, 'Not valid field with string ast' );
    }

    public function testEscapeStr()
    {
        $expect = new T\NotExpr( new T\StrVal('"a\\"b\\"c"') );
        $ast = $this->filter->getAst('not("a\\"b\\"c")');

        $this->assertEquals( $expect, $ast, 'Not valid escape str in ast' );

        $expect = new T\NotExpr( new T\StrVal("'a\\'b\\'c'") );
        $ast = $this->filter->getAst("not('a\\'b\\'c')");

        $this->assertEquals( $expect, $ast, 'Not valid escape str in ast' );

        $expect = new T\NotExpr( new T\StrVal("'a\\c'") );
        $ast = $this->filter->getAst("not('a\\c')");

        $this->assertEquals( $expect, $ast, 'Not valid escape str in ast' );
    }

    public function eqApplyTest()
    {
        $ast = new T\EqExpr(
            new T\FldVal( 'ApplyFld' ),
            new T\StrVal( '"value"' ),
            '='
        );

        $this->assertTrue( $ast->apply(['ApplyFld' => 'value']), 'Not valid apply with str and fld' );
        $this->assertFalse( $ast->apply(['ApplyFld' => 'value2']), 'Not valid apply with str and fld' );
    }

    public function testAndExpr()
    {
        $expect = new T\AndExpr(
            new T\IntVal( '1' ),
            new T\IntVal( '0' )
        );

        $ast = $this->filter->getAst('and(1, 0)');
        $this->assertEquals( $expect, $ast, 'not valid and' );
    }

    public function testOrExpr()
    {
        $expect = new T\OrExpr(
            new T\IntVal( '1' ),
            new T\IntVal( '0' )
        );

        $ast = $this->filter->getAst('or(1, 0)');
        $this->assertEquals( $expect, $ast, 'not valid or' );
    }

    public function likeStartsApplyTest()
    {
        $ast = new T\LikeExpr(
            new T\FldVal( 'Model' ),
            new T\StrVal( '"Volks%"' )
        );

        $data = [
            [
                'Model' => 'Volkswagen Polo',
                "RegNumber" => "X 586 XK 677",
                "Vin" => "3GKEC16T51G226718",
            ],
            [
                'Model' => 'Mazda',
                "RegNumber" => "H 349 PP 338",
                "Vin" => "1N4AL11D35C393619",
            ],
            [
                'Model' => 'VOLVO',
                "RegNumber" => "A 546 XK 852",
                "Vin" => "1FTZR15V5YTB72526",
            ],
            [
                'Model' => 'Volkswagen Polo',
                "RegNumber" => "B 865 PC 732",
                "Vin" => "3VWSE69M32M154055",
            ],
        ];

        $expect = [
            [
                'Model' => 'Volkswagen Polo',
                "RegNumber" => "X 586 XK 677",
                "Vin" => "3GKEC16T51G226718",
            ],
            [
                'Model' => 'Volkswagen Polo',
                "RegNumber" => "B 865 PC 732",
                "Vin" => "3VWSE69M32M154055",
            ]
        ];

        $this->assertEquals($expect, $ast->apply($data), 'Not valid apply with str and fld' );
    }

    public function likeEndsApplyTest()
    {
        $ast = new T\LikeExpr(
            new T\FldVal( 'Vin' ),
            new T\StrVal( '"%516"' )
        );

        $data = [
            [
                'Model' => 'Volkswagen Polo',
                "RegNumber" => "X 586 XK 677",
                "Vin" => "3GKEC16T51G226718",
            ],
            [
                'Model' => 'Mazda',
                "RegNumber" => "H 349 PP 338",
                "Vin" => "1N4AL11D35C393516",
            ],
            [
                'Model' => 'VOLVO',
                "RegNumber" => "A 546 XK 852",
                "Vin" => "1FTZR15V5YTB72516",
            ],
            [
                'Model' => 'Volkswagen Polo',
                "RegNumber" => "B 865 PC 732",
                "Vin" => "3VWSE69M32M154055",
            ],
        ];

        $expect = [
            [
                'Model' => 'Mazda',
                "RegNumber" => "H 349 PP 338",
                "Vin" => "1N4AL11D35C393516",
            ],
            [
                'Model' => 'VOLVO',
                "RegNumber" => "A 546 XK 852",
                "Vin" => "1FTZR15V5YTB72516",
            ],
        ];

        $this->assertEquals($expect, $ast->apply($data), 'Not valid apply with str and fld' );
    }

    public function likeContainsApplyTest()
    {
        $ast = new T\LikeExpr(
            new T\FldVal( 'RegNumber' ),
            new T\StrVal( '"%XK%"' )
        );

        $data = [
            [
                'Model' => 'Volkswagen Polo',
                "RegNumber" => "X 586 XK 677",
                "Vin" => "3GKEC16T51G226718",
            ],
            [
                'Model' => 'Mazda',
                "RegNumber" => "H 349 PP 338",
                "Vin" => "1N4AL11D35C393516",
            ],
            [
                'Model' => 'VOLVO',
                "RegNumber" => "A 546 XK 852",
                "Vin" => "1FTZR15V5YTB72516",
            ],
            [
                'Model' => 'Volkswagen Polo',
                "RegNumber" => "B 865 PC 732",
                "Vin" => "3VWSE69M32M154055",
            ],
        ];

        $expect = [
            [
                'Model' => 'Volkswagen Polo',
                "RegNumber" => "X 586 XK 677",
                "Vin" => "3GKEC16T51G226718",
            ],
            [
                'Model' => 'VOLVO',
                "RegNumber" => "A 546 XK 852",
                "Vin" => "1FTZR15V5YTB72516",
            ],
        ];

        $this->assertEquals($expect, $ast->apply($data), 'Not valid apply with str and fld' );
    }
}
