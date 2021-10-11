<?php

namespace app\infrastructure\repository;

use app\internal\house\domain\entity\EntranceEntity;
use app\internal\house\domain\entity\EntranceType;
use app\domain\exception\NotFoundException;
use app\domain\factory\EntranceFactory;
use app\internal\house\domain\repository\EntranceRepositoryInterface;
use app\domain\SearchCriteria;
use app\internal\house\domain\vo\EntranceGate;
use app\models\db\Entrances;
use app\models\db\EntrancesDevices;
use app\models\db\EntrancesFlats;
use app\models\db\EntrancesGate;
use app\models\db\EntrancesRange;
use app\models\UuidHelper;
use yii\db\ActiveQuery;
use yii\db\Exception;

/**
 * Реализация репозитория входов
 */
class EntranceRepository implements EntranceRepositoryInterface
{
    /** @var EntranceSaver */
    private $entraceSaver;
    /** @var EntranceFinder */
    private $entraceFinder;

    /**
     * EntranceRepository constructor.
     * @param EntranceSaver $entraceSaver
     * @param EntranceFinder $entraceFinder
     */
    public function __construct(EntranceSaver $entraceSaver, EntranceFinder $entraceFinder)
    {
        $this->entraceSaver = $entraceSaver;
        $this->entraceFinder = $entraceFinder;
    }

    /** {@inheritDoc} */
    public function find(SearchCriteria $criteria): array
    {
        if (!array_filter($criteria->getAll())) {
            return [];
        }

        return $this->entraceFinder->find($criteria);
    }

    /** {@inheritDoc} */
    public function findById(string $uid): ?EntranceEntity
    {
        return $this->entraceFinder->findById($uid);
    }

    /** {@inheritDoc} */
    public function findByHouseUidAndEntranceNum(string $houseUid, int $entranceNum): ?EntranceEntity
    {
        return $this->entraceFinder->findByHouseUidAndEntranceNum($houseUid, $entranceNum);
    }

    /** {@inheritDoc} */
    public function save(EntranceEntity $entity): void
    {
        $entrance = Entrances::findOne(['entranceUid' => $entity->getId()]) ?? new Entrances();
        $this->entraceSaver->save($entrance, $entity);
    }

    /**
     * @param string $houseUid
     * @param int $flatNum
     * @return EntranceEntity[]
     */
    public function findByRange(string $houseUid, int $flatNum): array
    {
        return $this->entraceFinder->findByRange($houseUid, $flatNum);
    }

    /**
     * Находит входы у калитки
     *
     * @param string $gateEntranceUid
     * @return array
     */
    public function findGateEntrances(string $gateEntranceUid): array
    {
        return $this->entraceFinder->findGateEntrances($gateEntranceUid);
    }

    /** {@inheritDoc} */
    public function delete(string $uid): void
    {
        $entrance = Entrances::find()->where(['entranceUid' => $uid])->one();

        if ($entrance) {
            if (!$entrance->delete()) {
                throw new \RuntimeException('Не удалось удалить вход.');
            }

            EntrancesGate::deleteAll(['gateUid' => $entrance]);
            EntrancesFlats::deleteAll(['entranceUid' => $entrance]);
            EntrancesDevices::deleteAll(['entranceUid' => $entrance]);
        }
    }
}
