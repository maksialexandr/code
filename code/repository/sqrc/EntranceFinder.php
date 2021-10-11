<?php

namespace app\infrastructure\repository;

use app\domain\factory\EntranceFactory;
use app\domain\SearchCriteria;
use app\internal\house\domain\entity\EntranceEntity;
use app\internal\house\domain\entity\EntranceType;
use app\models\db\Entrances;
use app\models\db\EntrancesGate;
use app\models\db\EntrancesRange;
use yii\db\ActiveQuery;
use yii\db\Exception;

/**
 * Класс для поиска входов
 */
class EntranceFinder
{
    /**
     * @param SearchCriteria $criteria
     * @return array
     */
    public function find(SearchCriteria $criteria)
    {
        $query = $this->createQuery($criteria);

        $result = [];
        foreach ($query->asArray()->all() as $row) {
            if (isset($row['entranceUid'])) {
                /** @var EntrancesRange $entranceRange */
                $entranceRange = EntrancesRange::find()->where(['entranceUid' => $row['entranceUid']])->one();
                if ($entranceRange && $entranceRange->start && $entranceRange->end) {
                    $row['range'] = ['start' => $entranceRange->start, 'end' => $entranceRange->end];
                }

                $row['relays'] = $this->findRelays($row['entranceUid']);
                $row['gates'] = $this->findGates($row['entranceUid']);
            }
            $entrance = EntranceFactory::create($row);
            $entrance->setIsNewRecord(false);
            $result[] = $entrance;
        }

        return $result;
    }

    /**
     * @param string $uid
     * @return \app\internal\house\domain\entity\EntranceEntity|null
     */
    public function findById(string $uid)
    {
        $entrance = Entrances::find()->where(['entranceUid' => $uid])->asArray()->one();

        if (!$entrance) {
            return null;
        }

        /** @var EntrancesRange $entranceRange */
        $entranceRange = EntrancesRange::find()->where(['entranceUid' => $uid])->one();
        if ($entranceRange && $entranceRange->start && $entranceRange->end) {
            $entrance['range'] = ['start' => $entranceRange->start, 'end' => $entranceRange->end];
        }
        $entrance['relays'] = $this->findRelays($uid);
        $entrance['gates'] = $this->findGates($uid);

        $entranceEntity = EntranceFactory::create($entrance);
        $entranceEntity->setIsNewRecord(false);

        return $entranceEntity;
    }

    /**
     * @param string $houseUid
     * @param int $entranceNum
     * @return EntranceEntity
     */
    public function findByHouseUidAndEntranceNum(string $houseUid, int $entranceNum): ?EntranceEntity
    {
        if (!$row = Entrances::find()->where(
            [
                'houseUid' => $houseUid,
                'entranceNum' => $entranceNum,
                'entranceType' => EntranceType::MAIN
            ]
        )->asArray()->one()) {
            return null;
        }
        $entrance = EntranceFactory::create($row);
        $entrance->setIsNewRecord(false);
        return $entrance;
    }

    /**
     * @param SearchCriteria $criteria
     * @return ActiveQuery
     */
    private function createQuery(SearchCriteria $criteria): ActiveQuery
    {
        $query = Entrances::find();

        $params = [];
        if ($criteria->get('houseUid')) {
            $params['houseUid'] = $criteria->get('houseUid');
        }
        if ($criteria->get('buyerId')) {
            $params['buyerId'] = $criteria->get('buyerId');
        }
        if ($criteria->get('entranceUid')) {
            $params['entranceUid'] = $criteria->get('entranceUid');
        }

        if ($criteria->get('mac')) {
            $query
                ->select('*')
                ->from('entrances e')
                ->join('inner join', 'entrances_devices ed', 'ed.entranceUid = e.entranceUid')
                ->where(['ed.mac' => $criteria->get('mac')]);
            return $query;
        }

        $query->where($params);

        return $query;
    }

