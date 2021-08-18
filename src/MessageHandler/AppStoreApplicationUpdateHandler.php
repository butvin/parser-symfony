<?php

namespace App\MessageHandler;

use App\Message\AppStoreApplicationUpdateMessage;
use App\Repository\AppStoreApplicationRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Email;

class AppStoreApplicationUpdateHandler implements MessageHandlerInterface
{
    private AppStoreApplicationRepository $repository;

    private MailerInterface $mailer;

    private string $emailFrom;

    private array $emailsTo;

    public function __construct(
        AppStoreApplicationRepository $repository,
        MailerInterface $mailer,
        string $emailFrom,
        array $emailsTo
    ) {
        $this->repository = $repository;
        $this->mailer = $mailer;
        $this->emailFrom = $emailFrom;
        $this->emailsTo = $emailsTo;
    }

    public function __invoke(AppStoreApplicationUpdateMessage $message)
    {
        $application = $this->repository->find($message->getId());

        if (null === $application) {
            return;
        }

        $subject = sprintf('Application "%s" updated.', $application->getName());

        foreach ($this->emailsTo as $emailTo) {
            $email = (new TemplatedEmail())
                ->from($this->emailFrom)
                ->to($emailTo)
                ->priority(Email::PRIORITY_HIGH)
                ->subject($subject)
                ->htmlTemplate('_email/application_update.html.twig')
                ->context([
                    'application' => $application,
                    'oldVersion'     => $message->getOldVersion(),
                    'newVersion'     => $message->getNewVersion(),
                ]);

            $this->mailer->send($email);
        }
    }
}
