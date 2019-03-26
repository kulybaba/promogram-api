<?php

namespace App\Controller;

use App\Aws\S3Manager;
use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api")
 */
class CompanyController extends AbstractController
{
    /**
     * @var SerializerInterface $serializer
     */
    private $serializer;

    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * @var S3Manager $s3Manager
     */
    private $s3Manager;

    /**
     * CompanyController constructor.
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $em
     * @param S3Manager $s3Manager
     */
    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, S3Manager $s3Manager)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->em = $em;
        $this->s3Manager = $s3Manager;
    }

    /**
     * @Route("/profile/{id}/companies/list", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function listAction(User $user)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->json(['companies' => $user->getCompany()]);
    }

    /**
     * @Route("/profile/companies/{id}/view", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function viewAction(Company $company)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->json($company);
    }

    /**
     * @Route("/profile/{id}/companies/create", requirements={"id"="\d+"}, methods={"POST"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function createAction(Request $request, User $user)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('company_create', $user);

        if (!$request->getContent()) {
            throw new HttpException('400', 'Bad request');
        }

        $company = $this->serializer->deserialize($request->getContent(), Company::class, JsonEncoder::FORMAT);
        $company->setUser($user);

        if (count($this->validator->validate($company))) {
            throw new HttpException('400', 'Bad request');
        }

        $this->em->persist($company);
        $this->em->flush();

        return $this->json($company);
    }

    /**
     * @Route("/profile/{user}/companies/{company}/update", requirements={"user": "\d+", "company": "\d+"}, methods={"PUT"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function updateAction(Request $request, User $user, Company $company)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('company_update', $user);

        if (!$request->getContent()) {
            throw new HttpException('400', 'Bad request');
        }

        $this->serializer->deserialize($request->getContent(), Company::class, JsonEncoder::FORMAT, ['object_to_populate' => $company]);

        if (count($this->validator->validate($company))) {
            throw new HttpException('400', 'Bad request');
        }

        $this->em->persist($company);
        $this->em->flush();

        return $this->json($company);
    }

    /**
     * @Route("/profile/{user}/companies/{company}/delete", requirements={"user": "\d+", "company": "\d+"}, methods={"DELETE"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function deleteAction(User $user, Company $company)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('company_delete', $user);

        $user->removeCompany($company);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Company deleted',
        ]);
    }

    /**
     * @Route("/profile/{user}/companies/{company}/change-picture", requirements={"user": "\d+", "company": "\d+"}, methods={"PUT"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function changePictureAction(Request $request, User $user, Company $company)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('company_update', $user);

        if (!$request->getContent()) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Bad request');
        }

        $picture = new \Imagick();

        if (!$picture->readImageBlob($request->getContent())) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Bad request');
        }

        $company->setPictureContent($request->getContent());

        $this->em->getUnitOfWork()->scheduleForUpdate($company);
        $this->em->persist($company);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Company picture changed'
        ]);
    }

    /**
     * @Route("/profile/{user}/companies/{company}/delete-picture", requirements={"user": "\d+", "company": "\d+"}, methods={"PUT"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function deletePictureAction(User $user, Company $company)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('company_update', $user);

        if ($company->getPicture() == $this->getParameter('default_company_picture')) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'No company picture');
        }

        if ($company->getPictureKey()) {
            $this->s3Manager->deletePicture($company->getPictureKey());
            $company->setPictureKey(null);
        }

        $company->setPicture($this->getParameter('default_company_picture'));

        $this->em->persist($company);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Company picture deleted'
        ]);
    }
}
