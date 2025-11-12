<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 */

declare(strict_types=1);

namespace Example\Module\CurrencyRate\DTO;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * Exchange Rate Data Transfer Object
 */
class ExchangeRate
{
    private string $currency;
    private float $rate;
    private DateTimeImmutable $date;
    private string $tableName;
    private string $tableNumber;

    public function __construct(
        string $currency,
        float $rate,
        DateTimeInterface $date,
        string $tableName = '',
        string $tableNumber = ''
    ) {
        $this->currency = strtoupper($currency);
        $this->rate = $rate;
        $this->date = DateTimeImmutable::createFromInterface($date);
        $this->tableName = $tableName;
        $this->tableNumber = $tableNumber;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getTableNumber(): string
    {
        return $this->tableNumber;
    }

    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'rate' => $this->rate,
            'date' => $this->date->format('Y-m-d'),
            'table_name' => $this->tableName,
            'table_number' => $this->tableNumber,
        ];
    }
}
