<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\UserEvent;
use App\Repository\EventRepository;
use App\Repository\UserEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EventCloseCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:event-close';

    /**
     * @var EventRepository
     */
    private $eventRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
	 * EventNotificationCommand constructor.
	 * @param EventRepository $eventRepository
	 * @param EntityManagerInterface $entityManager
	 */
    public function __construct(EventRepository $eventRepository, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;

        parent::__construct();
    }

    protected function configure()
    {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = new \DateTime("now");

        $events = $this->eventRepository->findToClose();
        /** @var Event $event */
        foreach ($events as $event) {
            $output->writeln($date->format("Y-m-d H:i:s") . " - " . self::class . ": Close Event " . $event->getId());

            $event->close();

            $this->entityManager->persist($event);
            $this->entityManager->flush();
        }
    }
}
