<?php

namespace App\Controller;

use App\Entity\Commune;
use App\Entity\Media;
use App\Repository\CommuneRepository;
use App\Repository\MediaRepository;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CommuneController extends AbstractController
{
    /**
     * @Route("commune/{slug}", name="communebyslug")
     * @param string $slug
     * @param CommuneRepository $communeRepository
     * @return JsonResponse
     */
    public function getCommuneBySlug(string $slug, CommuneRepository $communeRepository)
    {
        $response = new JsonResponse();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($this->serializeCommune($communeRepository->findBy(['slug' => $slug])));
        return $response;
    }

    /**
     * @Route("commune", name="commune")
     */
    public function getCommune(Request $request, CommuneRepository $communeRepository)
    {
        $filter = [];
        $em = $this->getDoctrine()->getManager();
        $metaData = $em->getClassMetadata(Commune::class)->getFieldNames();
        foreach ($metaData as $value) {
            if ($request->query->get($value)) {
                $filter[$value] = $request->query->get($value);
            }
        }

        $response = new JsonResponse();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($this->serializeCommune($communeRepository->findBy($filter)));
        return $response;
    }

    /**
     * @Route("api/admin/commune", name="addCommune", methods={"PUT"})
     * @param Request $request
     * @return Response
     * @Security(name="Bearer")
     */
    public function createCommune(Request $request) {
        $entityManager = $this->getDoctrine()->getManager();
        $commune = new Commune();
        $datas = json_decode($request->getContent(),true);
        $commune->setCodeDepartement($datas['codeDepartement'])
            ->setCodeRegion($datas['codeRegion'])
            ->setCodePostal($datas['codePostal'])
            ->setName($datas['name'])
            ->setSlug($datas['name'])
            ->setPopulation($datas['population']);
                $contentMedia = new Media();
                $contentMedia->setFormat("photo")
                    ->setUrl($datas['media']);
                $commune->setMedia($contentMedia);
                $entityManager->persist($contentMedia);

        $entityManager->persist($commune);
        $entityManager->flush();
        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent("Commune created at id : " . $commune->getId());
        return $response;
    }

    /**
     * @Route ("api/admin/commune", name="deleteCommune", methods={"DELETE"})
     * @param Request $request
     * @param CommuneRepository $communeRepository
     * @return Response
     * @Security(name="Bearer")
     */
    public function deleteCommune(Request $request, CommuneRepository $communeRepository) {
        $entityManager = $this->getDoctrine()->getManager();
        $data = json_decode(
            $request->getContent(),
            true
        );
        $response = new Response();
        if (isset($data['commune_id'])) {
            $commune = $communeRepository->find($data['commune_id']);
            if ($commune === null) {
                $response->setContent("Cette commune n'existe pas");
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            } else {
                $entityManager->remove($commune);
                $entityManager->flush();
                $response->setContent("Suppression de la commune");
                $response->setStatusCode(Response::HTTP_OK);
            }
        } else {
            $response->setContent("Mauvais format de la requête");
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
        return $response;
    }

    /**
     * @Route ("api/admin/commune", name="updateCommune", methods={"PATCH"})
     * @param Request $request
     * @param CommuneRepository $communeRepository
     * @param MediaRepository $mediaRepository
     * @return Response
     * @Security(name="Bearer")
     */
    public function updateCommune(Request $request, CommuneRepository $communeRepository,MediaRepository $mediaRepository) {
        $entityManager = $this->getDoctrine()->getManager();
        $data = json_decode(
            $request->getContent(),
            true
        );
        $response = new Response();
        if ($data['commune_id']) {
            $commune = $communeRepository->findOneBy(['id' => $data['commune_id']]);
            $newCommune = $commune;
            isset($data["name"]) && $newCommune->setName($data['name']);
            isset($data["codeDepartement"]) && $newCommune->setcodeDepartement($data['codeDepartement']);
            isset($data["codeRegion"]) && $newCommune->setcodeRegion($data['codeRegion']);
            isset($data["population"]) && $newCommune->setpopulation($data['population']);
            isset($data["codesPostal"]) && $newCommune->setMedia($data['codesPostal']);
            if ($data["media"]) {
                    $contentMedia = $newCommune->getMedia();
                    $contentMedia->setUrl($data["media"]);
                    $entityManager->persist($contentMedia);
            }

            $entityManager->persist($commune);
            $entityManager->flush();
            $response->setContent("Mise à jours de la commune à l'id : " . $commune->getId());
            $response->setStatusCode(Response::HTTP_OK);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
        return $response;
    }

    private function serializeCommune($objet){
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getSlug();
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);

        return $serializer->serialize($objet, 'json');
    }
}
