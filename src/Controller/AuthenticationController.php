<?php

namespace App\Controller;

use App\Entity\Authentication\User;
use App\Form\Authentication\LoginType;
use App\Form\Authentication\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            ;

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('Page/Authentication/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
