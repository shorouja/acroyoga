<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationControllerTest extends WebTestCase
{
    private function post(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $client = static::getClient();
        $client->request(
            'POST',
            '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        return $client->getResponse();
    }

    protected function setUp(): void
    {
        self::createClient();
        // Clean the user table so each test starts from a known state.
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testRegistersNewUserTokenless(): void
    {
        // No Authorization header is ever set — proves the firewall/access_control path.
        $response = $this->post([
            'email' => 'new@example.com',
            'password' => 'password123',
            'displayName' => 'New User',
        ]);

        self::assertSame(201, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        // Response contains exactly these keys — no password, no roles.
        self::assertSame(['id', 'email', 'displayName'], array_keys($body));
        self::assertSame('new@example.com', $body['email']);
        self::assertSame('New User', $body['displayName']);
        self::assertIsInt($body['id']);

        // The persisted password is hashed, not the plaintext.
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'new@example.com']);
        self::assertInstanceOf(User::class, $user);
        self::assertNotSame('password123', $user->getPassword());
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user, 'password123'));
    }

    public function testRejectsDuplicateEmail(): void
    {
        $payload = ['email' => 'dup@example.com', 'password' => 'password123', 'displayName' => 'First'];
        self::assertSame(201, $this->post($payload)->getStatusCode());

        $response = $this->post($payload);
        self::assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('email', $body['errors']);
    }

    public function testRejectsShortPassword(): void
    {
        // Guards the "password validated on the hash" silent-failure risk.
        $response = $this->post([
            'email' => 'short@example.com',
            'password' => 'x',
            'displayName' => 'Short Pw',
        ]);
        self::assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('password', $body['errors']);
    }

    public function testRejectsInvalidEmail(): void
    {
        $response = $this->post([
            'email' => 'not-an-email',
            'password' => 'password123',
            'displayName' => 'Bad Email',
        ]);
        self::assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('email', $body['errors']);
    }

    public function testRejectsBlankDisplayName(): void
    {
        $response = $this->post([
            'email' => 'noname@example.com',
            'password' => 'password123',
            'displayName' => '',
        ]);
        self::assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('displayName', $body['errors']);
    }

    public function testRejectsNonScalarField(): void
    {
        $client = static::getClient();
        $client->request(
            'POST',
            '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => ['x' => 1],
                'password' => 'password123',
                'displayName' => 'Bad Type',
            ])
        );
        $response = $client->getResponse();
        self::assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('email', $body['errors']);
    }
}
