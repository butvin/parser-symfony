<?php

namespace App\MessageHandler;

use App\Message\PublisherDeleteMessage;
use App\Repository\PublisherRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Email;

class PublisherDeleteHandler implements MessageHandlerInterface
{
    private PublisherRepository $repository;

    private MailerInterface $mailer;

    private string $emailFrom;

    private array $emailsTo;

    public function __construct(
        PublisherRepository $repository,
        MailerInterface $mailer,
        string $emailFrom,
        array $emailsTo
    ) {
        $this->repository = $repository;
        $this->mailer = $mailer;
        $this->emailFrom = $emailFrom;
        $this->emailsTo = $emailsTo;
    }

    public function __invoke(PublisherDeleteMessage $message)
    {
        $publisher = $this->repository->find($message->getId());

        if (null === $publisher) {
            return;
        }

        $subject = sprintf('Problems with publisher "%s"', $publisher->getName());

        foreach ($this->emailsTo as $emailTo) {
            $email = (new TemplatedEmail())
                ->from($this->emailFrom)
                ->to($emailTo)
                ->priority(Email::PRIORITY_HIGH)
                ->subject($subject)
                ->htmlTemplate('_email/publisher_delete.html.twig')
                ->context([
                    'publisher'    => $publisher,
                    'reason'       => $message->getReason()
                ]);

            $this->mailer->send($email);
        }
    }
}
