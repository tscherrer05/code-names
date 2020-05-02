<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html h1', 'Code games');
    }

    public function testLogin()
    {
        $client = static::createClient();

        $client->request('GET', '/login?game=1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('input#login');
        $this->assertInputValueSame('gameId', '1', 'Wrong input value');
    }

    public function testPostLogin()
    {
        $client = static::createClient();

        $client->request(
            'POST', 
            '/login',
            [
                "gameId" => 1,
                "login" => "ChuckNorris78"
            ]
        );

        $this->assertTrue($client->getResponse()->isSuccessful(), 'response status is 2xx');
    }
}