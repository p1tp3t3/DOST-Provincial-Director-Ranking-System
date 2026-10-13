<?php

use App\Services\KpiEntryService;
use App\Services\RankingService;

// Pure helpers behind scoring and the quarterly roll-up.

describe('RankingService::parseNumber', function () {
    it('parses the formats found in legacy KPI data', function ($raw, $expected) {
        expect(RankingService::parseNumber($raw))->toBe($expected);
    })->with([
        'integer'                => ['12', 12.0],
        'decimal'                => ['12.5', 12.5],
        'thousands separators'   => ['1,250,000', 1250000.0],
        'negative'               => ['-3', -3.0],
        'padded'                 => ['  7 ', 7.0],
        'compound breakdown'     => ["3 - extension\n2 - public good", 5.0],
        'keyword: value'         => ['MOA: 4', 4.0],
        'null'                   => [null, null],
        'empty'                  => ['', null],
        'dash'                   => ['-', null],
        'n/a'                    => ['N/A', null],
        'words only'             => ['none', null],
    ]);
});

describe('KpiEntryService::combine', function () {
    it('adds the quarters for sum', function () {
        expect(KpiEntryService::combine([1 => 10, 2 => 20, 3 => null, 4 => 5], 'sum'))->toBe(35.0);
    });

    it('takes the last quarter with a value for latest', function () {
        expect(KpiEntryService::combine([1 => 10, 2 => 7, 3 => null, 4 => null], 'latest'))->toBe(7.0)
            ->and(KpiEntryService::combine([1 => 10, 2 => null, 3 => 4, 4 => null], 'latest'))->toBe(4.0);
    });

    it('averages only the quarters with a value', function () {
        expect(KpiEntryService::combine([1 => 4, 2 => 5, 3 => null, 4 => null], 'average'))->toBe(4.5);
    });

    it('counts a zero as a value', function () {
        expect(KpiEntryService::combine([1 => 0, 2 => null], 'sum'))->toBe(0.0)
            ->and(KpiEntryService::combine([1 => 6, 2 => 0], 'average'))->toBe(3.0)
            ->and(KpiEntryService::combine([1 => 6, 2 => 0], 'latest'))->toBe(0.0);
    });

    it('returns null when no quarter has a value', function ($rule) {
        expect(KpiEntryService::combine([1 => null, 2 => null], $rule))->toBeNull()
            ->and(KpiEntryService::combine([], $rule))->toBeNull();
    })->with(['sum', 'latest', 'average']);

    it('treats an unknown rule as sum', function () {
        expect(KpiEntryService::combine([1 => 1, 2 => 2], 'whatever'))->toBe(3.0);
    });
});

describe('KpiEntryService::toNumberString', function () {
    it('formats numbers without trailing zeros', function ($value, $expected) {
        expect(KpiEntryService::toNumberString($value))->toBe($expected);
    })->with([
        [3, '3'],
        [3.0, '3'],
        [12.5, '12.5'],
        ['12.50', '12.5'],
        [0, '0'],
        [1234567.125, '1234567.125'],
        [null, null],
        ['', null],
    ]);
});

describe('KpiEntryService::quarterOf', function () {
    it('maps months to quarters', function ($date, $quarter) {
        expect(KpiEntryService::quarterOf($date))->toBe($quarter);
    })->with([
        ['2026-01-01', 1], ['2026-03-31', 1],
        ['2026-04-01', 2], ['2026-06-30', 2],
        ['2026-07-01', 3], ['2026-09-30', 3],
        ['2026-10-01', 4], ['2026-12-31', 4],
    ]);
});
