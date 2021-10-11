<?php

namespace app\internal\house\domain\repository;

use app\internal\house\domain\entity\EntranceEntity;
use app\domain\exception\DomainException;
use app\domain\exception\NotFoundException;
use app\domain\SearchCriteria;

/**
 * Интерфейс репозитория входов
 */
interface EntranceRepositoryInterface
{
    /**
     * Получение входа по ид
     *
     * @param string $uid
     * @return EntranceEntity|null
     */
    public function findById(string $uid): ?EntranceEntity;

    /**
     * Поиск входов
     *
     * @param SearchCriteria $criteria
     * @return EntranceEntity[]
     */
    public function find(SearchCriteria $criteria): array;

    /**
     * Сохранение входа
     *
     * @param EntranceEntity $entity
     */
    public function save(EntranceEntity $entity): void;

    /**
     * Удаление входа
     *
     * @param string $uid
     * @throws NotFoundException
     * @throws \RuntimeException
     */
    public function delete(string $uid): void;

    /**
     * Находит входы у калитки
     *
     * @param string $gateEntranceUid
     * @return EntranceEntity[]
     * @throws DomainException
     */
    public function findGateEntrances(string $gateEntranceUid): array;

    /**
     * Поиск всех номеров квартир которые привязаны ко входу
     *
     * @param string $entranceUid
     * @return string[]
     * @throws \RuntimeException
     */
    public function findFlatNums(string $entranceUid): array;

    /**
     * @param string $houseUid
     * @param int $entranceNum
     * @return EntranceEntity|null
     */
    public function findByHouseUidAndEntranceNum(string $houseUid, int $entranceNum): ?EntranceEntity;

    /**
     * @param string $houseUid
     * @param int $flatNum
     * @return array
     */
    public function findByRange(string $houseUid, int $flatNum): array;
}