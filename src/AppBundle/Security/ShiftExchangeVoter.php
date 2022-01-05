<?php
// src/AppBundle/Security/ShiftExchangeVoter.php
namespace AppBundle\Security;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Shift;
use AppBundle\Entity\ShiftExchange;
use AppBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ShiftExchangeVoter extends Voter
{
    const GET = 'get';
    const REJECT = 'reject';
    const ACCEPT = 'accept';
    const CREATE = 'create';

    private $decisionManager;
    private $container;

    /**
     * @var ShiftService
     */
    private $shiftService;

    public function __construct(ContainerInterface $container, AccessDecisionManagerInterface $decisionManager)
    {
        $this->container = $container;
        $this->decisionManager = $decisionManager;
    }

    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, array(self::GET, self::REJECT, self::ACCEPT, self::CREATE))) {
            return false;
        }

        // only vote on Task objects inside this voter
        if (!$subject instanceof ShiftExchange) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {  // the user must be logged in; if not, deny access
            return false;
        }

        // ROLE_ADMIN can do anything! The power!
        if ($this->decisionManager->decide($token, array('ROLE_ADMIN'))) {
            return true;
        }

        $shift_exchange = $subject;

        // you know $subject is a Post object, thanks to supports
        switch ($attribute) {
            case self::CREATE:
                if (!$subject instanceof Shift) {
                    return false;
                }
                return $subject->getShifter() == $user->getBeneficiary();
            case self::FREE:
            case self::LOCK:
                if ($this->decisionManager->decide($token, array('ROLE_ADMIN','ROLE_SHIFT_MANAGER'))) {
                    return true;
                }
                return false;
            case self::DISMISS:
                if ($this->decisionManager->decide($token, array('ROLE_ADMIN','ROLE_SHIFT_MANAGER'))) {
                    return true;
                }
                return $this->canDismiss($shift, $user);
            case self::REJECT:
                if ($this->decisionManager->decide($token, array('ROLE_ADMIN','ROLE_SHIFT_MANAGER'))) {
                    return true;
                }
                return $this->canReject($shift, $user);
            case self::ACCEPT:
                if ($this->decisionManager->decide($token, array('ROLE_ADMIN','ROLE_SHIFT_MANAGER'))) {
                    return true;
                }
                return $this->canAccept($shift, $user);
            case self::VALIDATE:
            case self::INVALIDATE:
                if ($this->decisionManager->decide($token, array('ROLE_ADMIN','ROLE_SHIFT_MANAGER'))) {
                    return true;
                }
                return false;
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canDismiss(Shift $shift, User $user)
    {
        if ($shift->getIsDismissed()) {
            return false;
        }
        if (!$shift->getShifter()) {
            return false;
        }
        if ($shift->getIsPast()) {
            return false;
        }
        if ($user->getBeneficiary() === $shift->getShifter()) {
            return true;
        }
        return false;
    }

    private function canReject(Shift $shift, User $user = null)
    {
        if ($user instanceof User) {  // the user is logged in
            return $user->getBeneficiary() === $shift->getLastShifter();
        } // the user is not logged in
        $token = $this->container->get('request_stack')->getCurrentRequest()->get('token');
        if ($shift->getId()) {
            if ($shift->getLastShifter()) {
                if ($token == $shift->getTmpToken($shift->getLastShifter()->getId())) {
                    return true;
                }
            } else {
                return false;
            }
        }
        return false;
    }

    private function canAccept(Shift $shift, User $user = null)
    {
        return $this->canReject($shift, $user);
    }


}
