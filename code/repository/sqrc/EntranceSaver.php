<?php

namespace app\infrastructure\repository;

use app\domain\EventManager;
use app\infrastructure\events\EntranceSaveEvent;
use app\internal\device\infrastructure\PhysicalDeviceFabric;
use app\internal\house\domain\entity\EntranceEntity;
use app\internal\house\domain\entity\EntranceType;
use app\models\db\Entrances;
use app\models\db\EntrancesDevices;
use app\models\db\EntrancesGate;
use app\models\db\EntrancesRange;
use app\models\UuidHelper;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Модель для сохранения входа
 */
class EntranceSaver
{
    /**
     * @param Entrances $entrance
     * @param EntranceEntity $entranceEntity
     */
    public function save(Entrances $entrance, EntranceEntity $entranceEntity)
    {
        $this->saveEntrance($entrance, $entranceEntity);
        $this->saveRange($entrance, $entranceEntity);
        $this->saveGates($entrance, $entranceEntity);

        EventManager::instance()->addEvent(new EntranceSaveEvent($entranceEntity));
    }

    /**
     * @param Entrances $entrance
     * @param EntranceEntity $entranceEntity
     */
    private function saveEntrance(Entrances $entrance, EntranceEntity $entranceEntity)
    {
        $entrance->setAttributes([
            'entranceUid' => $entranceEntity->getId() ?? UuidHelper::getUuidV4Ex(),
            'title' => (string)$entranceEntity->getTitle(),
            'houseUid' => $entranceEntity->getHouseUid(),
            'buyerId' => $entranceEntity->getBuyerId(),
            'entranceNum' => in_array($entranceEntity->getType(), [EntranceType::MAIN, EntranceType::ADDITIONAL])
                ? $entranceEntity->getEntranceNum()
                : null,
            'entranceType' => $entranceEntity->getType(),
        ]);

        if (!$entrance->save()) {
            \Yii::error(['msg' => 'Не удалось сохранить вход', 'dbModel' => 'Entrances', 'details' => current($entrance->firstErrors)]);
            throw new \RuntimeException('Не удалось сохранить вход');
        }
    }

    /**
     * @param Entrances $entrance
     * @param EntranceEntity $entranceEntity
     */
    private function saveRange(Entrances $entrance, EntranceEntity $entranceEntity)
    {
        if (!$entranceEntity->getRange()) {
            return;
        }

        $entranceRange = EntrancesRange::findOne(['entranceUid' => $entrance->entranceUid]) ?? new EntrancesRange();
        $entranceRange->setAttributes([
            'entranceUid' => $entrance->entranceUid,
            'start' => $entranceEntity->getRange()->getStart(),
            'end' => $entranceEntity->getRange()->getEnd(),
        ]);

        if (!$entranceRange->save()) {
            \Yii::error(['msg' => 'Не удалось сохранить диапазон входа', 'dbModel' => 'Entrances', 'details' => current($entranceRange->firstErrors)]);
            throw new \RuntimeException('Не удалось сохранить диапазон входа');
        }
    }

    /**
     * @param Entrances $entrance
     * @param EntranceEntity $entranceEntity
     */
    private function saveGates(Entrances $entrance, EntranceEntity $entranceEntity)
    {
        $currentGates = ArrayHelper::index(EntrancesGate::findAll([
            'gateUid' => $entranceEntity->getId()
        ]), 'entranceUid');

        $gatesToDelete = $currentGates;
        $entranceGates = $entranceEntity->getGates();

        foreach ($entranceGates as $entranceGate) {
            $gate = $currentGates[$entranceGate->getEntranceUid()] ?? new EntrancesGate();
            $gate->setAttributes([
                'gateUid' => $entrance->entranceUid,
                'prefix' => $entranceGate->getPrefix(),
                'entranceUid' => $entranceGate->getEntranceUid(),
                'rangeStart' => $entranceGate->getFrom(),
                'rangeStop' => $entranceGate->getTo(),
                'additionFlats' => $entranceGate->getAdditionalFlats(),
            ]);

            if (!$gate->save()) {
                \Yii::error(['msg' => 'Не удалось сохранить вход', 'dbModel' => 'EntrancesGate', 'details' => current($gate->firstErrors)]);
                throw new \RuntimeException('Во время сохранения входа произошла ошибка');
            }

            unset($gatesToDelete[$entranceGate->getEntranceUid()]);
        }

        if ($gatesToDelete) {
            EntrancesGate::deleteAll(['gateUid' => $entranceEntity->getId(), 'entranceUid' => $gatesToDelete]);
        }
    }
}