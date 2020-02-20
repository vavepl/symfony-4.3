<?php

namespace App\Event;

use App\Entity\Company;
use App\Entity\UserEvent;
use App\Message\MessageNotificationInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PushNotificationEvent extends Event
{
    public const NAME = 'notification.push';

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var MessageNotificationInterface $message
     */
    protected $message;

    /**
     * @var UserEvent $userEvent
     */
    protected $userEvent;

    protected $company;

    /**
     * UserRegisterEvent constructor.
     * @param UserInterface $user
     * @param MessageNotificationInterface $message
     * @param UserEvent $userEvent
     */
    public function __construct(UserInterface $user, MessageNotificationInterface $message, UserEvent $userEvent)
    {
        $this->user = $user;
        $this->message = $message;
        $this->userEvent = $userEvent;
    }

    /**
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * @return MessageNotificationInterface
     */
    public function getMessage(): MessageNotificationInterface
    {
        return $this->message;
    }

    /**
     * @param MessageNotificationInterface $message
     */
    public function setMessage(MessageNotificationInterface $message): void
    {
        $this->message = $message;
    }

    /**
     * @return UserEvent
     */
    public function getUserEvent(): UserEvent
    {
        return $this->userEvent;
    }

    /**
     * @param UserEvent $userEvent
     */
    public function setUserEvent(UserEvent $userEvent): void
    {
        $this->userEvent = $userEvent;
    }
}
