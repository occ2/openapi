<?php

namespace Apitte\OpenApi;

use Apitte\Core\Schema\Endpoint;
use Apitte\Core\Schema\Schema as ApiSchema;
use Apitte\OpenApi\Schema\Info;
use Apitte\OpenApi\Schema\MediaType;
use Apitte\OpenApi\Schema\OpenApi;
use Apitte\OpenApi\Schema\Operation;
use Apitte\OpenApi\Schema\Parameter;
use Apitte\OpenApi\Schema\PathItem;
use Apitte\OpenApi\Schema\Paths;
use Apitte\OpenApi\Schema\RequestBody;
use Apitte\OpenApi\Schema\Response;
use Apitte\OpenApi\Schema\Responses;
use Apitte\OpenApi\Schema\Schema;
use Apitte\OpenApi\Schema\Tag;

class OpenApiService
{

	/** @var ApiSchema */
	protected $schema;

	/**
	 * @param ApiSchema $schema
	 */
	public function __construct(ApiSchema $schema)
	{
		$this->schema = $schema;
	}

	/**
	 * @return OpenApi
	 */
	public function createSchema()
	{
		$info = new Info('Api Docs', '2.0.5-beta');
		$paths = new Paths();
		$openApi = new OpenApi($info, $paths);

		$endpointId = 0;
		foreach ($this->getEndpoints() as $endpoint) {
			$endpointId++;

			$pathItem = new PathItem();
			foreach ($endpoint->getMethods() as $method) {

				//Request MediaType
				$mediaType = new MediaType();
				$body = new Schema([
					'type' => 'object',
					'required' => [
						'name',
					],
					'properties' => (object) [
						'name' => (object) [
							'type' => 'string',
						],
						'age' => (object) [
							'type' => 'integer',
							'format' => 'int32',
							'minimum' => 0,
						],
					],
				]);
				$mediaType->setSchema($body);

				//Request body
				$requestBody = new RequestBody(['application/json' => $mediaType]);
				$requestBody->setDescription('Request body description');
				$requestBody->setRequired(TRUE);

				//Reference
				//$schemaReference = new ComponentReference(
				//	ComponentReference::TYPE_SCHEMA,
				//	'sample'
				//);

				//Response MediaType
				$mediaType = new MediaType();
				$mediaType->setSchema($body);

				//Default response - required
				$defaultResponse = new Response('Response description');
				$defaultResponse->setContent('application/json', $mediaType);

				//Responses
				$responses = new Responses($defaultResponse);

				//Create unique operation id
				$operationId = $endpointId . $method;

				//Operation
				$operation = new Operation($operationId, $responses);
				$operation->setTags($this->getOperationTags($endpoint));

				$operation->setDescription('Long description');
				$operation->setSummary('Short description');
				$operation->setDeprecated(FALSE);

				//Parameters
				foreach ($endpoint->getParameters() as $endpointParam) {
					$param = new Parameter($endpointParam->getName(), Parameter::IN_QUERY);
					$param->setDescription($endpointParam->getDescription());
					//$param->setRequired(TRUE); //TODO
					//$param->setAllowEmptyValue(FALSE); //TODO
					//$param->setDeprecated(FALSE); //TODO
					$param->setSchema(new Schema([
						'type' => 'integer',
						'format' => 'int32',
					]));
					$operation->setParameter($param);
				}
				//$operation->setRequestBody($requestBody);
				$pathItem->setOperation(strtolower($method), $operation);
			}
			$paths->setPathItem($endpoint->getMask(), $pathItem);
		}

		//Global tags
		foreach ($this->getTags() as $tag) {
			$openApi->addTag($tag);
		}

		//Components
		//$components = new Components();
		//$components->setSchema('sample', $schema);
		//$openApi->setComponents($components);

		return $openApi;
	}

	/**
	 * @return Endpoint[]
	 */
	protected function getEndpoints()
	{
		return $this->schema->getEndpoints();
	}

	/**
	 * @param Endpoint $endpoint
	 * @return string[]
	 */
	protected function getOperationTags(Endpoint $endpoint)
	{
		$tags = $endpoint->getTags();
		unset($tags['group.ids']);
		unset($tags['group.paths']);
		unset($tags['id']);
		return array_keys($tags);
	}

	/**
	 * @return Tag[]
	 */
	protected function getTags()
	{
		return [];
	}

}
