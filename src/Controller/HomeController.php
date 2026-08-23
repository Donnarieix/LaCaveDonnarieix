<?php

namespace App\Controller;

use App\Entity\Folder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ){}

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/home', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('Page/home.html.twig', [
            'root' => null,
            'pinned' => $this->entityManager->getRepository(Folder::class)->findBy(['pinned' => true]),
            'storage' => FolderController::quota($this->getUser()),
            'user' => $this->getUser(),
        ]);
    }
}
