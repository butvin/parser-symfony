<?php


namespace App\Validator;

use App\Entity\Publisher;
use App\Service\AbstractParser;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ContainsParseUrlValidator extends ConstraintValidator
{
    /**
     * @var iterable|AbstractParser[]
     */
    private iterable $parsers;

    public function __construct(iterable $parsers)
    {
        $this->parsers = $parsers;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ContainsParseUrl) {
            throw new UnexpectedTypeException($constraint, ContainsParseUrl::class);
        }

        if (!$value instanceof Publisher) {
            throw new UnexpectedValueException($value, Publisher::class);
        }

        foreach ($this->parsers as $parser) {
            if ($externalId = $parser->getExternalId($value->getUrl())) {
                $value->setExternalId($externalId);
                $value->setType($parser->getType());
                return;
            }
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ url }}', $value->getUrl())
            ->addViolation();
    }
}