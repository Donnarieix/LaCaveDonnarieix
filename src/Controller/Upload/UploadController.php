<?php

namespace App\Controller\Upload;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class UploadController extends AbstractController
{
    private string $tmpDir;
    private string $storageDir;

    public function __construct(string $tmpDir, string $storageDir)
    {
        $this->tmpDir = $tmpDir;
        $this->storageDir = $storageDir;
    }

    #[Route('/api/uploads/init', name: 'upload_init', methods: ['POST'])]
    public function init(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $originalName = (string)($data['filename'] ?? '');
        $size = (int)($data['size'] ?? 0);

        if ($originalName === '' || $size <= 0) {
            return $this->json(['error' => 'filename/size requis'], 400);
        }

        // Chunk size conseillé (64 MiB). Tu peux monter à 128/256 si tu veux.
        $chunkSize = (int)($data['chunkSize'] ?? (64 * 1024 * 1024));
        $chunkSize = max(1 * 1024 * 1024, min($chunkSize, 256 * 1024 * 1024)); // 1MiB..256MiB

        $totalChunks = (int)ceil($size / $chunkSize);
        if ($totalChunks <= 0) {
            return $this->json(['error' => 'totalChunks invalide'], 400);
        }

        $uploadId = bin2hex(random_bytes(16)); // simple
        $dir = $this->tmpDir . '/' . $uploadId;

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $this->json(['error' => 'Impossible de créer le dossier tmp'], 500);
        }

        $manifest = [
            'uploadId' => $uploadId,
            'filename' => $originalName,
            'size' => $size,
            'chunkSize' => $chunkSize,
            'totalChunks' => $totalChunks,
            'createdAt' => time(),
        ];

        file_put_contents($dir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        return $this->json([
            'uploadId' => $uploadId,
            'chunkSize' => $chunkSize,
            'totalChunks' => $totalChunks,
        ]);
    }

    #[Route('/api/uploads/{uploadId}/chunk', name: 'upload_chunk', methods: ['POST'])]
    public function chunk(string $uploadId, Request $request): JsonResponse
    {
        $dir = $this->tmpDir . '/' . $uploadId;
        $manifestPath = $dir . '/manifest.json';

        if (!is_file($manifestPath)) {
            return $this->json(['error' => 'uploadId inconnu'], 404);
        }

        $index = (int)$request->request->get('index', -1);
        if ($index < 0) {
            return $this->json(['error' => 'index requis'], 400);
        }

        $chunkFile = $request->files->get('chunk');
        if (!$chunkFile) {
            return $this->json(['error' => 'chunk requis'], 400);
        }

        $manifest = json_decode((string)file_get_contents($manifestPath), true);
        $totalChunks = (int)($manifest['totalChunks'] ?? 0);

        if ($index >= $totalChunks) {
            return $this->json(['error' => 'index hors limite'], 400);
        }

        // On écrit le chunk tel quel en .part
        $partPath = sprintf('%s/%06d.part', $dir, $index);

        // move() fait un rename (rapide) si même FS
        $chunkFile->move($dir, basename($partPath));

        return $this->json(['ok' => true, 'index' => $index]);
    }

    #[Route('/api/uploads/{uploadId}/complete', name: 'upload_complete', methods: ['POST'])]
    public function complete(string $uploadId): JsonResponse
    {
        $dir = $this->tmpDir . '/' . $uploadId;
        $manifestPath = $dir . '/manifest.json';

        if (!is_file($manifestPath)) {
            return $this->json(['error' => 'uploadId inconnu'], 404);
        }

        $manifest = json_decode((string)file_get_contents($manifestPath), true);

        $originalName = (string)$manifest['filename'];
        $size = (int)$manifest['size'];
        $totalChunks = (int)$manifest['totalChunks'];

        // Vérifie que tous les chunks sont là
        for ($i = 0; $i < $totalChunks; $i++) {
            $partPath = sprintf('%s/%06d.part', $dir, $i);
            if (!is_file($partPath)) {
                return $this->json(['error' => 'Chunk manquant', 'missingIndex' => $i], 409);
            }
        }

        // Nom safe
        $slugger = new AsciiSlugger();
        $safeName = $slugger->slug(pathinfo($originalName, PATHINFO_FILENAME))->lower()->toString();
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $finalName = $safeName . ($ext ? '.' . strtolower($ext) : '');

        // Exemple: stockage par user
        $userId = $this->getUser()?->getUserIdentifier() ?? 'anon';
        $finalDir = $this->storageDir . '/' . $userId;
        if (!is_dir($finalDir) && !mkdir($finalDir, 0775, true) && !is_dir($finalDir)) {
            return $this->json(['error' => 'Impossible de créer le dossier final'], 500);
        }

        // Important: écriture en streaming (0 RAM)
        $finalPath = $finalDir . '/' . $uploadId . '-' . $finalName;
        $out = fopen($finalPath, 'wb');
        if (!$out) {
            return $this->json(['error' => 'Impossible d’ouvrir le fichier final'], 500);
        }

        $written = 0;
        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $partPath = sprintf('%s/%06d.part', $dir, $i);
                $in = fopen($partPath, 'rb');
                if (!$in) {
                    throw new \RuntimeException("Impossible de lire chunk $i");
                }
                $copied = stream_copy_to_stream($in, $out);
                fclose($in);
                if ($copied === false) {
                    throw new \RuntimeException("Copie échouée pour chunk $i");
                }
                $written += $copied;
            }
        } finally {
            fclose($out);
        }

        if ($written !== $size) {
            // Si taille attendue != taille réelle, on refuse (intégrité)
            @unlink($finalPath);
            return $this->json(['error' => 'Taille finale incorrecte', 'written' => $written, 'expected' => $size], 422);
        }

        // Nettoyage tmp
        for ($i = 0; $i < $totalChunks; $i++) {
            @unlink(sprintf('%s/%06d.part', $dir, $i));
        }
        @unlink($manifestPath);
        @rmdir($dir);

        return $this->json([
            'ok' => true,
            'path' => $finalPath,
            'filename' => $finalName,
            'bytes' => $written,
        ]);
    }
}
