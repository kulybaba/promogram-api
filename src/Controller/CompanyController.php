<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
     * CompanyController constructor.
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $em
     */
    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->em = $em;
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
}
