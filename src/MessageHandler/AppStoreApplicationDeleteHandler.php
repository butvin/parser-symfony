<?php

namespace App\MessageHandler;

use App\Message\AppStoreApplicationDeleteMessage;
use App\Repository\AppStoreApplicationRepository;
use App\Repository\AppStorePublisherRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Email;

class AppStoreApplicationDeleteHandler implements MessageHandlerInterface
{
    private AppStorePublisherRepository $publisherRepository;

    private AppStoreApplicationRepository $applicationRepository;

    private MailerInterface $mailer;

    private string $emailFrom;

    private array $emailsTo;

    public function __construct(
        AppStorePublisherRepository $publisherRepository,
        AppStoreApplicationRepository $applicationRepository,
        MailerInterface $mailer,
        string $emailFrom,
        array $emailsTo
    ) {
        $this->publisherRepository = $publisherRepository;
        $this->applicationRepository = $applicationRepository;
        $this->mailer = $mailer;
        $this->emailFrom = $emailFrom;
        $this->emailsTo = $emailsTo;
    }

    public function __invoke(AppStoreApplicationDeleteMessage $message)
    {
        $publisher = $this->publisherRepository->find($message->getPublisherId());

        $applications = $this->applicationRepository->createQueryBuilder('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $message->getAppIds())
            ->getQuery()
            ->getResult();

        if (empty($applications)) {
            return;
        }

        $subject = sprintf('Problems with application(s)');

        foreach ($this->emailsTo as $emailTo) {
            $email = (new TemplatedEmail())
                ->from($this->emailFrom)
                ->to($emailTo)
                ->priority(Email::PRIORITY_HIGH)
                ->subject($subject)
                ->htmlTemplate('_email/application_delete.html.twig')
                ->context([
                    'publisher'    => $publisher,
                    'applications' => $applications
                ]);

            $this->mailer->send($email);
        }
    }
}
