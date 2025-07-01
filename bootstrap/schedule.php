<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('proforma:expire')
    ->daily()
    ->at('00:00')
    ->description('Expire proforma invoices that have passed their valid_until date');
