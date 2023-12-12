<?php

namespace Compwright\OAuth2_Quickbooks_Online;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\HttpFactory;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class QuickbooksOnlineTest extends TestCase
{
    use QueryBuilderTrait;

    protected QuickbooksProvider $provider;

    protected HttpFactory $httpFactory;

    protected function setUp(): void
    {
        $this->provider = new QuickbooksProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);

        $this->httpFactory = new HttpFactory();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $scopeSeparator = ',';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);
        $this->assertStringContainsString($encodedScope, $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/connect/oauth2', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth2/v1/tokens/bearer', $uri['path']);
    }

    public function testGetAccessToken()
    {
        /** @var MockObject&ResponseInterface */
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getBody')->willReturn(
            $this->httpFactory->createStream('{"access_token": "mock_access_token","expires_in": 3600,"restricted_to": [],"token_type": "bearer","refresh_token": "mock_refresh_token"}')
        );
        $response->method('getHeader')->willReturn(['content-type' => 'json']);

        /** @var MockObject&ClientInterface */
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())->method('send')->willReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $companyId = rand(1000,9999);
        $companyName = uniqid();

        /** @var MockObject&ResponseInterface $postResponse */
        $postResponse = $this->createStub(ResponseInterface::class);
        $postResponse->method('getBody')->willReturn(
            $this->httpFactory->createStream('{"access_token": "mock_access_token","expires_in": 3600,"restricted_to": [],"token_type": "bearer","refresh_token": "mock_refresh_token"}')
        );
        $postResponse->method('getHeader')->willReturn(['content-type' => 'json']);

        /** @var MockObject&ResponseInterface $userResponse */
        $userResponse = $this->createStub(ResponseInterface::class);
        $userResponse->method('getBody')->willReturn(
            $this->httpFactory->createStream(json_encode([
                'CompanyInfo' => [
                    'Id' => $companyId,
                    'CompanyName' => $companyName,
                ]
            ]))
        );
        $userResponse->method('getHeader')->willReturn(['content-type' => 'json']);

        /** @var MockObject&ClientInterface */
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code', 'realmId' => $companyId]);
        $owner = $this->provider->getResourceOwner($token);

        $this->assertEquals($companyId, $owner->getId());
        $this->assertEquals($companyName, $owner->getName());
        $this->assertEquals([
            'CompanyInfo' => [
                'Id' => $companyId,
                'CompanyName' => $companyName,
            ]
        ], $owner->toArray());
    }

    public function testOauth2Error()
    {
        $this->expectException(IdentityProviderException::class);

        /** @var MockObject&ResponseInterface */
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getBody')->willReturn(
            $this->httpFactory->createStream('{"errors":"invalid_client","error_description":"Invalid client`s id or secret."}')
        );
        $response->method('getHeader')->willReturn(['content-type' => 'json']);
        $response->method('getStatusCode')->willReturn(400);

        /** @var MockObject&ClientInterface */
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('send')
            ->willReturn($response);
        $this->provider->setHttpClient($client);

        $this->provider->getAccessToken('refresh_token', ['refresh_token' => 'mock_refresh_token']);
    }
}
