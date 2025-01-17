<?php

namespace App\Tests\Controller;

use App\Entity\Mesure;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{

    private $em;

    private $userRepository;

    private $mesureRepository;

    /**
     * Create a client with a default Authorization header.
     *
     * @param string $username
     * @param string $password
     *
     * @return KernelBrowser
     */
    protected function createAuthenticatedClient($username = 'aymeric.mayeux@hetic.net', $password = 'azerty'): KernelBrowser
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => $password,
            ])
        );

        $data = json_decode($client->getResponse()->getContent(), true);

        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $client;
    }

    public function testUserWithBadCredentials(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'hehe',
                'password' => 'hoho',
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSignUpWithRequiredInformations(): void
    {
        $kernel = self::bootKernel();

        $this->userRepository = $kernel->getContainer()
            ->get('doctrine')
            ->getRepository(User::class)
        ;
        $user = $this->userRepository->findOneBy(['email' => 'john.doe2@doe.com']);

        if ($user) {
            $this->mesureRepository = $kernel->getContainer()
                ->get('doctrine')
                ->getRepository(Mesure::class)
            ;
            $mesure = $this->mesureRepository->findOneBy(['toMesure' => $user->getId()]);

            $this->em = $kernel->getContainer()->get('doctrine')->getManager();
            $this->em->remove($mesure);
            $this->em->remove($user);
            $this->em->flush();
        }

        /* Shutdown the previous kernel to ensure that we can create a client. */
        self::ensureKernelShutdown();

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/signup',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'password' => password_hash('test', 'argon2id'),
                'email' => 'john.doe2@doe.com',
                'nbResident' => 5,
                'livingArea' => 50.0,
                'gas' => true,
                'insulation' => false,
                'agoraNumber' => '12345678',
                'nifNumber' => '1123456789013',
                'navigoNumber' => '01234567',
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testUsersPageIsAuth(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/admin/users');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testUsersPageIsRestricted(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/admin/users');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}