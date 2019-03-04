<?php

declare(strict_types=1);

namespace Portiny\GraphQL\GraphQL;

use GraphQL\Error\Debug;
use GraphQL\Error\FormattedError;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use Portiny\GraphQL\Contract\Http\Request\RequestParserInterface;
use Portiny\GraphQL\Contract\Provider\MutationFieldsProviderInterface;
use Portiny\GraphQL\Contract\Provider\QueryFieldsProviderInterface;
use Portiny\GraphQL\GraphQL\Schema\SchemaCacheProvider;
use Throwable;
use Tracy\ILogger;

final class RequestProcessor
{
	/**
	 * @var bool
	 */
	private $debugMode = false;

	/**
	 * @var bool
	 */
	private $schemaCache = false;

	/**
	 * @var MutationFieldsProviderInterface
	 */
	private $mutationFieldsProvider;

	/**
	 * @var QueryFieldsProviderInterface
	 */
	private $queryFieldsProvider;

	/**
	 * @var SchemaCacheProvider
	 */
	private $schemaCacheProvider;

	public function __construct(
		bool $debugMode,
		MutationFieldsProviderInterface $mutationFieldsProvider,
		QueryFieldsProviderInterface $queryFieldsProvider,
		SchemaCacheProvider $schemaCacheProvider
	) {
		$this->debugMode = $debugMode;
		$this->mutationFieldsProvider = $mutationFieldsProvider;
		$this->queryFieldsProvider = $queryFieldsProvider;
		$this->schemaCacheProvider = $schemaCacheProvider;
	}

	public function setSchemaCache(bool $useSchemaCache): void
	{
		$this->schemaCache = $useSchemaCache;
	}

	/**
	 * @param mixed|null $context
	 * @param array|null $allowedQueries
	 * @param array|null $allowedMutations
	 */
	public function process(
		RequestParserInterface $requestParser,
		array $rootValue = [],
		$context = null,
		?array $allowedQueries = null,
		?array $allowedMutations = null,
		?ILogger $logger = null
	): array {
		try {
			if ($this->schemaCache && $this->schemaCacheProvider->isCached()) {
				$schema = $this->schemaCacheProvider->getSchema();
			} else {
				$schema = $this->createSchema($allowedQueries, $allowedMutations);
				if ($this->schemaCache) {
					$this->schemaCacheProvider->save($schema);
				}
			}

			$result = GraphQL::executeQuery(
				$schema,
				$requestParser->getQuery(),
				$rootValue,
				$context,
				$requestParser->getVariables()
			);

			$output = $result->toArray($this->detectDebugLevel($logger));
		} catch (Throwable $throwable) {
			if ($logger) {
				$logger->log($throwable, ILogger::EXCEPTION);
			}

			$output = [
				'errors' => [FormattedError::createFromException($throwable, false, 'An error occurred.')],
			];
		}

		return $output;
	}

	/**
	 * @param mixed|null $context
	 * @param array|null $allowedQueries
	 * @param array|null $allowedMutations
	 */
	public function processViaPromise(
		PromiseAdapter $promiseAdapter,
		RequestParserInterface $requestParser,
		array $rootValue = [],
		$context = null,
		?array $allowedQueries = null,
		?array $allowedMutations = null,
		?ILogger $logger = null
	): Promise {
		try {
			return GraphQL::promiseToExecute(
				$promiseAdapter,
				$this->createSchema($allowedQueries, $allowedMutations),
				$requestParser->getQuery(),
				$rootValue,
				$context,
				$requestParser->getVariables()
			);
		} catch (Throwable $throwable) {
			if ($logger) {
				$logger->log($throwable);
			}

			return $promiseAdapter->createRejected($throwable);
		}
	}

	private function createSchema(?array $allowedQueries = null, ?array $allowedMutations = null): Schema
	{
		$configuration = [
			'query' => $this->createQueryObject($allowedQueries),
		];

		$mutationObject = $this->createMutationObject($allowedMutations);
		if ($mutationObject->getFields()) {
			$configuration['mutation'] = $mutationObject;
		}

		return new Schema($configuration);
	}

	private function detectDebugLevel(?ILogger $logger): int
	{
		return $this->debugMode
			? Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE
			: ($logger === null ? 0 : Debug::RETHROW_INTERNAL_EXCEPTIONS);
	}

	private function createQueryObject(?array $allowedQueries = null): ObjectType
	{
		return new ObjectType([
			'name' => 'Query',
			'fields' => $this->queryFieldsProvider->convertFieldsToArray($allowedQueries),
		]);
	}

	private function createMutationObject(?array $allowedMutations = null): ObjectType
	{
		return new ObjectType([
			'name' => 'Mutation',
			'fields' => $this->mutationFieldsProvider->convertFieldsToArray($allowedMutations),
		]);
	}
}
