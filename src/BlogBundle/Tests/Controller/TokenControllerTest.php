<?php

namespace BlogBundle\Tests\Controller;

use BlogBundle\Tests\CustomWebTestCase as WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TokenControllerTest extends WebTestCase
{
    /**
     * Set up the database and fixtures for tests.
     */
    public function setUp()
    {
        self::runCommand('doctrine:database:create');
        self::runCommand('doctrine:schema:create');
        self::runCommand('doctrine:fixtures:load --purge-with-truncate --no-interaction');

        $this->fixtures = $this->loadFixtures([
            'BlogBundle\DataFixtures\ORM\LoadUserData',
        ])->getReferenceRepository();
    }

    /**
     * Test POST HTTP method.
     */
    public function testJsonPost()
    {
        $this->client = static::createClient();
        $route = $this->getUrl('api_post_tokens', ['_format' => 'json']);
		$user = $this->fixtures->getReference('user');

        $this->client->request(
            'POST',
            $route,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"username":"test","password":"test"}'
        );

		$this->assertTrue(isset($user));
		$this->assertTrue($user->getId() !== null);

        $response = $this->client->getResponse();
        $this->assertStatusCode(Response::HTTP_NO_CONTENT, $this->client);
        $this->assertTrue(
            $response->headers->has('Location')
        );
        $this->assertTrue(
            $response->headers->has('X-Auth-Token')
        );
		$this->assertEquals(
			$response->headers->get('X-Auth-Token'),
			$user->getApiKey()
		);
    }

    /**
     * Test POST HTTP method with bad credentials.
     */
    public function testJsonPostBadCredentials()
    {
        $this->client = static::createClient();
        $route = $this->getUrl('api_post_tokens', ['_format' => 'json']);

        $this->client->request(
            'POST',
            $route,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"username":"test","password":"bar"}'
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponse($response, Response::HTTP_UNAUTHORIZED);

		$this->client->request(
            'POST',
            $route,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"username":"foo","password":"bar"}'
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponse($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test POST HTTP method with bad parameters.
     */
    public function testJsonPostBadParameters()
    {
        $this->client = static::createClient();
        $route = $this->getUrl('api_post_tokens', ['_format' => 'json']);

        $this->client->request(
            'POST',
            $route,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"name":"foo","pass":"bar","foo":"bar"}'
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponse($response, Response::HTTP_UNAUTHORIZED);
    }
}
