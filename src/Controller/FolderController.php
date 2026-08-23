<?php

namespace App\Controller;

use App\Entity\Authentication\User;
use App\Entity\File;
use App\Entity\Folder;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FolderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ){}

    #[Route('/api/folder/create', 'api_folder_create', methods: ['POST'])]
    public function create(
        Request $request
    ): Response
    {
        $data = json_decode($request->getContent(), true);
        $parent = null;
        if ($data['parent']) {
            $parent = $this->entityManager->getRepository(Folder::class)->findOneBy(['uuid' => $data['parent']]);
            if ($parent && $parent->getOwner() !== $this->getUser()) {
                throw $this->createNotFoundException();
            }
        }

        $folder = new Folder();
        $folder
            ->setUuid(Uuid::v4()->toRfc4122())
            ->setName($data['name'])
            ->setParent($parent)
            ->setOwner($this->getUser())
            ->setPinned(false)
        ;

        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        return $this->json($folder, Response::HTTP_CREATED);
    }

    #[Route('/api/folder/get', 'api_folder_get', methods: ['POST'])]
    public function get(
        Request $request,
    ): Response
    {
        $request_data = json_decode($request->getContent(), true);

        if ($request_data['parent'] === "favorites") {
            $childs = $this->entityManager->getRepository(Folder::class)->findBy(['favorite' => true]);
            $files = $this->entityManager->getRepository(File::class)->findBy(['favorite' => true]);
            $childs = array_merge($childs, $files);
        } else if ($request_data['parent'] === "shared") {
            $qb = $this->entityManager->getRepository(Folder::class)->createQueryBuilder('f');
            $qb->where('f.shared_uuid != null');
            $folders = $qb->getQuery()->getResult();

            $qb = $this->entityManager->getRepository(File::class)->createQueryBuilder('f');
            $qb->where('f.shared_uuid != null');
            $files = $qb->getQuery()->getResult();

            $childs = array_merge($folders, $files);
        } else {
            $root = $this->getUser();
            if ($request_data['parent']) {
                $root = $this->entityManager->getRepository(Folder::class)->findOneBy(['uuid' => $request_data['parent']]);
                if ($root && $root->getOwner() !== $this->getUser()) {
                    throw $this->createNotFoundException();
                }
            }

            $childs = $root->getFolders();
            $files = $root->getFiles();
            $childs = array_merge($childs->toArray(), $files->toArray());
        }
        $data = [];
        foreach ($childs as $child) {
            if ($request_data['parent'] === "favorites" ||
                ($root !== $this->getUser() || ($root === $this->getUser() && $child->getParent() === null))
            ) {
                if ($child instanceof Folder) {
                    $data[] = [
                        "type" => "folder",
                        "uuid" => $child->getUuid(),
                        "name" => $child->getName(),
                        "pinned" => $child->isPinned(),
                        "favorite" => $child->isFavorite(),
                    ];
                }
                if ($child instanceof File) {
                    $data[] = [
                        "type" => "file",
                        "uuid" => $child->getUuid(),
                        "name" => $child->getName(),
                        "favorite" => $child->isFavorite(),
                        "mimeType" => $child->getType(),
                    ];
                }

            }
        }
        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/folder/{uuid}', 'app_folder_uuid')]
    public function rootByUUID(
        string $uuid,
    ): Response
    {
        $folder = $this->entityManager->getRepository(Folder::class)->findOneBy(['uuid' => $uuid]);
        if (!$folder || $this->getUser() !== $folder->getOwner()) {
            throw $this->createNotFoundException();
        }

        return $this->render('Page/home.html.twig', [
            'root' => $folder->getUuid(),
            'path' => $this->getPath($folder),
            'pinned' => $this->entityManager->getRepository(Folder::class)->findBy(['pinned' => true]),
            'favorite' => $folder->isFavorite(),
            'storage' => FolderController::quota($this->getUser()),
            'user' => $this->getUser(),
        ]);
    }

    public function getPath(
        Folder $folder,
    ): array
    {
        $path = [];
        $current = $folder;
        while ($current) {
            $path[] = [
                "uuid" => $current->getUuid(),
                "name" => $current->getName(),
            ];
            $current = $current->getParent();
        }

        return array_reverse($path);
    }

    #[Route('/api/folder/search', 'api_folder_search', methods: ['POST'])]
    public function search(
        Request $request,
    ): Response
    {
        $request_data = json_decode($request->getContent(), true);

        $root = $this->getUser();
        if ($request_data['parent']) {
            $root = $this->entityManager->getRepository(Folder::class)->findOneBy(['uuid' => $request_data['parent']]);
            if ($root && $root->getOwner() !== $this->getUser()) {
                throw $this->createNotFoundException();
            }
        }

        $qb = $this->entityManager->getRepository(Folder::class)->createQueryBuilder('f');
        $qb
            ->where('LOWER(f.name) LIKE :search')
            ->setParameter('search', '%' . $request_data['search'] . '%')
        ;
        $folders = $qb->getQuery()->getResult();

        $qb = $this->entityManager->getRepository(File::class)->createQueryBuilder('f');
        $qb
            ->where('LOWER(f.name) LIKE :search')
            ->setParameter('search', '%' . $request_data['search'] . '%')
        ;
        $files = $qb->getQuery()->getResult();

        $data = [];
        $merged = array_merge($folders, $files);
        foreach ($merged as $item) {
            if ($root !== $this->getUser() || ($root === $this->getUser() && $item->getParent() === null)) {
                if ($item instanceof Folder) {
                    $data[] = [
                        "type" => "folder",
                        "uuid" => $item->getUuid(),
                        "name" => $item->getName(),
                        "pinned" => $item->isPinned(),
                        "favorite" => $item->isFavorite(),
                    ];
                }
                if ($item instanceof File) {
                    $data[] = [
                        "type" => "file",
                        "uuid" => $item->getUuid(),
                        "name" => $item->getName(),
                        "favorite" => $item->isFavorite(),
                        "mimeType" => $item->getType(),
                    ];
                }
            }
        }
        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/api/folder/delete', 'api_folder_delete', methods: ['POST'])]
    public function delete(
        Request $request,
    ): Response
    {
        $request_data = json_decode($request->getContent(), true);

        $folder = $this->entityManager->getRepository(Folder::class)->findOneBy(['uuid' => $request_data['uuid']]);
        if (!$folder || $this->getUser() !== $folder->getOwner()) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($folder);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/folder/rename', 'api_folder_rename', methods: ['POST'])]
    public function rename(
        Request $request,
    ): Response
    {
        $request_data = json_decode($request->getContent(), true);

        $folder = $this->entityManager->getRepository(Folder::class)->findOneBy(['uuid' => $request_data['uuid']]);
        if (!$folder || $this->getUser() !== $folder->getOwner()) {
            throw $this->createNotFoundException();
        }

        $folder->setName($request_data['name']);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/folder/pin', 'api_folder_pin', methods: ['POST'])]
    public function setPin(
        Request $request,
    ): Response
    {
        $request_data = json_decode($request->getContent(), true);

        $folder = $this->entityManager->getRepository(Folder::class)->findOneBy(['uuid' => $request_data['uuid']]);
        if (!$folder || $this->getUser() !== $folder->getOwner()) {
            throw $this->createNotFoundException();
        }

        $folder->setPinned($request_data['pinned']);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/item/favorite', 'api_item_favorite', methods: ['POST'])]
    public function setFavorite(
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

        $item->setFavorite($request_data['favorite']);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/favorites', 'api_favorites')]
    public function favorites(): Response
    {
        return $this->render('Page/home.html.twig', [
            'root' => "favorites",
            'pinned' => $this->entityManager->getRepository(Folder::class)->findBy(['pinned' => true]),
            'storage' => FolderController::quota($this->getUser()),
            'user' => $this->getUser(),
        ]);
    }

    static function bytesToHuman(int $bytes): array
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1000 && $i < count($units) - 1) {
            $bytes /= 1000;
            $i++;
        }

        return [
            'value' => round($bytes, 2),
            'unit' => $units[$i]
        ];
    }

    static public function quota(
        User $user,
    ): array
    {
        $used = array_sum(
            array_map(fn($f) => $f->getSize(), $user->getFiles()->toArray())
        );

        $current = FolderController::bytesToHuman($used);
        $max = FolderController::bytesToHuman($user->getMaxStorage());

        return [
            'currentValue' => $current['value'],
            'currentUnit' => $current['unit'],
            'maxValue' => $max['value'],
            'maxUnit' => $max['unit'],
            'currentValueRaw' => $used,
            'maxValueRaw' => $user->getMaxStorage(),
        ];
    }
}
