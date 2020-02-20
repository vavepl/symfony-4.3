<?php


namespace App\Service;


use App\Dto\CommentDTO;
use App\Dto\EventDTO;
use App\Entity\Company;
use App\Entity\Event;
use App\Entity\EventCategory;
use App\Entity\EventDetail;
use App\Entity\User;
use App\Entity\UserCompany;
use App\Exception\Messages;
use App\Repository\EventDetailRepository;
use App\Repository\EventRepository;
use App\Repository\UserCompanyRepository;
use App\Utils\UploaderHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FOS\RestBundle\Exception\InvalidParameterException;
use FOS\RestBundle\Request\ParamFetcher;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\File\File as FileObject;
use App\Dto\File as FileDto;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventService
{

    use ServiceTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var EventRepository
     */
    private $eventRepository;
    /**
     * @var EventDetailRepository
     */
    private $eventDetailRepository;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var UploaderHelper
     */
    private $uploaderHelper;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var PropertyListExtractorInterface
     */
    private $propertyListExtractor;

    /**
     * @var UserCompanyRepository
     */
    private $userCompanyRepository;

	/**
	 * EventService constructor.
	 *
	 * @param EntityManagerInterface $entityManager
	 * @param EventRepository $eventRepository
	 * @param EventDetailRepository $eventDetailRepository
	 * @param SerializerInterface $serializer
	 * @param UploaderHelper $uploaderHelper
	 * @param ValidatorInterface $validator
	 * @param PropertyListExtractorInterface $propertyListExtractor
	 * @param UserCompanyRepository $userCompanyRepository
	 */
    public function __construct(EntityManagerInterface $entityManager, EventRepository $eventRepository, EventDetailRepository $eventDetailRepository, SerializerInterface $serializer, UploaderHelper $uploaderHelper, ValidatorInterface $validator, PropertyListExtractorInterface $propertyListExtractor, UserCompanyRepository $userCompanyRepository)
    {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
        $this->eventDetailRepository = $eventDetailRepository;
        $this->serializer = $serializer;
        $this->uploaderHelper = $uploaderHelper;
        $this->validator = $validator;
        $this->propertyListExtractor = $propertyListExtractor;
	    $this->userCompanyRepository = $userCompanyRepository;
    }

    public function create(Event $event, Company $company): Event
    {
        if(!$event->getEventCategory()){
            throw new NotFoundHttpException(Messages::EVENT_CATEGORY_DOES_NOT_EXIST);
        }

        $event->setCompany($company);

        $usersCompany = $company->getEmployees();

        foreach($usersCompany as $userCompany){
            $userCompany->incrementActive(1);
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    public function update(Event $event, EventDTO $updatedEvent): Event
    {
        $event->setDescription($updatedEvent->getDescription());
        $event->setIsUserLimitReached($updatedEvent->getIsUserLimitReached());
	    $event->setPhone($updatedEvent->getPhone());
	    $event->setIsDeposit($updatedEvent->getIsDeposit());
	    $event->setDepositAmount($updatedEvent->getDepositAmount());

	    if($event->getUsers()->count() == 0){
            $event->setStartDate($updatedEvent->getStartDate());
            $event->setEndDate($updatedEvent->getEndDate());

            foreach ($event->getEventDetails() as $eventDetail) {
                $this->entityManager->remove($eventDetail);
            }

            $event->clearEventDetails();

            foreach ($updatedEvent->getEventDetails() as $updatedEventDetail) {
                $eventDetail = new EventDetail();

                $eventDetail->setPrice($updatedEventDetail["price"]);
                $eventDetail->setTitle($updatedEventDetail["title"]);

                $event->addEventDetail($eventDetail);
            }

            $event->setCalendarDetail($updatedEvent->getCalendarDetail());
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    public function get(Company $company)
    {
        return $company->getEvents();
    }

    public function getForCompanyWithFilters(ParamFetcher $paramFetcher, Company $company)
    {
        $blacklistedUsers = $this->userCompanyRepository->findUsersByCompanyStatus($company, UserCompany::STATUS_BLACKLISTED);

        $events = $this->eventRepository->findForCompanyWithFilters($paramFetcher, $company, $blacklistedUsers);

        return $events;
    }

    public function getEventsList(ParamFetcher $paramFetcher, Company $company)
    {
        $events = $this->eventRepository->findEventsList($paramFetcher, $company);

        return $events;
    }

	public function getForUsersWithFilters(ParamFetcher $paramFetcher, User $user)
	{
		$events = $this->eventRepository->findForUsersWithFilters($paramFetcher, $user);

		return $events;
	}

    /**
     * @param Event $event
     * @param CommentDTO $commentDTO
     * @throws Exception
     */
    public function cancel(Event $event, CommentDTO $commentDTO)
    {
        $eventStartDate = $event->getStartDate();
        $currentDate = new DateTime('now');
        $intervalDate = $eventStartDate->diff($currentDate);

        if (($eventStartDate < $currentDate) || ($intervalDate->format("%a") <= 2)) {
            throw new BadRequestHttpException(Messages::EVENT_CANCELLING_ALLOWED_48H_BEFORE_EVENT_START);
        }

        if ($event->getStatus() == Event::STATUS_CANCELED) {
            throw new BadRequestHttpException(Messages::CANNOT_CANCEL_CANCELLED_EVENT);
        }

        $event->setComment($commentDTO->comment);
        $event->setStatus(Event::STATUS_CANCELED);
        $event->setEndDate(new DateTime());

        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    /**
     * @param Event $event
     * @param UploadedFile $file
     * @throws FileNotFoundException
     * @throws \League\Flysystem\FileExistsException
     */
    public function uploadPhoto(Event $event, UploadedFile $file)
    {
        $filename = $this->uploaderHelper->uploadImage($file, null, UploaderHelper::EVENT_IMAGE);

        $event->addFile($filename);

        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    /**
     * @param Event $event
     * @param FileDto $file
     * @throws Exception
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function removePhoto(Event $event, FileDto $file)
    {
        $fileViolations = $this->validator->validate($file);

        if ($fileViolations->count() > 0) {
            throw new Exception($fileViolations->get(0)->getMessage());
        }

        if(empty($event->getFiles())){
            throw new Exception(Messages::THIS_EVENT_DO_NOT_HAVE_FILES);
        }

        $this->uploaderHelper->removeImage($file->getFilename());

        $event->removeFile($this->uploaderHelper->getServerPath($file->getFilename()));

        $this->entityManager->persist($event);
        $this->entityManager->flush();

    }
}
