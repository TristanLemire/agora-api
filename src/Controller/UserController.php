<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\LevelRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     description="Get users",
     *     @OA\Response(
     *         response="200",
     *         description="List of all users",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User")),
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="No users found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", description="HTTP Code status"),
     *             @OA\Property(property="message", type="string", description="Returned message"),
     *         ),
     *     ),
     * )
     * @Route("/api/users", name="api_get_users", methods={"GET"})
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function index(UserRepository $userRepository): JsonResponse
    {
        $result = $userRepository->findAll();

        if (!$result) {
            return $this->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'No users found',
            ]);
        }

        return $this->json($result, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /**
     * @OA\Get(
     *     path="/api/user/{id}",
     *     description="Get user",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Id of User",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="List of all users",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User")),
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="User does not exist",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", description="HTTP Code status"),
     *             @OA\Property(property="message", type="string", description="Returned message"),
     *         ),
     *     ),
     * )
     * @Route("/api/user/{id}", name="api_get_user", methods={"GET"})
     * @param UserRepository $userRepository
     * @param int $id
     * @return JsonResponse
     */
    public function oneUser(UserRepository $userRepository, int $id): JsonResponse
    {
        $result = $userRepository->findOneUser($id);

        if (!$result) {
            return $this->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'This user does not exist',
            ]);
        }

        return $this->json($result, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /**
     * @OA\Post(
     *     path="/api/signup",
     *     description="Create user",
     *     @OA\Response(
     *         response="200",
     *         description="User created",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User")),
     *     ),
     * )
     * @Route("/api/signup", name="api_signup_user", methods={"POST"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param LevelRepository $levelRepository
     * @return JsonResponse
     */
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator, LevelRepository $levelRepository): JsonResponse
    {
        $json = $request->getContent();

        $level = $levelRepository->findOneBy(['levelNumber' => 0]);

        try {
            $user = $serializer->deserialize($json, User::class, 'json');
            $user
                ->setPassword(password_hash($user->getPassword(), 'argon2id'))
                ->setRegistrationDate(new DateTime())
                ->setLevel($level)
                ->setSavingWater(0)
                ->setSavingWaste(0)
                ->setSavingElectricity(0)
                ->setSavingGas(0)
            ;

            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $em->persist($user);
            $em->flush();

            return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user:create']);

        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
