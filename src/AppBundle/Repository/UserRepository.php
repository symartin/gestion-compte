<?php

namespace AppBundle\Repository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{

    public function findWithNewCycleStarting()
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->where('u.withdrawn = 0')
            ->andWhere('u.frozen = 0')
            ->andWhere('u.firstShiftDate is not NULL')
            ->andWhere('MOD(DATE_DIFF(:now, u.firstShiftDate), 28) = 0')
            ->setParameter('now', new \Datetime('now'));

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $role
     *
     * @return array
     */
    public function findByRole($role)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from($this->_entityName, 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%');

        return $qb->getQuery()->getResult();
    }
}
