<?php

namespace app\internal\house\domain\entity;

use app\domain\Entity;
use app\domain\vo\EntranceFlatRange;
use app\internal\house\domain\vo\EntranceGate;
use app\internal\device\domain\entity\RelayEntity;

/**
 * Вход
 */
class EntranceEntity extends Entity
{
    /** @var string|null Uid */
    private $uid;
    /** @var string Uid дома */
    private $houseUid;
    /** @var int Ид покупателя */
    private $buyerId;
    /** @var string Название входа */
    private $title;
    /** @var int Номер входа */
    private $entranceNum;
    /** @var int Тип входа */
    private $type;
    /** @var bool Не отвязывать при авторизации */
    private $notUnbindUser;
    /** @var EntranceFlatRange Номер входа */
    private $range;
    /** @var RelayEntity[] Реле устройств, к которым привязан вход */
    private $relays = [];
    /** @var EntranceGate[] Входы привязанные к калитке */
    private $gates = [];

    /**
     * @param string|null $uid
     * @param string $houseUid
     * @param int $buyerId
     */
    public function __construct(?string $uid, string $houseUid, ?int $buyerId = null)
    {
        $this->uid = $uid;
        $this->houseUid = $houseUid;
        $this->buyerId = $buyerId;
    }

    /** {@inheritDoc} */
    public function getId()
    {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getHouseUid(): string
    {
        return $this->houseUid;
    }

    /**
     * @return int
     */
    public function getBuyerId(): ?int
    {
        return $this->buyerId;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getEntranceNum(): ?int
    {
        return $this->entranceNum;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @param int $entranceNum
     */
    public function setEntranceNum(?int $entranceNum): void
    {
        $this->entranceNum = $entranceNum;
    }

    /**
     * @return EntranceFlatRange
     */
    public function getRange(): ?EntranceFlatRange
    {
        return $this->range;
    }

    /**
     * @param EntranceFlatRange $range
     */
    public function setRange(EntranceFlatRange $range): void
    {
        $this->range = $range;
    }

    /**
     * @return string|null
     */
    public function getUid(): ?string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     */
    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * @return int
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return RelayEntity[]
     */
    public function getRelays(): array
    {
        return $this->relays;
    }

    /**
     * @param RelayEntity $relay
     */
    public function addRelay(RelayEntity $relay): void
    {
        $this->relays[] = $relay;
    }

    /**
     * @param RelayEntity[] $relays
     */
    public function setRelays(array $relays): void
    {
        $this->relays = $relays;
    }

    /**
     * @return EntranceGate[]
     */
    public function getGates(): array
    {
        return $this->gates;
    }

    /**
     * @param EntranceGate[] $gates
     */
    public function setGates(array $gates): void
    {
        $this->gates = $gates;
    }

    /**
     * @return int
     */
    public function getNotUnbindUser(): bool
    {
        return (bool)$this->notUnbindUser;
    }

    /**
     * @param bool $notUnbindUser
     */
    public function setNotUnbindUser(bool $notUnbindUser): void
    {
        $this->notUnbindUser = $notUnbindUser;
    }

    /** {@inheritDoc} */
    public function fields()
    {
        return ['id', 'houseUid', 'buyerId', 'title', 'entranceNum', 'type', 'range', 'relays', 'gates', 'notUnbindUser'];
    }
}