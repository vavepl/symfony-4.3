<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    use RepositoryTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findUserCompanyByStatus(Company $company, int $status)
    {
        return  $this->createQueryBuilder('u')
            ->leftJoin('u.companies', 'uc')
            ->andWhere('uc.company = :company')
            ->setParameter('company', $company)
            ->andWhere('uc.companyStatus = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param ParamFetcher $paramFetcher
     * @param array $favouritesUsers
     * @return array
     * @throws \Exception
     */
    public function findFavouritesWithFilters(ParamFetcher $paramFetcher, array $favouritesUsers)
    {
        $gender = $paramFetcher->get('gender');

        $age_from = $paramFetcher->get('age_from');
        $age_to = $paramFetcher->get('age_to');

        $dist_from = $paramFetcher->get('dist_from');
        $dist_to = $paramFetcher->get('dist_to');

        $latitude = $paramFetcher->get('latitude');
        $longitude = $paramFetcher->get('longitude');

        $rate = $paramFetcher->get('rate');

        $limit = $paramFetcher->get('limit');
        $page = $paramFetcher->get('page');

        $qb =  $this->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'up')
            ->addSelect('u, up');

        if ($gender === "0" || $gender === "1") {
            $qb->andWhere('up.gender = :gender');
            $qb->setParameter('gender', $gender);
        }

        if($rate) {
            $qb->andWhere('u.ratingTotal >= :rate');
            $qb->setParameter('rate', $rate);
        }

        if ($dist_from && $dist_to && $latitude && $longitude) {
            $qb->andWhere('ST_Distance_Sphere(up.point, ST_GeomFromText(:p2)) BETWEEN :dist_from AND :dist_to');

            $qb->setParameter('dist_from', $dist_from, ParameterType::INTEGER);
            $qb->setParameter('dist_to', $dist_to, ParameterType::INTEGER);

            $qb->setParameter('p2', sprintf('POINT(%f %f)', $latitude, $longitude), Type::STRING);
        }

        if ($age_from && $age_to) {
            $startDate = (new DateTimeImmutable('first day of january'))->modify(sprintf("- %d years", $age_to));
            $endDate = (new DateTimeImmutable('last day of december'))->modify(sprintf("- %d years", $age_from));

            $qb->andWhere('up.birthDate BETWEEN :start_date AND :end_date');
            $qb->setParameter('start_date', $startDate);
            $qb->setParameter('end_date', $endDate);
        }

        $qb->andWhere('u.id IN (:ids)');
        $qb->setParameter('ids', $favouritesUsers);

        return $this->paginate($qb, $limit, $page);
    }

    /**
     * @param ParamFetcher $paramFetcher
     * @param array $blacklistedUsers
     * @return array
     */
    public function findBlacklistWithFilters(ParamFetcher $paramFetcher, array $blacklistedUsers)
    {
        $limit = $paramFetcher->get('limit');
        $page = $paramFetcher->get('page');

        $qb =  $this->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'up')
            ->addSelect('u, up');

        $qb->andWhere('u.id IN (:ids)');
        $qb->setParameter('ids', $blacklistedUsers);

        return $this->paginate($qb, $limit, $page);
    }

}
