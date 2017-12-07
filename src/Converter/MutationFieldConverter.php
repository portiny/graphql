<?php

declare(strict_types = 1);

namespace Portiny\GraphQL\Converter;

use Portiny\GraphQL\Contract\Mutation\MutationFieldInterface;


class MutationFieldConverter
{

	public static function toArray(MutationFieldInterface $mutationField): array
	{
		return [
			$mutationField->getName() => [
				'type' => $mutationField->getType(),
				'description' => $mutationField->getDescription(),
				'args' => $mutationField->getArgs(),
				'resolve' => function ($root, $args, $context) use ($mutationField) {
					return call_user_func_array([$mutationField, 'resolve'], [$root, $args, $context]);
				}
			]
		];
	}


	public static function toObject(array $data): MutationFieldInterface
	{
		// TODO
	}

}
