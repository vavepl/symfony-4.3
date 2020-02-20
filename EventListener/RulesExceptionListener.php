<?php

namespace App\EventListener;


use App\Entity\Employee;
use App\Entity\SystemNotification;
use App\Entity\User;
use App\Exception\RulesException;
use App\Repository\SystemNotificationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class RulesExceptionListener
{

    /**
     * @var SystemNotification
     */
    private $data;
    /**
     * @var SystemNotificationRepository
     */
    private $systemNotificationRepository;

    public function __construct(SystemNotificationRepository $systemNotificationRepository)
    {
        $this->systemNotificationRepository = $systemNotificationRepository;
    }

    /**
     * @param ExceptionEvent $event
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onKernelException(ExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();
        if (!$exception instanceof RulesException) {
            return;
        }

        if (!$exception->getUser() instanceof UserInterface) {
            return;
        }

        if ($exception->getUser() instanceof Employee) {
            $this->data = $this->systemNotificationRepository->getLastRulesNotification(SystemNotification::TYPE_EMPLOYEE);
        }

        if ($exception->getUser() instanceof User) {
            $this->data = $this->systemNotificationRepository->getLastRulesNotification(SystemNotification::TYPE_USER);
        }
        $message = json_encode(["code" => $exception->getStatusCode(), "message" => $exception->getMessage(), "data" => $this->data->getBody()]);

        // Customize your response object to display the exception details
        $response = new Response();
        $response->setContent($message);

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // sends the modified response object to the event
        $event->setResponse($response);
    }
}
