<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardExport implements WithMultipleSheets
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $cashRegisters;
    protected $userName;

    public function __construct($startDate, $endDate, $cashRegisters, $userName)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->cashRegisters = $cashRegisters;
        $this->userName = $userName;
    }

    public function sheets(): array
    {
        return [
            new CashExpensesSheet($this->startDate, $this->endDate, $this->userName),
            new CategoryExpensesSheet($this->startDate, $this->endDate, $this->cashRegisters, $this->userName),
            new ObjectExpensesSheet($this->startDate, $this->endDate, $this->cashRegisters, $this->userName),
            new ProjectExpensesSheet($this->startDate, $this->endDate, $this->userName),
        ];
    }
}