<?php


namespace App\Controller\Employee;

use App\Dto\CommentDTO;
use App\Dto\EventDTO;
use App\Entity\Event;
use App\Exception\Messages;
use App\Service\EventService;
use App\Utils\UploaderHelper;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class EventController
 * @package App\Controller\Employee
 * @Rest\Route("/employees/events", name="api_employees_events_")
 */
class EventController extends AbstractFOSRestController
{
    /**
     * @var EventService
     */
    private $eventService;

    /**
     * EventController constructor.
     * @param EventService $eventService
     */
    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Create event
     *
     * @IsGranted({"ROLE_COMPANY_OWNER", "ROLE_COMPANY_EMPLOYEE"}, message="Resource access denied")
     * @Rest\Post("", name="post")
     * @ParamConverter(
     *     "event", class="App\Entity\Event",
     *     converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext"={"groups"={"write"}},
     *      "validator"={"groups"={"write"}}
     *     }
     * )
     * @Rest\View(serializerGroups={"read"}, statusCode=Response::HTTP_CREATED)
     *
     * @param Event $event
     * @param ConstraintViolationListInterface $validationErrors
     * @return Event|View
     * @throws Exception
     */
    public function createEvent(Event $event, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            return View::create($validationErrors, Response::HTTP_BAD_REQUEST);
        }

        $company = ($this->getUser())->getCompany();

        if (null === $company) {
            throw new NotFoundHttpException(Messages::COMPANY_PROFILE_NOT_EXIST);
        }

        $event = $this->eventService->create($event, $company);

        return $event;
    }

    /**
     * Update event
     *
     * @IsGranted("event:edit", subject="event", message="Resource access denied")
     * @Rest\Put("/{id}", name="id_put", requirements={"id"="\d+"})
     * @Rest\View(serializerGroups={"read"}, statusCode=Response::HTTP_OK)
     * @ParamConverter(
     *     "updatedEvent", class="App\Dto\EventDTO",
     *     converter="fos_rest.request_body",
     *     options={
     *      "deserializationContext"={"groups"={"read"}},
     *      "validator"={"groups"={"write"}}
     *     }
     * )
     *
     * @param Event $event
     * @param EventDTO $updatedEvent
     * @param ConstraintViolationListInterface $validationErrors
     * @return Event|View
     */
    public function updateEvent(Event $event, EventDTO $updatedEvent, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            return View::create($validationErrors, Response::HTTP_BAD_REQUEST);
        }

        $event = $this->eventService->update($event, $updatedEvent);

        return $event;
    }

    /**
     * Cancel event
     * @Rest\Put("/{id}/cancel", name="id_cancel_put", requirements={"id"="\d+"})
     * @IsGranted("event:edit", subject="event", message="Resource access denied")
     * @Rest\View(serializerGroups={"read"}, statusCode=Response::HTTP_OK)
     * @ParamConverter(
     *     "commentDTO", class="App\Dto\CommentDTO",
     *     converter="fos_rest.request_body",
     * )
     * @param Event $event
     * @param CommentDTO $commentDTO
     * @param ConstraintViolationListInterface $validationErrors
     * @return Event|View
     * @throws Exception
     */
    public function cancelEvent(Event $event, CommentDTO $commentDTO, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            return View::create($validationErrors, Response::HTTP_BAD_REQUEST);
        }

        $this->eventService->cancel($event, $commentDTO);
        return $event;
    }

    /**
     * Get event
     * @Rest\Get("/{id}", name="id_get", requirements={"id"="\d+"})
     * @IsGranted({"ROLE_COMPANY_OWNER", "ROLE_COMPANY_EMPLOYEE"}, message="Resource access denied")
     * @Rest\View(serializerGroups={"read"}, statusCode=Response::HTTP_OK)
     * @param Event $event
     * @return Event
     */
    public function getEvent(Event $event)
    {
        return $event;
    }

    /**
     * Get events with filter
     *
     * @Rest\Get("", name="get_filter")
     * @IsGranted({"ROLE_COMPANY_OWNER", "ROLE_COMPANY_EMPLOYEE"}, message="Resource access denied")
     * @Rest\QueryParam(name="category", requirements="\d+")
     * @Rest\QueryParam(name="price_from", requirements="\d+")
     * @Rest\QueryParam(name="price_to", requirements="\d+")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="10")
     * @Rest\QueryParam(name="page", requirements="\d+", default="1")
     * @Rest\QueryParam(name="dist_from", requirements="\d+", default="0")
     * @Rest\QueryParam(name="dist_to", requirements="\d+")
     * @Rest\QueryParam(name="date_from")
     * @Rest\QueryParam(name="date_to")
     * @Rest\QueryParam(name="latitude", requirements="-?\d+\.\d+")
     * @Rest\QueryParam(name="longitude", requirements="-?\d+\.\d+")
     * @Rest\QueryParam(name="rate", requirements="\d+")
     * @Rest\QueryParam(name="user_limit_reached", requirements="\d+")
     * @Rest\QueryParam(name="status_event", requirements="\d+")
     * @Rest\QueryParam(name="status_user_event")
     * @Rest\QueryParam(name="get_users_by_user_event_status")
     * @Rest\QueryParam(name="blacklist")
     * @Rest\View(serializerGroups={"read"}, statusCode=Response::HTTP_OK)
     *
     * @param ParamFetcher $paramFetcher
     * @return array
     */
    public function getEventsWithFilter(ParamFetcher $paramFetcher)
    {
        $company = ($this->getUser())->getCompany();

        if (null === $company) {
            throw new NotFoundHttpException(Messages::COMPANY_PROFILE_NOT_EXIST);
        }

        $events = $this->eventService->getForCompanyWithFilters($paramFetcher, $company);

        return $events;
    }

    /**
     * Get events with filter
     *
     * @Rest\Get("/list", name="get_list")
     * @IsGranted({"ROLE_COMPANY_OWNER", "ROLE_COMPANY_EMPLOYEE"}, message="Resource access denied")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="10")
     * @Rest\QueryParam(name="page", requirements="\d+", default="1")
     * @Rest\QueryParam(name="status", requirements="\d+")
     * @Rest\View(serializerGroups={"event_list"}, statusCode=Response::HTTP_OK)
     *
     * @param ParamFetcher $paramFetcher
     * @return array
     */
    public function getEventsList(ParamFetcher $paramFetcher)
    {
        $company = ($this->getUser())->getCompany();

        if (null === $company) {
            throw new NotFoundHttpException(Messages::COMPANY_PROFILE_NOT_EXIST);
        }

        $events = $this->eventService->getEventsList($paramFetcher, $company);

        return $events;
    }
}
