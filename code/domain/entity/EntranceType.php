<?php

namespace app\internal\house\domain\entity;

class EntranceType
{
    /** @var int Основной вход в подъезд */
    public const MAIN = 1;
    /** @var int Калитка с префиксом */
    public const GATE_WITH_PREFIX = 2;
    /** @var int Дополнительный вход */
    public const ADDITIONAL = 3;
    /** @var int Калитка без префикса */
    public const GATE_WITHOUT_PREFIX = 4;

    /** @var array тип-название входа */
    private const NAMES_LIST = [
        self::MAIN => 'Подъезд',
        self::GATE_WITH_PREFIX => 'Калитка',
        self::ADDITIONAL => 'Дополнительный вход',
        self::GATE_WITHOUT_PREFIX => 'Калитка (без префикса)'
    ];

    /**
     * Получить название типа входа
     *
     * @param int $entranceType
     * @return string
     */
    public static function name(int $entranceType): string
    {
        return self::NAMES_LIST[$entranceType] ?? 'Неизвестный тип входа';
    }

    /**
     * Список названий типов входов
     *
     * @return array
     */
    public static function namesList(): array
    {
        return self::NAMES_LIST ?? [];
    }
}