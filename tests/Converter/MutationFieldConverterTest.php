<?php

namespace Portiny\GraphQL\Tests\Converter;

use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use Portiny\GraphQL\Contract\Mutation\MutationFieldInterface;
use Portiny\GraphQL\Converter\MutationFieldConverter;
use Portiny\GraphQL\Tests\AbstractContainerTestCase;


final class MutationFieldConverterTest extends AbstractContainerTestCase
{

	public function testToArray()
	{
		$mutationField = $this->getMutationField();
		$output = MutationFieldConverter::toArray($mutationField);

		$this->assertSame('Some name', key($output));

		$mutationFieldAsArray = reset($output);
		$this->assertInstanceOf(StringType::class, $mutationFieldAsArray['type']);
		$this->assertSame('Some description', $mutationFieldAsArray['description']);
		$this->assertArrayHasKey('someArg', $mutationFieldAsArray['args']);
		$this->assertArrayHasKey('type', $mutationFieldAsArray['args']['someArg']);
		$this->assertInstanceOf(StringType::class, $mutationFieldAsArray['args']['someArg']['type']);
		$this->assertTrue(is_callable($mutationFieldAsArray['resolve']));
	}


	private function getMutationField(): MutationFieldInterface
	{
		return (new class () implements MutationFieldInterface
		{

			/**
			 * {@inheritdoc}
			 */
			public function getName(): string
			{
				return 'Some name';
			}


			/**
			 * {@inheritdoc}
			 */
			public function getType(): Type
			{
				return Type::string();
			}


			/**
			 * {@inheritdoc}
			 */
			public function getDescription(): string
			{
				return 'Some description';
			}


			/**
			 * {@inheritdoc}
			 */
			public function getArgs(): array
			{
				return [
					'someArg' => ['type' => Type::string()]
				];
			}


			/**
			 * {@inheritdoc}
			 */
			public function resolve(array $root, array $args, $context = NULL)
			{
				return 'resolved';
			}

		});
	}

}
