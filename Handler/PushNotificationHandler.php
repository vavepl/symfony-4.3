<?php


namespace App\Handler;


use App\Entity\User;
use App\Entity\UserDevice;
use App\Event\PushNotificationEvent;
use App\Message\Data;
use App\Message\MessageNotificationInterface;
use App\Message\PushNotification;
use App\Repository\UserEventRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Firebase\Bundle\CloudMessagingBundle\Http\Request;
use Firebase\Bundle\CloudMessagingBundle\Http\Request\AndroidNotification;
use Firebase\Bundle\CloudMessagingBundle\Http\Request\IOSNotification;
use Firebase\Bundle\CloudMessagingBundle\Service\FCMService;
use Symfony\Component\Security\Core\User\UserInterface;


class PushNotificationHandler implements MessageHandlerInterface
{
    /**
     * @var FCMService
     */
    private $fcmService;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var UserEventRepository
     */
    private $userEventRepository;

    public function __construct(FCMService $fcmService, EventDispatcherInterface $eventDispatcher, UserEventRepository $userEventRepository)
    {
        $this->fcmService = $fcmService;
        $this->eventDispatcher = $eventDispatcher;
        $this->userEventRepository = $userEventRepository;
    }

    public function __invoke(PushNotification $notification)
    {
        /** @var UserInterface $user */
        $user = $notification->getUser();
        $message = $notification->getMessage();
        $data = $notification->getData();

        //if (getenv('APP_ENV') === 'dev') return; todo test notifications
        $userDevices = $user->getDevices();
        if ($userDevices->isEmpty()) {
            return;
        }

        $this->send($userDevices, $message, $data);
    }

    private function send(Collection $userDevices, MessageNotificationInterface $message, Data $data)
    {
        /** @var UserDevice $device */
        foreach ($userDevices as $device) {

            switch ($device->getType()) {
                case UserDevice::PLATFORM_IOS:
                    $notification = new IOSNotification();
                    break;
                case UserDevice::PLATFORM_ANDROID:
                    $notification = new AndroidNotification();
                    break;
                default:
                    continue 2;
                    break;
            }

            $notification->setTitle($message->getTitle());
            $notification->setBody($message->getTemplate());

            $request = new Request();
            // todo: group token by platform, to optimize sending
            $request->setRegistrationIds([$device->getToken()]);
            $request->setNotification($notification);
            $request->setDatas($data->toArray());

            $this->fcmService->send($request);
        }
    }
}
