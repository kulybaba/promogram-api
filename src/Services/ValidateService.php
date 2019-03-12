<?php

namespace App\Services;

use App\Exception\JsonHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidateService
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validate entity and throw JsonHttpException if errors exist
     *
     * @param $entity
     */
    public function validate($entity)
    {
        $errors = $this->validator->validate($entity);
        if (count($errors)) {
            throw new JsonHttpException(400, $errors->get(0)->getMessage());
        }
    }
}