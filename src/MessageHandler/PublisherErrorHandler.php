<?php

namespace App\MessageHandler;

use App\Message\PublisherErrorMessage;
use App\Repository\PublisherRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class PublisherErrorHandler implements MessageHandlerInterface
{
    private PublisherRepository $repository;

    private MailerInterface $mailer;

    private Environment $twig;

    private string $emailFrom;

    private array $emailsTo;

    public function __construct(
        PublisherRepository $repository,
        MailerInterface $mailer,
        Environment $twig,
        string $emailFrom,
        array $emailsTo
    ) {
        $this->repository = $repository;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->emailFrom = $emailFrom;
        $this->emailsTo = $emailsTo;
    }

    public function __invoke(PublisherErrorMessage $message)
    {
        $publisher = $this->repository->find($message->getId());

        if (null === $publisher) {
            return;
        }

        $subject = sprintf('Errors: parse publisher "%s"', $publisher->getName());

        $content = $this->twig->render('_email/publisher_error.html.twig', [
            'publisher' => $publisher,
            'reason'    => $message->getReason(),
            'trace'     => $message->getTrace(),
        ]);

        foreach ($this->emailsTo as $emailTo) {
            $email = (new Email())
                ->from($this->emailFrom)
                ->to($emailTo)
                ->priority(Email::PRIORITY_HIGH)
                ->subject($subject)
                ->html($content);

            $this->mailer->send($email);
        }
    }
}
