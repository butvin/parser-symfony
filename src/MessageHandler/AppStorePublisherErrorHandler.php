<?php

namespace App\MessageHandler;

use App\Message\AppStorePublisherErrorMessage;
use App\Repository\AppStorePublisherRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AppStorePublisherErrorHandler implements MessageHandlerInterface
{
    private string $emailFrom;

    private array $emailsTo;

    private AppStorePublisherRepository $publisherRepository;
    private MailerInterface $mailer;
    private Environment $twig;

    public function __construct(
        AppStorePublisherRepository $publisherRepository,
        MailerInterface $mailer,
        Environment $twig,
        string $emailFrom,
        array $emailsTo
    ) {
        $this->publisherRepository = $publisherRepository;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->emailFrom = $emailFrom;
        $this->emailsTo = $emailsTo;
    }

    public function __invoke(AppStorePublisherErrorMessage $message)
    {
        $publisher = $this->publisherRepository->find($message->getId());

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
