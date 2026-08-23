<?php

namespace App\Controller;

use App\Entity\Authentication\User;
use App\Entity\Folder;
use App\Form\Authentication\LoginType;
use App\Form\Authentication\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthenticationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ){}

    #[Route('/login', name: 'app_login')]
    public function login(
        Request $request,
    ): Response
    {
        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        return $this->render('Page/Authentication/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
    ): Response
    {
        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();

            $username = $form->get('username')->getData();
            $email = $form->get('email')->getData();
            $password = $form->get('password')->getData();

            $user
                ->setUsername($username)
                ->setEmail($email)
                ->setPassword($this->passwordHasher->hashPassword($user, $password))
                ->setMaxStorage(1_1000_1000_1000) // 1GB
            ;

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('Page/Authentication/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile', 'app_profile')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('Page/Authentication/profile.html.twig', [
            'pinned' => $this->entityManager->getRepository(Folder::class)->findBy(['pinned' => true]),
            'storage' => FolderController::quota($this->getUser()),
            'user' => $user,
        ]);
    }

    #[Route('/api/profile/set', 'api_profile_set', methods: ['POST'])]
    public function setProfile(
        Request $request,
    ): Response
    {
        if (!$this->getUser() instanceof User) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['username']) || empty($data['email'])) {
            return new JsonResponse(['message' => 'Username and email address are both required'], Response::HTTP_BAD_REQUEST);
        }

        $this->getUser()
            ->setUsername($data['username'])
            ->setEmail($data['email'])
            ->setFirstName($data['firstname'])
            ->setLastName($data['lastname'])
            ->setVisibility($data['visibility'])
        ;
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Profile updated'], Response::HTTP_OK);
    }

    #[Route('/api/profile/password/set', 'api_profile_password_set', methods: ['POST'])]
    public function setPassword(
        Request $request,
    ): Response
    {
        if (!$this->getUser() instanceof User) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['password']) || empty($data['confirm'])) {
            return new JsonResponse(['message' => 'Password and confirm are both required'], Response::HTTP_BAD_REQUEST);
        }
        if ($data['password'] !== $data['confirm']) {
            return new JsonResponse(['message' => 'Passwords do not match'], Response::HTTP_BAD_REQUEST);
        }

        $this->getUser()->setPassword($this->passwordHasher->hashPassword($this->getUser(), $data['password']));
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Password updated'], Response::HTTP_OK);
    }
}
