<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShiftExchange
 *
 * @ORM\Table(name="shift_exchange")
 * @ORM\Entity
 */
class ShiftExchange
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Shift", inversedBy="shift_exchanges")
     * @ORM\JoinColumn(name="shift_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $shift;

    /**
     * @ORM\ManyToOne(targetEntity="Beneficiary", inversedBy="received_shifts")
     * @ORM\JoinColumn(name="receiver", referencedColumnName="id", onDelete="CASCADE")
     */
    private $receiver;

    /**
     * @ORM\ManyToOne(targetEntity="Beneficiary", inversedBy="given_shifts")
     * @ORM\JoinColumn(name="giver", referencedColumnName="id", onDelete="CASCADE")
     */
    private $giver;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string")
     */
    private $status;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return ShiftExchange
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set shift
     *
     * @param \AppBundle\Entity\Event $event
     *
     * @return ShiftExchange
     */
    public function setShift(\AppBundle\Entity\Shift $shift = null)
    {
        $this->shift = $shift;

        return $this;
    }

    /**
     * Get shift
     *
     * @return \AppBundle\Entity\Shift
     */
    public function getShift()
    {
        return $this->shift;
    }

    /**
     * Set receiver
     *
     * @param \AppBundle\Entity\Beneficiary $receiver
     *
     * @return ShiftExchange
     */
    public function setReceiver(Beneficiary $receiver = null)
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Get receiver
     *
     * @return \AppBundle\Entity\Beneficiary
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * Set giver
     *
     * @param \AppBundle\Entity\Beneficiary $giver
     *
     * @return ShiftExchange
     */
    public function setGiver(Beneficiary $giver = null)
    {
        $this->giver = $giver;

        return $this;
    }

    /**
     * Get giver
     *
     * @return \AppBundle\Entity\Beneficiary
     */
    public function getGiver()
    {
        return $this->giver;
    }

    /**
     * Set status
     *
     * @param string status
     *
     * @return ShiftExchange
     */
    public function setStatus(string $status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}
