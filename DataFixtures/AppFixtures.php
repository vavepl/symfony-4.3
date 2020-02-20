<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\EmployeeVerification;
use App\Entity\Event;
use App\Entity\EventCategory;
use App\Entity\EventDetail;
use App\Entity\NotificationSetting;
use App\Entity\UserEvent;
use App\Entity\UserMarket;
use App\Entity\UserProfile;
use App\Entity\User;
use App\Entity\Employee;
use App\Entity\UserVerification;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use Faker\Factory;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    const USER_QUANTITY = 10;
    const EVENTS_QUANTITY = 100;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var Factory
     */
    private $faker;

    /**
     * UserFixtures constructor.
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        (new Dotenv())->load(__DIR__.'/../../.env');

        $this->encoder = $encoder;
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadNotificationSettings($manager, NotificationSetting::USER_TYPE_USER);
        $this->loadNotificationSettings($manager, NotificationSetting::USER_TYPE_EMPLOYEE);

        $this->loadEventCategories($manager);
        $manager->flush();

        for ($i = 0; $i < self::USER_QUANTITY; $i++) {

            $user = $this->loadUser($manager);
            $this->loadProfile($manager, $user);
            $this->loadUserVerification($manager, $user);

            $company = $this->loadCompanies($manager);

            $employee = $this->loadEmployee($manager, $company, Employee::ROLE_COMPANY_OWNER);
            $employee = $this->loadEmployee($manager, $company, Employee::ROLE_COMPANY_EMPLOYEE);
            $this->loadEmployeeVerification($manager, $employee);

            $event = $this->loadEvents($manager, $company);

            $this->loadUserEvent($manager, $user, $event);

            $this->loadUserMarket($manager, $user, $event);

            $manager->flush();
        }

        $manager->flush();
    }

    public function loadUser(ObjectManager $manager): User
    {
        $user = new User();
        $user->setEmail($this->faker->email());
        $user->setPhone($this->faker->numerify('48#########'));
        $user->setPassword($this->encoder->encodePassword(
            $user,
            getenv('USER_PLAIN_PASSWORD')
        ));
        $user->setRoles([User::ROLE_USER]);
        $user->setIsEnabled(false);
        $user->setRatingScore($this->faker->numberBetween(1,5));

        $manager->persist($user);
        return $user;
    }

    public function loadEmployee(ObjectManager $manager, Company $company, string $role): Employee
    {
        $user = new Employee();
        $user->setEmail($this->faker->email());
        $user->setPhone($this->faker->numerify('48#########'));
        $user->setPassword($this->encoder->encodePassword(
            $user,
            getenv('USER_PLAIN_PASSWORD')
        ));
        $user->setRoles([$role]);
        $user->setCompany($company);
        $user->setGivenName($this->faker->firstName);
        $user->setFamilyName($this->faker->lastName);

        $manager->persist($user);
        return $user;
    }

    public function loadProfile(ObjectManager $manager, User $user): void
    {
        $participant = new UserProfile();
        $participant->setGivenName($this->faker->firstName);
        $participant->setFamilyName($this->faker->lastName);
        $participant->setBirthDate($this->faker->dateTime());
        $participant->setStreet($this->faker->streetAddress);
        $participant->setAvatar("/uploads/user_avatar/test.png");
        $participant->setPostalCode($this->faker->numerify('##-###'));
        $participant->setLocality($this->faker->city);
        $participant->setIsConsentingMarketing($this->faker->numberBetween(0,1));
        $participant->setIsProfilePublic(true);
        $participant->setPoint($this->getRandomPoint());

        $participant->setUser($user);
        $participant->setGender($this->faker->numberBetween(0,1));

        $manager->persist($participant);
    }

    public function loadEvents(ObjectManager $manager, Company $company)
    {
        $event = new Event();

        $event->setPoint($this->getRandomPoint());

        $event->setLocality($this->faker->city);
        $event->setStreet($this->faker->streetAddress);
        $event->setVoivodship($this->faker->randomElement(['małopolskie', 'mazowieckie', 'śląskie', 'wrocławskie', 'lubuskie', 'lubelskie', 'podkarpackie', 'opolskie', 'pomorskie', 'zachochodniopomorskie', 'rzeszowskie']));

        $event->setPhone($this->faker->numerify('48#########'));
        $event->setCountry($this->faker->country);
        $event->setDescription($this->faker->realText(200));

        $startDate = $this->faker->dateTime();
        $event->setStartDate($startDate);
        $event->setEndDate($this->faker->dateTimeBetween($startDate, '+ 5 days'));

        $event->setStatus(0);
        $event->setIsDeposit(true);
        $event->setDepositAmount($this->faker->numberBetween(10,50));
        $event->setIsUserLimitReached(false);
        $event->setCompany($company);

        $calendarDetail = '';
        $event->setCalendarDetail($calendarDetail);

        $event->setEventCategory($this->getRandomCategory($manager));

        $manager->persist($event);

        for ($i = 0; $i < 3; $i++) {
            $eventDetail = new EventDetail();
            $eventDetail->setTitle($this->faker->randomElement(['Dekolt', 'Policzki', 'Uszy', 'Uszy', 'Dłonie']));
            $eventDetail->setPrice($this->faker->randomElement([5000,10000,15000,20000,25000,30000,35000, 40000, 45000, 50000]));
            $eventDetail->setEvent($event);
            $manager->persist($eventDetail);
        }

        return $event;
    }

    public function loadUserEvent(ObjectManager $manager, User $user, Event $event)
    {
        $userEvent = new UserEvent();

        $userEvent->setStatus(UserEvent::STATUS_INIT);
        $userEvent->setUser($user);
        $userEvent->setEvent($event);
        $userEvent->setDateSelection($this->faker->dateTime());

        $manager->persist($userEvent);
    }

    public function loadUserMarket(ObjectManager $manager, User $user)
    {
        $userMarket = new UserMarket();

        $userMarket->setUser($user);
        $userMarket->setEventCategory($this->getRandomCategory($manager));
        $userMarket->setStartDate($this->faker->dateTime());
        $userMarket->setEndDate($this->faker->dateTime());
        $userMarket->setRatingScore(3);
        $userMarket->setPoint($this->getRandomPoint());
        $userMarket->setLocality("Warszawa");
        $userMarket->setRadius(10);

        $manager->persist($userMarket);
    }

    public function getRandomCategory(ObjectManager $manager)
    {
        $parentCategories = $manager->getRepository(EventCategory::class)->findBy(['parent'=>null]);
        $randomParentIdx = array_rand($parentCategories, 1);

        $children = $manager->getRepository(EventCategory::class)->children($parentCategories[$randomParentIdx]);
        $randomChildIdx = array_rand($children, 1);

        return ($children[$randomChildIdx]);
    }

    public function loadCompanies(ObjectManager $manager)
    {
        $company = new Company();
        $company->setStreet($this->faker->streetAddress);
        $company->setPhone($this->faker->numerify('48#########'));
        $company->setLocality($this->faker->city);
        $company->setTaxIdNumber($this->faker->numerify('##########'));
        $company->setEmail($this->faker->email);
        $company->setLegalName($this->faker->company);
        $company->setPostalCode($this->faker->numerify('##-###'));
        $company->setBankAccountNumber($this->faker->iban('pl'));
        $company->setRatingScore($this->faker->numberBetween(1,5));
        $company->setLogo($this->faker->imageUrl());
        $company->setIsConsentingMarketing($this->faker->boolean(50));
        $company->setGivenName($this->faker->firstName);
        $company->setFamilyName($this->faker->lastName);

        $manager->persist($company);
        return $company;
    }

    public function loadUserVerification(ObjectManager $manager, User $user): void
    {
        $verification = new UserVerification();
        $verification->setCode(getenv('USER_VERIFICATION_CODE'));
        $verification->setExpire((new DateTime())->modify("+".getenv('USER_VERIFICATION_LIFETIME')." minutes"));
        $verification->setUser($user);

        $manager->persist($verification);
    }

    public function loadEmployeeVerification(ObjectManager $manager, Employee $employee): void
    {
        $verification = new EmployeeVerification();
        $verification->setCode(getenv('USER_VERIFICATION_CODE'));
        $verification->setExpire((new DateTime())->modify("+".getenv('USER_VERIFICATION_LIFETIME')." minutes"));
        $verification->setEmployee($employee);

        $manager->persist($verification);
    }

    public function loadNotificationSettings(ObjectManager $manager, $userType): void
    {
        foreach (NotificationSetting::TYPES as $key=>$name) {
            $notificationSetting = new NotificationSetting();

            $notificationSetting->setChannel(NotificationSetting::CHANNEL_EMAIL);
            $notificationSetting->setName($name);
            $notificationSetting->setUserType($userType);
            $notificationSetting->setMessageType($key);

            $manager->persist($notificationSetting);
            $manager->flush();
        }
    }


    public function loadEventCategories(ObjectManager $manager): void
    {
        $tree_category_1 = [
            'root' => 'Medycyna estetyczna',
             'elements' => [
                'Toksyna botulinowa',
                'Kwas hialuronowy',
                'Mezoterapia',
                'Osocze bogatopłytkowe',
                'Lipoliza iniekcyjna',
                'Leczenie nadpotliwości',
                'Terapia laserem CO2',
                'Radiofrekwencja mikroigłowa',
                'HIFU - Nieoperacyjny lifting skóry',
                'Karboksyterapia',
                'Nici PDO',
                [
                    'Nici haczykowe' => ['Dekolt', 'Policzki']]
                ]
        ];

        $tree_category_2 = [
            'root' => 'Tatuaż',
            'elements' => [
                'Cover up',
                'Tatuaż',
                'Kosmetyki do tatuażu',
            ]
        ];

        $tree_category_3 = [
            'root' => 'Fryzjerstwo',
            'elements' => [
                'Strzyżenie damskie',
                'Strzyżenie męskie',
                'Farbowanie włosów (koloryzacja)',
                'Farbowanie włosów z refleksami',
                'Pasemka na włosach (balayage)',
                'Dekoloryzacja włosów',
                'Koloryzacja flash',
                'Modelowanie włosów',
                'Przedłużanie włosów',
                'Zagęszczanie włosów',
                'Doczepianie włosów',
            ]
        ];

        $tree_category_4 = [
            'root' => 'Inne',
            'elements' => [
                'Manicure paznokci',
                'Pedicure paznokci',
                'Makijaż',
            ]
        ];

        $this->seedEventCategories($manager, $tree_category_1);
        $this->seedEventCategories($manager, $tree_category_2);
        $this->seedEventCategories($manager, $tree_category_3);
        $this->seedEventCategories($manager, $tree_category_4);
    }

    private function seedEventCategories(ObjectManager $manager, array $categories)
    {
        try {
            $main_category = new EventCategory();
            $main_category->setTitle($categories['root']);
            $manager->persist($main_category);

            foreach ($categories['elements'] as $element) {
                if (is_array($element)) {

                    $arrKey =  array_key_first($element);

                    // 2nd level tree
                    $sub_category = new EventCategory();
                    $sub_category->setTitle($arrKey);
                    $sub_category->setParent($main_category);
                    $manager->persist($sub_category);
                    $manager->flush();

                    // 3rd level tree
                    foreach ($element[$arrKey] as $sibling) {
                        $sub_category_sibling = new EventCategory();
                        $sub_category_sibling->setTitle($sibling);
                        $sub_category_sibling->setParent($sub_category);
                        $manager->persist($sub_category_sibling);
                        $manager->flush();
                    }
                    continue;
                }

                // 2nd level
                $sub_category = new EventCategory();
                $sub_category->setTitle($element);
                $sub_category->setParent($main_category);
                $manager->persist($sub_category);
                $manager->flush();
            }
        } catch (Exception $exception){
            echo "Exception " . $exception->getCode();
        }
    }

    private function getRandomPoint()
    {
        $point = new Point($this->faker->longitude, $this->faker->latitude);
        $point->setSrid(0);

        return $point;
    }
}
