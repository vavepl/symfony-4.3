<?php

declare(strict_types=1);

namespace App\Handler;


use App\Entity\User;
use App\Exception\Messages;
use App\Message\MessageNotificationInterface;
use App\Message\SmsNotification;
use Exception;
use Smsapi\Client\Feature\Sms\Bag\SendSmsBag;
use Smsapi\Client\SmsapiHttpClient;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SmsNotificationHandler implements MessageHandlerInterface
{
    public function __invoke(SmsNotification $notification)
    {
        /** @var User $user */
        $user = $notification->getUser();
        $message = $notification->getMessage();

        if (getenv('APP_ENV') === 'dev') return;
        $this->send($user->getPhone(), $message);
    }

    private function send(string $phone, MessageNotificationInterface $message)
    {
        $apiToken = getenv('SMSAPI_KEY');
        if (!$apiToken) {
            throw new Exception(Messages::SMS_API_KEY_NOT_FOUND);
        }

        $sms = SendSmsBag::withMessage($phone, $message->getTemplate());
        $sms->from = getenv('SMSAPI_FROM');

        $service = (new SmsapiHttpClient())
            ->smsapiPlService($apiToken);

        $service->smsFeature()->sendSms($sms);
    }
}
