<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Folder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class SharedController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ){}

    #[Route('/api/item/share', 'api_item_share', methods: ['POST'])]
    public function share(
        Request $request,
    ): Response
    {
        $request_data = json_decode($request->getContent(), true);

        $classname = null;
        if ($request_data['itemType'] === 'folder') {
            $classname = Folder::class;
        } else if ($request_data['itemType'] === 'file') {
            $classname = File::class;
        }

        if ($classname === null) {
            return $this->json(null, Response::HTTP_BAD_REQUEST);
        }
        $item = $this->entityManager->getRepository($classname)->findOneBy(['uuid' => $request_data['uuid']]);
        if (!$item || $this->getUser() !== $item->getOwner()) {
            throw $this->createNotFoundException();
        }

        $item->setSharedUuid(Uuid::v4());
        if ($request_data['type'] === 'folder') {
            $item->setSharedRecursive($request_data['recursive']);
        }
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/item/shared', 'app_item_shared')]
    public function shared(): Response {
        $qb = $this->entityManager->getRepository(Folder::class)->createQueryBuilder('f');
        $qb->where('f.shared_uuid != null');
        $folders = $qb->getQuery()->getResult();

        $qb = $this->entityManager->getRepository(File::class)->createQueryBuilder('f');
        $qb->where('f.shared_uuid != null');
        $files = $qb->getQuery()->getResult();

        $shared = array_merge($folders, $files);
        return $this->render('Page/home.html.twig', [
            'parent' => 'shared',
            'pinned' => $this->entityManager->getRepository(Folder::class)->findBy(['pinned' => true]),
            'storage' => FolderController::quota($this->getUser()),
            'username' => $this->getUser()->getUsername(),
        ]);
    }

    #[Route('/shared/{uuid}', 'app_folder_shared_uuid')]
    public function sharedFolder(
        string $uuid
    ): Response
    {
        $folder = $this->entityManager->getRepository(Folder::class)->findOneBy(['uuid' => $uuid]);
        if (!$folder || $this->getUser() !== $folder->getOwner()) {
            throw $this->createNotFoundException();
        }

        // Vérification des droits (recursive)
        $current = $folder;
        while ($current && $current->getSharedUuid() === null) {
            $current = $folder->getParent();
        }
        if ($current === null || !$current->isSharedRecursive()) {
            throw $this->createNotFoundException();
        }

        return $this->render('Page/shared.html.twig', [

        ]);
    }
}
