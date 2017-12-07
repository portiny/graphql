<?php

namespace Portiny\GraphQL\Tests\Adapter\Nette;

use Portiny\GraphQL\GraphQLProcessor;
use Portiny\GraphQL\Tests\AbstractContainerTestCase;


final class GraphQLExtensionTest extends AbstractContainerTestCase
{

	public function testLoadConfiguration()
	{
		$graphQLProcessor = $this->container->getByType(GraphQLProcessor::class);
		$this->assertInstanceOf(GraphQLProcessor::class, $graphQLProcessor);
	}

}
