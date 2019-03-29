<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Coupon;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api")
 */
class CouponController extends AbstractController
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
     * CouponController constructor.
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
     * @Route("/profile/companies/{id}/coupons/list", requirements={"id"="\d+"}, methods={"GET"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function listAction(Company $company)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('coupon_view', $company->getUser());

        return $this->json(['coupons' => $company->getCoupons()], Response::HTTP_OK, [], ['normalization' => 'coupon']);
    }

    /**
     * @Route("/profile/companies/coupons/{id}/view", requirements={"id"="\d+"}, methods={"GET"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function viewAction(Coupon $coupon)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('coupon_view', $coupon->getRetailer());

        return $this->json($coupon, Response::HTTP_OK, [], ['normalization' => 'coupon']);
    }

    /**
     * @Route("/profile/companies/{id}/coupons/create", requirements={"id"="\d+"}, methods={"POST"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function createAction(Request $request, Company $company)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('coupon_create', $company->getUser());

        if (!$request->getContent()) {
            throw new HttpException('400', 'Bad request');
        }

        $data = json_decode($request->getContent(), true);

        if (!$data['expire']) {
            throw new HttpException('400', 'Bad request');
        }

        $expire = new \DateTime($data['expire']);

        $coupon = $this->serializer->deserialize($request->getContent(), Coupon::class, JsonEncoder::FORMAT, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['expire']]);
        $coupon->setExpire($expire);
        $coupon->setRetailer($this->getUser());
        $coupon->setCompany($company);

        if (count($this->validator->validate($coupon))) {
            throw new HttpException('400', 'Bad request');
        }

        $this->em->persist($coupon);
        $this->em->flush();

        return $this->json($coupon, Response::HTTP_OK, [], ['normalization' => 'coupon']);
    }

    /**
     * @Route("/profile/companies/coupons/{id}/update", requirements={"id"="\d+"}, methods={"PUT"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function updateAction(Request $request, Coupon $coupon)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('coupon_update', $coupon->getRetailer());

        if (!$request->getContent()) {
            throw new HttpException('400', 'Bad request');
        }

        $data = json_decode($request->getContent(), true);

        if (!$data['expire']) {
            throw new HttpException('400', 'Bad request');
        }

        $expire = new \DateTime($data['expire']);

        $this->serializer->deserialize($request->getContent(), Coupon::class, JsonEncoder::FORMAT, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['expire'], AbstractNormalizer::OBJECT_TO_POPULATE => $coupon]);
        $coupon->setExpire($expire);

        if (count($this->validator->validate($coupon))) {
            throw new HttpException('400', 'Bad request');
        }

        $this->em->persist($coupon);
        $this->em->flush();

        return $this->json($coupon, Response::HTTP_OK, [], ['normalization' => 'coupon']);
    }

    /**
     * @Route("/profile/companies/coupons/{id}/delete", requirements={"id"="\d+"}, methods={"DELETE"})
     * @IsGranted("ROLE_RETAILER")
     */
    public function deleteAction(Coupon $coupon)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('coupon_delete', $coupon->getRetailer());

        $company = $coupon->getCompany();

        $company->removeCoupon($coupon);

        $this->em->persist($company);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Coupon deleted',
        ]);
    }
}
