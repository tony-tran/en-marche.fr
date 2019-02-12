<?php

namespace AppBundle\Command;

use AppBundle\Entity\IdeasWorkshop\Idea;
use AppBundle\Mailer\MailerService;
use AppBundle\Mailer\Message\IdeaContributionsMessage;
use AppBundle\Repository\IdeaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotifyIdeaAuthorAboutContributionsCommand extends Command
{
    protected static $defaultName = 'idea-workshop:notification:contributions';

    private $entityManager;
    private $ideaRepository;
    private $urlGenerator;
    private $mailer;

    public function __construct(
        EntityManagerInterface $entityManager,
        IdeaRepository $ideaRepository,
        UrlGeneratorInterface $urlGenerator,
        MailerService $mailer
    ) {
        $this->entityManager = $entityManager;
        $this->ideaRepository = $ideaRepository;
        $this->urlGenerator = $urlGenerator;
        $this->mailer = $mailer;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getIdeas() as $idea) {
            $this->sendMail($idea);
        }
    }

    /**
     * @return Idea[]
     */
    private function getIdeas(): array
    {
        return $this->ideaRepository->createQueryBuilder('idea')
            ->where('idea.publishedAt IS NOT NULL')
            ->andWhere('DATE(idea.publishedAt) = :fourDays OR DATE(idea.publishedAt) = :eightDays')
            ->andWhere(':now < idea.finalizedAt')
            ->setParameter('now', new \DateTime())
            ->setParameter('fourDays', (new \DateTime('-4 days'))->format('Y-m-d'))
            ->setParameter('eightDays', (new \DateTime('-8 days'))->format('Y-m-d'))
            ->getQuery()
            ->getResult()
        ;
    }

    private function sendMail(Idea $idea): void
    {
        if ($idea->getCommentsCount() > 0) {
            $message = IdeaContributionsMessage::createWithContributions(
                $idea->getAuthor(),
                $idea->getName(),
                $this->urlGenerator->generate(
                    'react_app_ideas_workshop_proposition',
                    ['id' => $idea->getUuidAsString()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                $this->ideaRepository->countContributors($idea)['count'],
                $idea->getCommentsCount()
            );
        } else {
            $message = IdeaContributionsMessage::createWithoutContributions(
                $idea->getAuthor(),
                $idea->getName(),
                $this->urlGenerator->generate(
                    'react_app_ideas_workshop_proposition',
                    ['id' => $idea->getUuidAsString()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );
        }

        $this->mailer->sendMessage($message);
    }
}
