<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EmbedLMSController extends AbstractController
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @Route("/lms/embed", name="pumukit_lms_embed", methods={"POST"})
     */
    public function embedLms(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $mmId = $data['mmId'] ?? null;

        if (!$mmId) {
            return new JsonResponse(['error' => 'Missing mmId'], 400);
        }

        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => $mmId,
        ]);

        if (!$multimediaObject) {
            return new JsonResponse(['error' => 'MultimediaObject not found'], 404);
        }

        $currentYear = date('Y');
        $property = $multimediaObject->getProperty('embedded_in_lms');

        if (null === $property) {
            $multimediaObject->setProperty('embedded_in_lms', [$currentYear]);
        } elseif (is_array($property)) {
            if (!in_array($currentYear, $property)) {
                $property[] = $currentYear;
                $multimediaObject->setProperty('embedded_in_lms', $property);
            }
        } elseif ($property !== $currentYear) {
            $multimediaObject->setProperty('embedded_in_lms', [$property, $currentYear]);
        }

        $this->documentManager->flush();

        return new JsonResponse(['status' => 'ok', 'mmId' => $mmId]);
    }
}
