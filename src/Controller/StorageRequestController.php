<?php

namespace App\Controller;

use App\Entity\StorageRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class StorageRequestController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ){}

    #[Route('/api/request/create', name: 'api_request_create', methods: ['POST'])]
    public function create(
        Request $request,
    ): Response
    {
        $data = json_decode($request->getContent(), true);

        $requestedValue = $data['value'] ?? 1;
        $requestedUnit = $data['unit'] ?? 'GB';

        $requests = $this->entityManager->getRepository(StorageRequest::class)->findBy(['applicant' => $this->getUser()]);
        if ($requests !== []) {
            return new JsonResponse(['message' => 'Request already exists.'], Response::HTTP_BAD_REQUEST);
        }

        $storageRequest = (new StorageRequest())
            ->setApplicant($this->getUser())
            ->setRequestedValue($requestedValue)
            ->setRequestedUnit($requestedUnit)
        ;
        $this->entityManager->persist($storageRequest);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Storage request created.'], Response::HTTP_CREATED);
    }

    #[Route('/api/request/get', 'api_request_get', methods: ['POST'])]
    public function get(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $requests = $this->entityManager->getRepository(Request::class)->findAll();
        $data = [];
        /* @var $request StorageRequest */
        foreach ($requests as $request) {
            $data[] = [
                'email' => $request->getApplicant()->getEmail(),
                'value' => $request->getRequestedValue(),
                'unit' => $request->getRequestedUnit(),
            ];
        }
        return new JsonResponse($data, Response::HTTP_OK);
    }
}
