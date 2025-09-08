<?php

/**
 * Set return type of \FP_CLI\Utils\parse_url().
 *
 * Based on ParseUrlFunctionDynamicReturnTypeExtension in PHPStan itself
 * and WpParseUrlFunctionDynamicReturnTypeExtension in the PHPStan FinPress extension.
 *
 * phpcs:disable FinPress.FP.AlternativeFunctions.parse_url_parse_url
 */

declare(strict_types=1);

namespace FP_CLI\Tests\PHPStan;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function parse_url;

use const PHP_URL_FRAGMENT;
use const PHP_URL_HOST;
use const PHP_URL_PASS;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_QUERY;
use const PHP_URL_SCHEME;
use const PHP_URL_USER;

final class ParseUrlFunctionDynamicReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension {

	/** @var array<int, \PHPStan\Type\Type>|null */
	private $componentTypesPairedConstants = null;

	/** @var array<string, \PHPStan\Type\Type>|null */
	private $componentTypesPairedStrings = null;

	/** @var \PHPStan\Type\Type|null */
	private $allComponentsTogetherType = null;

	public function isFunctionSupported( FunctionReflection $functionReflection ): bool {
		return $functionReflection->getName() === 'FP_CLI\Utils\parse_url';
	}

	public function getTypeFromFunctionCall( FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope ): ?Type {
		if ( count( $functionCall->getArgs() ) < 1 ) {
			return null;
		}

		$this->cacheReturnTypes();

		$componentType = new ConstantIntegerType( -1 );

		if ( count( $functionCall->getArgs() ) > 1 ) {
			$componentType = $scope->getType( $functionCall->getArgs()[1]->value );

			if ( ! $componentType->isConstantValue()->yes() ) {
				return $this->createAllComponentsReturnType();
			}

			$componentType = $componentType->toInteger();

			if ( ! $componentType instanceof ConstantIntegerType ) {
				return $this->createAllComponentsReturnType();
			}
		}

		$urlType = $scope->getType( $functionCall->getArgs()[0]->value );
		if ( count( $urlType->getConstantStrings() ) > 0 ) {
			$types = [];
			foreach ( $urlType->getConstantStrings() as $constantString ) {
				try {
                    // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
					$result = @parse_url( $constantString->getValue(), $componentType->getValue() );
				} catch ( \Error $e ) {
					$types[] = new ConstantBooleanType( false );
					continue;
				}

				$types[] = $scope->getTypeFromValue( $result );
			}

			return TypeCombinator::union( ...$types );
		}

		if ( $componentType->getValue() === -1 ) {
			return TypeCombinator::union( $this->createComponentsArray(), new ConstantBooleanType( false ) );
		}

		return $this->componentTypesPairedConstants[ $componentType->getValue() ] ?? new ConstantBooleanType( false );
	}

	private function createAllComponentsReturnType(): Type {
		if ( null === $this->allComponentsTogetherType ) {
			$returnTypes = [
				new ConstantBooleanType( false ),
				new NullType(),
				IntegerRangeType::fromInterval( 0, 65535 ),
				new StringType(),
				$this->createComponentsArray(),
			];

			$this->allComponentsTogetherType = TypeCombinator::union( ...$returnTypes );
		}

		return $this->allComponentsTogetherType;
	}

	private function createComponentsArray(): Type {
			$builder = ConstantArrayTypeBuilder::createEmpty();

		if ( null === $this->componentTypesPairedStrings ) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		foreach ( $this->componentTypesPairedStrings as $componentName => $componentValueType ) {
			$builder->setOffsetValueType( new ConstantStringType( $componentName ), $componentValueType, true );
		}

			return $builder->getArray();
	}

	private function cacheReturnTypes(): void {
		if ( null !== $this->componentTypesPairedConstants ) {
			return;
		}

		$stringType = new StringType();
		$port       = IntegerRangeType::fromInterval( 0, 65535 );
		$falseType  = new ConstantBooleanType( false );
		$nullType   = new NullType();

		$stringOrFalseOrNull = TypeCombinator::union( $stringType, $falseType, $nullType );
		$portOrFalseOrNull   = TypeCombinator::union( $port, $falseType, $nullType );

		$this->componentTypesPairedConstants = [
			PHP_URL_SCHEME   => $stringOrFalseOrNull,
			PHP_URL_HOST     => $stringOrFalseOrNull,
			PHP_URL_PORT     => $portOrFalseOrNull,
			PHP_URL_USER     => $stringOrFalseOrNull,
			PHP_URL_PASS     => $stringOrFalseOrNull,
			PHP_URL_PATH     => $stringOrFalseOrNull,
			PHP_URL_QUERY    => $stringOrFalseOrNull,
			PHP_URL_FRAGMENT => $stringOrFalseOrNull,
		];

		$this->componentTypesPairedStrings = [
			'scheme'   => $stringType,
			'host'     => $stringType,
			'port'     => $port,
			'user'     => $stringType,
			'pass'     => $stringType,
			'path'     => $stringType,
			'query'    => $stringType,
			'fragment' => $stringType,
		];
	}
}
