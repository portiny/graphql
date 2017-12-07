<?php

declare(strict_types = 1);

namespace Portiny\GraphQL\Converter;

use Portiny\GraphQL\Contract\Field\QueryFieldInterface;


final class QueryFieldConverter
{

	public static function toArray(QueryFieldInterface $queryField): array
	{
		return [
			$queryField->getName() => [
				'type' => $queryField->getType(),
				'description' => $queryField->getDescription(),
				'args' => $queryField->getArgs(),
				'resolve' => function ($root, $args, $context) use ($queryField) {
					return call_user_func_array([$queryField, 'resolve'], [$root, $args, $context]);
				}
			]
		];
	}


	public static function toObject(array $data): QueryFieldInterface
	{
		// TODO
	}

}