    /**
     * @param string $gateEntranceUid
     * @return array
     */
    public function findGateEntrances(string $gateEntranceUid)
    {
        /** @var Entrances $entrance */
        $entrance = Entrances::find()->where(['entranceUid' => $gateEntranceUid, 'entranceType' => [EntranceType::GATE_WITH_PREFIX, EntranceType::GATE_WITHOUT_PREFIX]])->one();

        if (!$entrance) {
            return [];
        }

        $data = Entrances::find()
            ->select(['e.*', 'eg.rangeStart as start', 'eg.rangeStop as end'])
            ->from('entrances e')
            ->join('join', 'entrances_gate eg', 'eg.entranceUid = e.entranceUid')
            ->where(['eg.gateUid' => $gateEntranceUid])
            ->asArray();

        $result = [];
        foreach ($data->all() as $row) {
            $entrance = EntranceFactory::create(array_merge($row, [
                'range' => [
                    'start' => $row['start'],
                    'end' => $row['end']
                ],
                'relays' => $this->findRelays($row['entranceUid'])
            ]));
            $entrance->setIsNewRecord(false);
            $result[] = $entrance;
        }

        return $result;
    }

    /**
     * Поиск входов по дому и квартире, в диапазоне
     *
     * @param string $houseUid
     * @param int $flatNum
     * @return EntranceEntity[]
     */
    public function findByRange(string $houseUid, int $flatNum): array
    {
        if (!$rows = Entrances::find()->where(
            [
                'houseUid' => $houseUid,
                'entranceType' => EntranceType::MAIN
            ]
        )->asArray()->all()) {
            return [];
        }

        $ranges = [];
        foreach ($rows as $row) {
            if (!$range = EntrancesRange::find()
                ->where(['entranceUid' => $row['entranceUid']])
                ->andWhere(['<=', 'start', $flatNum])
                ->andWhere(['>=', 'end', $flatNum])
                ->asArray()
                ->all()
            ) {
                continue;
            }
            $entrance = EntranceFactory::create($row);
            $entrance->setIsNewRecord(false);
            $ranges[] = $entrance;

            if (!$gate = EntrancesGate::find()
                ->where(['entranceUid' => $row['entranceUid']])
                ->andWhere(['<=', 'rangeStart', $flatNum])
                ->andWhere(['>=', 'rangeStop', $flatNum])
                ->asArray()
                ->one()) {
                continue;
            }

            if (!$entranceGate = Entrances::find()->where(
                [
                    'entranceUid' => $gate['gateUid'],
                ]
            )->asArray()->one()) {
                continue;
            }

            $entranceGate = EntranceFactory::create($entranceGate);
            $entranceGate->setIsNewRecord(false);
            $ranges[] = $entranceGate;
        }
        return $ranges;
    }

    /**
     * Найти реле для входа
     *
     * @param string $entranceUid
     * @return array
     */
    private function findRelays(string $entranceUid): array
    {
        try {
            $data = \Yii::$app->db->createCommand('
                    SELECT r.RELAY_ID, r.STATUS_CODE, d.MAC, ed.entranceUid 
                    FROM td.relay r
                    JOIN td.device d ON d.DEVICE_ID = r.DEVICE_ID
                    LEFT JOIN crm.entrances_devices ed ON ed.mac = d.MAC AND r.RELAY_ID = ed.relayId
                    WHERE ed.entranceUid = :entranceUid
                ')
                ->bindValue('entranceUid', $entranceUid)
                ->queryAll();
        } catch (Exception $e) {
            return [];
        }

        $relays = [];
        foreach ($data as $relay) {
            $relays[] = [
                'id' => $relay['RELAY_ID'],
                'mac' => $relay['MAC'],
                'entrance' => $relay['entranceUid'],
                'statusCode' => $relay['STATUS_CODE']
            ];
        }
        return $relays;
    }

    /**
     * Найти реле для входа
     *
     * @param string $entranceUid
     * @return array
     */
    private function findGates(string $entranceUid): array
    {
        $entrancesGates = EntrancesGate::findAll(['gateUid' => $entranceUid]);

        $data = [];

        foreach ($entrancesGates as $gate) {
            $data[] = [
                'entranceUid' => $gate->entranceUid,
                'from' => $gate->rangeStart,
                'to' => $gate->rangeStop,
                'prefix' => $gate->prefix ?? '',
                'additionalFlats' => $gate->additionFlats,
            ];
        }

        return $data;
    }
}