<?php

namespace App\Validator;

use App\Entity\AppStorePublisher;
use App\Service\AppStorePublisherParser;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ContainsAppStoreParseUrlValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ContainsAppStoreParseUrl) {
            throw new UnexpectedTypeException($constraint, ContainsAppStoreParseUrl::class);
        }

        if (!$value instanceof AppStorePublisher) {
            throw new UnexpectedValueException($value, AppStorePublisher::class);
        }
        $externalId = AppStorePublisherParser::getIdByUrl($value->getUrl());
        if ($externalId && AppStorePublisherParser::isDeveloperUrl($value->getUrl())) {
            $value->setExternalId($externalId);
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ url }}', $value->getUrl())
            ->addViolation();
    }
}