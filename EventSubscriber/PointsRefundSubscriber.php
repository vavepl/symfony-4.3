<?php

namespace App\EventSubscriber\Workflow;

use App\Entity\Event;
use App\Entity\UserEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

class PointsRefundSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.user_event_state.transition.user_remove' => 'onUserRemove',
        ];
    }

    public function onUserRemove(TransitionEvent $transitionEvent)
    {
        $userEvent = $transitionEvent->getSubject();
        if (!$userEvent instanceof UserEvent) {
            return;
        }

        /** @var Event $event */
        $event = $userEvent->getEvent();

        $difference = (new \DateTime('now'))->diff($event->getStartDate());

        $hours = $difference->h + ($difference->days * 24);

        if($hours >= getenv('EVENT_POINTS_REFUND_HOURS')){

            $company = $event->getCompany();

            $company->incrementBalance($this->getPointsRefundCommission($userEvent->getCommission()));

            $this->entityManager->persist($company);
            $this->entityManager->flush();
        }
    }

    private function getPointsRefundCommission(int $price): int
    {
        $commission = getenv('EVENT_POINTS_REFUND_COMMISSION');

        return $price * ($commission/100);
    }
}
