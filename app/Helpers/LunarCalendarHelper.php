<?php

/*
 * This file is part of the overtrue/chinese-calendar.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Helpers;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Class Calendar.
 *
 * @author overtrue <i@overtrue.me>
 */
class LunarCalendarHelper
{
    /**
     * 农历 1900-2100 的润大小信息.
     *
     * @var array
     */
    protected $lunars = [
        0x04bd8, 0x04ae0, 0x0a570, 0x054d5, 0x0d260, 0x0d950, 0x16554, 0x056a0, 0x09ad0, 0x055d2, // 1900-1909
        0x04ae0, 0x0a5b6, 0x0a4d0, 0x0d250, 0x1d255, 0x0b540, 0x0d6a0, 0x0ada2, 0x095b0, 0x14977, // 1910-1919
        0x04970, 0x0a4b0, 0x0b4b5, 0x06a50, 0x06d40, 0x1ab54, 0x02b60, 0x09570, 0x052f2, 0x04970, // 1920-1929
        0x06566, 0x0d4a0, 0x0ea50, 0x06e95, 0x05ad0, 0x02b60, 0x186e3, 0x092e0, 0x1c8d7, 0x0c950, // 1930-1939
        0x0d4a0, 0x1d8a6, 0x0b550, 0x056a0, 0x1a5b4, 0x025d0, 0x092d0, 0x0d2b2, 0x0a950, 0x0b557, // 1940-1949
        0x06ca0, 0x0b550, 0x15355, 0x04da0, 0x0a5b0, 0x14573, 0x052b0, 0x0a9a8, 0x0e950, 0x06aa0, // 1950-1959
        0x0aea6, 0x0ab50, 0x04b60, 0x0aae4, 0x0a570, 0x05260, 0x0f263, 0x0d950, 0x05b57, 0x056a0, // 1960-1969
        0x096d0, 0x04dd5, 0x04ad0, 0x0a4d0, 0x0d4d4, 0x0d250, 0x0d558, 0x0b540, 0x0b6a0, 0x195a6, // 1970-1979
        0x095b0, 0x049b0, 0x0a974, 0x0a4b0, 0x0b27a, 0x06a50, 0x06d40, 0x0af46, 0x0ab60, 0x09570, // 1980-1989
        0x04af5, 0x04970, 0x064b0, 0x074a3, 0x0ea50, 0x06b58, 0x05ac0, 0x0ab60, 0x096d5, 0x092e0, // 1990-1999
        0x0c960, 0x0d954, 0x0d4a0, 0x0da50, 0x07552, 0x056a0, 0x0abb7, 0x025d0, 0x092d0, 0x0cab5, // 2000-2009
        0x0a950, 0x0b4a0, 0x0baa4, 0x0ad50, 0x055d9, 0x04ba0, 0x0a5b0, 0x15176, 0x052b0, 0x0a930, // 2010-2019
        0x07954, 0x06aa0, 0x0ad50, 0x05b52, 0x04b60, 0x0a6e6, 0x0a4e0, 0x0d260, 0x0ea65, 0x0d530, // 2020-2029
        0x05aa0, 0x076a3, 0x096d0, 0x04afb, 0x04ad0, 0x0a4d0, 0x1d0b6, 0x0d250, 0x0d520, 0x0dd45, // 2030-2039
        0x0b5a0, 0x056d0, 0x055b2, 0x049b0, 0x0a577, 0x0a4b0, 0x0aa50, 0x1b255, 0x06d20, 0x0ada0, // 2040-2049
        0x14b63, 0x09370, 0x049f8, 0x04970, 0x064b0, 0x168a6, 0x0ea50, 0x06b20, 0x1a6c4, 0x0aae0, // 2050-2059
        0x0a2e0, 0x0d2e3, 0x0c960, 0x0d557, 0x0d4a0, 0x0da50, 0x05d55, 0x056a0, 0x0a6d0, 0x055d4, // 2060-2069
        0x052d0, 0x0a9b8, 0x0a950, 0x0b4a0, 0x0b6a6, 0x0ad50, 0x055a0, 0x0aba4, 0x0a5b0, 0x052b0, // 2070-2079
        0x0b273, 0x06930, 0x07337, 0x06aa0, 0x0ad50, 0x14b55, 0x04b60, 0x0a570, 0x054e4, 0x0d160, // 2080-2089
        0x0e968, 0x0d520, 0x0daa0, 0x16aa6, 0x056d0, 0x04ae0, 0x0a9d4, 0x0a2d0, 0x0d150, 0x0f252, // 2090-2099
        0x0d520, // 2100
    ];

    /**
     * 公历每个月份的天数表.
     *
     * @var array
     */
    protected $solarMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    /**
     * 天干地支之天干速查表.
     *
     * @var array
     */
    protected $gan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

    /**
     * 天干地支之天干速查表 <=> 色彩.
     *
     * @var array
     */
    protected $colors = ['青', '青', '红', '红', '黄', '黄', '白', '白', '黑', '黑'];

    /**
     * 天干地支之天干速查表 <=> 五行.
     *
     * @var array
     */
    protected $wuXing = ['木', '木', '火', '火', '土', '土', '金', '金', '水', '水'];

    /**
     * 地支 <=> 五行.
     *
     * @var array
     */
    protected $zhiWuxing = ['水', '土', '木', '木', '土', '火', '火', '土', '金', '金', '土', '水'];

    /**
     * 天干地支之地支速查表.
     *
     * @var array
     */
    protected $zhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /**
     * 天干地支之地支速查表 <=> 生肖.
     *
     * @var array
     */
    protected $animals = ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'];

    /**
     * 24节气速查表.
     *
     * @var array
     */
    protected $solarTerm = [
        '小寒', '大寒', '立春', '雨水', '惊蛰', '春分',
        '清明', '谷雨', '立夏', '小满', '芒种', '夏至',
        '小暑', '大暑', '立秋', '处暑', '白露', '秋分',
        '寒露', '霜降', '立冬', '小雪', '大雪', '冬至',
    ];

    /**
     * 1900-2100 各年的 24 节气日期速查表.
     *
     * @var array
     */
    protected $solarTerms = [
        '9778397bd097c36b0b6fc9274c91aa', '97b6b97bd19801ec9210c965cc920e', '97bcf97c3598082c95f8c965cc920f',
        '97bd0b06bdb0722c965ce1cfcc920f', 'b027097bd097c36b0b6fc9274c91aa', '97b6b97bd19801ec9210c965cc920e',
        '97bcf97c359801ec95f8c965cc920f', '97bd0b06bdb0722c965ce1cfcc920f', 'b027097bd097c36b0b6fc9274c91aa',
        '97b6b97bd19801ec9210c965cc920e', '97bcf97c359801ec95f8c965cc920f', '97bd0b06bdb0722c965ce1cfcc920f',
        'b027097bd097c36b0b6fc9274c91aa', '9778397bd19801ec9210c965cc920e', '97b6b97bd19801ec95f8c965cc920f',
        '97bd09801d98082c95f8e1cfcc920f', '97bd097bd097c36b0b6fc9210c8dc2', '9778397bd197c36c9210c9274c91aa',
        '97b6b97bd19801ec95f8c965cc920e', '97bd09801d98082c95f8e1cfcc920f', '97bd097bd097c36b0b6fc9210c8dc2',
        '9778397bd097c36c9210c9274c91aa', '97b6b97bd19801ec95f8c965cc920e', '97bcf97c3598082c95f8e1cfcc920f',
        '97bd097bd097c36b0b6fc9210c8dc2', '9778397bd097c36c9210c9274c91aa', '97b6b97bd19801ec9210c965cc920e',
        '97bcf97c3598082c95f8c965cc920f', '97bd097bd097c35b0b6fc920fb0722', '9778397bd097c36b0b6fc9274c91aa',
        '97b6b97bd19801ec9210c965cc920e', '97bcf97c3598082c95f8c965cc920f', '97bd097bd097c35b0b6fc920fb0722',
        '9778397bd097c36b0b6fc9274c91aa', '97b6b97bd19801ec9210c965cc920e', '97bcf97c359801ec95f8c965cc920f',
        '97bd097bd097c35b0b6fc920fb0722', '9778397bd097c36b0b6fc9274c91aa', '97b6b97bd19801ec9210c965cc920e',
        '97bcf97c359801ec95f8c965cc920f', '97bd097bd097c35b0b6fc920fb0722', '9778397bd097c36b0b6fc9274c91aa',
        '97b6b97bd19801ec9210c965cc920e', '97bcf97c359801ec95f8c965cc920f', '97bd097bd07f595b0b6fc920fb0722',
        '9778397bd097c36b0b6fc9210c8dc2', '9778397bd19801ec9210c9274c920e', '97b6b97bd19801ec95f8c965cc920f',
        '97bd07f5307f595b0b0bc920fb0722', '7f0e397bd097c36b0b6fc9210c8dc2', '9778397bd097c36c9210c9274c920e',
        '97b6b97bd19801ec95f8c965cc920f', '97bd07f5307f595b0b0bc920fb0722', '7f0e397bd097c36b0b6fc9210c8dc2',
        '9778397bd097c36c9210c9274c91aa', '97b6b97bd19801ec9210c965cc920e', '97bd07f1487f595b0b0bc920fb0722',
        '7f0e397bd097c36b0b6fc9210c8dc2', '9778397bd097c36b0b6fc9274c91aa', '97b6b97bd19801ec9210c965cc920e',
        '97bcf7f1487f595b0b0bb0b6fb0722', '7f0e397bd097c35b0b6fc920fb0722', '9778397bd097c36b0b6fc9274c91aa',
        '97b6b97bd19801ec9210c965cc920e', '97bcf7f1487f595b0b0bb0b6fb0722', '7f0e397bd097c35b0b6fc920fb0722',
        '9778397bd097c36b0b6fc9274c91aa', '97b6b97bd19801ec9210c965cc920e', '97bcf7f1487f531b0b0bb0b6fb0722',
        '7f0e397bd097c35b0b6fc920fb0722', '9778397bd097c36b0b6fc9274c91aa', '97b6b97bd19801ec9210c965cc920e',
        '97bcf7f1487f531b0b0bb0b6fb0722', '7f0e397bd07f595b0b6fc920fb0722', '9778397bd097c36b0b6fc9274c91aa',
        '97b6b97bd19801ec9210c9274c920e', '97bcf7f0e47f531b0b0bb0b6fb0722', '7f0e397bd07f595b0b0bc920fb0722',
        '9778397bd097c36b0b6fc9210c91aa', '97b6b97bd197c36c9210c9274c920e', '97bcf7f0e47f531b0b0bb0b6fb0722',
        '7f0e397bd07f595b0b0bc920fb0722', '9778397bd097c36b0b6fc9210c8dc2', '9778397bd097c36c9210c9274c920e',
        '97b6b7f0e47f531b0723b0b6fb0722', '7f0e37f5307f595b0b0bc920fb0722', '7f0e397bd097c36b0b6fc9210c8dc2',
        '9778397bd097c36b0b70c9274c91aa', '97b6b7f0e47f531b0723b0b6fb0721', '7f0e37f1487f595b0b0bb0b6fb0722',
        '7f0e397bd097c35b0b6fc9210c8dc2', '9778397bd097c36b0b6fc9274c91aa', '97b6b7f0e47f531b0723b0b6fb0721',
        '7f0e27f1487f595b0b0bb0b6fb0722', '7f0e397bd097c35b0b6fc920fb0722', '9778397bd097c36b0b6fc9274c91aa',
        '97b6b7f0e47f531b0723b0b6fb0721', '7f0e27f1487f531b0b0bb0b6fb0722', '7f0e397bd097c35b0b6fc920fb0722',
        '9778397bd097c36b0b6fc9274c91aa', '97b6b7f0e47f531b0723b0b6fb0721', '7f0e27f1487f531b0b0bb0b6fb0722',
        '7f0e397bd097c35b0b6fc920fb0722', '9778397bd097c36b0b6fc9274c91aa', '97b6b7f0e47f531b0723b0b6fb0721',
        '7f0e27f1487f531b0b0bb0b6fb0722', '7f0e397bd07f595b0b0bc920fb0722', '9778397bd097c36b0b6fc9274c91aa',
        '97b6b7f0e47f531b0723b0787b0721', '7f0e27f0e47f531b0b0bb0b6fb0722', '7f0e397bd07f595b0b0bc920fb0722',
        '9778397bd097c36b0b6fc9210c91aa', '97b6b7f0e47f149b0723b0787b0721', '7f0e27f0e47f531b0723b0b6fb0722',
        '7f0e397bd07f595b0b0bc920fb0722', '9778397bd097c36b0b6fc9210c8dc2', '977837f0e37f149b0723b0787b0721',
        '7f07e7f0e47f531b0723b0b6fb0722', '7f0e37f5307f595b0b0bc920fb0722', '7f0e397bd097c35b0b6fc9210c8dc2',
        '977837f0e37f14998082b0787b0721', '7f07e7f0e47f531b0723b0b6fb0721', '7f0e37f1487f595b0b0bb0b6fb0722',
        '7f0e397bd097c35b0b6fc9210c8dc2', '977837f0e37f14998082b0787b06bd', '7f07e7f0e47f531b0723b0b6fb0721',
        '7f0e27f1487f531b0b0bb0b6fb0722', '7f0e397bd097c35b0b6fc920fb0722', '977837f0e37f14998082b0787b06bd',
        '7f07e7f0e47f531b0723b0b6fb0721', '7f0e27f1487f531b0b0bb0b6fb0722', '7f0e397bd097c35b0b6fc920fb0722',
        '977837f0e37f14998082b0787b06bd', '7f07e7f0e47f531b0723b0b6fb0721', '7f0e27f1487f531b0b0bb0b6fb0722',
        '7f0e397bd07f595b0b0bc920fb0722', '977837f0e37f14998082b0787b06bd', '7f07e7f0e47f531b0723b0b6fb0721',
        '7f0e27f1487f531b0b0bb0b6fb0722', '7f0e397bd07f595b0b0bc920fb0722', '977837f0e37f14998082b0787b06bd',
        '7f07e7f0e47f149b0723b0787b0721', '7f0e27f0e47f531b0b0bb0b6fb0722', '7f0e397bd07f595b0b0bc920fb0722',
        '977837f0e37f14998082b0723b06bd', '7f07e7f0e37f149b0723b0787b0721', '7f0e27f0e47f531b0723b0b6fb0722',
        '7f0e397bd07f595b0b0bc920fb0722', '977837f0e37f14898082b0723b02d5', '7ec967f0e37f14998082b0787b0721',
        '7f07e7f0e47f531b0723b0b6fb0722', '7f0e37f1487f595b0b0bb0b6fb0722', '7f0e37f0e37f14898082b0723b02d5',
        '7ec967f0e37f14998082b0787b0721', '7f07e7f0e47f531b0723b0b6fb0722', '7f0e37f1487f531b0b0bb0b6fb0722',
        '7f0e37f0e37f14898082b0723b02d5', '7ec967f0e37f14998082b0787b06bd', '7f07e7f0e47f531b0723b0b6fb0721',
        '7f0e37f1487f531b0b0bb0b6fb0722', '7f0e37f0e37f14898082b072297c35', '7ec967f0e37f14998082b0787b06bd',
        '7f07e7f0e47f531b0723b0b6fb0721', '7f0e27f1487f531b0b0bb0b6fb0722', '7f0e37f0e37f14898082b072297c35',
        '7ec967f0e37f14998082b0787b06bd', '7f07e7f0e47f531b0723b0b6fb0721', '7f0e27f1487f531b0b0bb0b6fb0722',
        '7f0e37f0e366aa89801eb072297c35', '7ec967f0e37f14998082b0787b06bd', '7f07e7f0e47f149b0723b0787b0721',
        '7f0e27f1487f531b0b0bb0b6fb0722', '7f0e37f0e366aa89801eb072297c35', '7ec967f0e37f14998082b0723b06bd',
        '7f07e7f0e47f149b0723b0787b0721', '7f0e27f0e47f531b0723b0b6fb0722', '7f0e37f0e366aa89801eb072297c35',
        '7ec967f0e37f14998082b0723b06bd', '7f07e7f0e37f14998083b0787b0721', '7f0e27f0e47f531b0723b0b6fb0722',
        '7f0e37f0e366aa89801eb072297c35', '7ec967f0e37f14898082b0723b02d5', '7f07e7f0e37f14998082b0787b0721',
        '7f07e7f0e47f531b0723b0b6fb0722', '7f0e36665b66aa89801e9808297c35', '665f67f0e37f14898082b0723b02d5',
        '7ec967f0e37f14998082b0787b0721', '7f07e7f0e47f531b0723b0b6fb0722', '7f0e36665b66a449801e9808297c35',
        '665f67f0e37f14898082b0723b02d5', '7ec967f0e37f14998082b0787b06bd', '7f07e7f0e47f531b0723b0b6fb0721',
        '7f0e36665b66a449801e9808297c35', '665f67f0e37f14898082b072297c35', '7ec967f0e37f14998082b0787b06bd',
        '7f07e7f0e47f531b0723b0b6fb0721', '7f0e26665b66a449801e9808297c35', '665f67f0e37f1489801eb072297c35',
        '7ec967f0e37f14998082b0787b06bd', '7f07e7f0e47f531b0723b0b6fb0721', '7f0e27f1487f531b0b0bb0b6fb0722',
    ];

    /**
     * 数字转中文速查表.
     *
     * @var array
     */
    protected $weekdayAlias = ['日', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十'];

    /**
     * 日期转农历称呼速查表.
     *
     * @var array
     */
    protected $dateAlias = ['初', '十', '廿', '卅'];

    /**
     * 月份转农历称呼速查表.
     *
     * @var array
     */
    protected $monthAlias = ['正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '冬', '腊'];

    /**
     * 传入阳历年月日获得详细的公历、农历信息.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     *
     * @return array
     */
    public function solar($year, $month, $day, $hour = null)
    {
        $date = $this->makeDate("{$year}-{$month}-{$day}");
        $lunar = $this->solar2lunar($year, $month, $day, $hour);
        $week = abs($date->format('w')); // 0 ~ 6 修正 星期七 为 星期日

        return array_merge(
            $lunar,
            [
                'gregorian_year' => (string) $year,
                'gregorian_month' => sprintf('%02d', $month),
                'gregorian_day' => sprintf('%02d', $day),
                'gregorian_hour' => !is_numeric($hour) || $hour < 0 || $hour > 23 ? null : sprintf('%02d', $hour),
                'week_no' => $week, // 在周日时将会传回 0
                'week_name' => '星期' . $this->weekdayAlias[$week],
                'is_today' => 0 === $this->makeDate('now')->diff($date)->days,
                'constellation' => $this->toConstellation($month, $day),
                'is_same_year' => $lunar['lunar_year'] == $year ?: false,
            ]
        );
    }

    /**
     * 传入农历年月日以及传入的月份是否闰月获得详细的公历、农历信息.
     *
     * @param int  $year        lunar year
     * @param int  $month       lunar month
     * @param int  $day         lunar day
     * @param bool $isLeapMonth lunar month is leap or not.[如果是农历闰月第四个参数赋值true即可]
     * @param int  $hour        birth hour.[0~23]
     *
     * @return array
     */
    public function lunar($year, $month, $day, $isLeapMonth = false, $hour = null)
    {
        $solar = $this->lunar2solar($year, $month, $day, $isLeapMonth);

        return $this->solar($solar['solar_year'], $solar['solar_month'], $solar['solar_day'], $hour);
    }

    /**
     * 返回农历指定年的总天数.
     *
     * @param int $year
     *
     * @return int
     */
    public function daysOfYear($year)
    {
        $sum = 348;

        for ($i = 0x8000; $i > 0x8; $i >>= 1) {
            $sum += ($this->lunars[$year - 1900] & $i) ? 1 : 0;
        }

        return $sum + $this->leapDays($year);
    }

    /**
     * 返回农历指定年的总月数.
     *
     * @param int $year
     *
     * @return int
     */
    public function monthsOfYear($year)
    {
        return 0 < $this->leapMonth($year) ? 13 : 12;
    }

    /**
     * 返回农历 y 年闰月是哪个月；若 y 年没有闰月 则返回0.
     *
     * @param int $year
     *
     * @return int
     */
    public function leapMonth($year)
    {
        // 闰字编码 \u95f0
        return $this->lunars[$year - 1900] & 0xf;
    }

    /**
     * 返回农历y年闰月的天数 若该年没有闰月则返回 0.
     *
     * @param int $year
     *
     * @return int
     */
    public function leapDays($year)
    {
        if ($this->leapMonth($year)) {
            return ($this->lunars[$year - 1900] & 0x10000) ? 30 : 29;
        }

        return 0;
    }

    /**
     * 返回农历 y 年 m 月（非闰月）的总天数，计算 m 为闰月时的天数请使用 leapDays 方法.
     *
     * @param int $year
     * @param int $month
     *
     * @return int
     */
    public function lunarDays($year, $month)
    {
        // 月份参数从 1 至 12，参数错误返回 -1
        if ($month > 12 || $month < 1) {
            return -1;
        }

        return ($this->lunars[$year - 1900] & (0x10000 >> $month)) ? 30 : 29;
    }

    /**
     * 返回公历 y 年 m 月的天数.
     *
     * @param int $year
     * @param int $month
     *
     * @return int
     */
    public function solarDays($year, $month)
    {
        // 若参数错误 返回-1
        if ($month > 12 || $month < 1) {
            return -1;
        }

        $ms = $month - 1;

        if (1 == $ms) { // 2 月份的闰平规律测算后确认返回 28 或 29
            return ((0 === $year % 4) && (0 !== $year % 100) || (0 === $year % 400)) ? 29 : 28;
        }

        return $this->solarMonth[$ms];
    }

    /**
     * 农历年份转换为干支纪年.
     *
     * @param int      $lunarYear
     * @param null|int $termIndex
     *
     * @return string
     */
    public function ganZhiYear($lunarYear, $termIndex = null)
    {
        /**
         * 据维基百科干支词条：『在西历新年后，华夏新年或干支历新年之前，则续用上一年之干支』
         * 所以干支年份应该不需要根据节气校正，为免影响现有系统，此处暂时保留原有逻辑
         * https://zh.wikipedia.org/wiki/%E5%B9%B2%E6%94%AF.
         *
         * 即使考虑节气，有的年份没有立春，有的年份有两个立春，此处逻辑仍不能处理该特殊情况
         */
        $adjust = null !== $termIndex && 3 > $termIndex ? 1 : 0;

        $ganKey = ($lunarYear + $adjust - 4) % 10;
        $zhiKey = ($lunarYear + $adjust - 4) % 12;

        return $this->gan[$ganKey] . $this->zhi[$zhiKey];
    }

    /**
     * 公历月、日判断所属星座.
     *
     * @param int $gregorianMonth
     * @param int $gregorianDay
     *
     * @return string
     */
    public function toConstellation($gregorianMonth, $gregorianDay)
    {
        $constellations = '魔羯水瓶双鱼白羊金牛双子巨蟹狮子处女天秤天蝎射手魔羯';
        $arr = [20, 19, 21, 21, 21, 22, 23, 23, 23, 23, 22, 22];

        return mb_substr(
            $constellations,
            $gregorianMonth * 2 - ($gregorianDay < $arr[$gregorianMonth - 1] ? 2 : 0),
            2,
            'UTF-8'
        );
    }

    /**
     * 传入offset偏移量返回干支.
     *
     * @param int $offset 相对甲子的偏移量
     *
     * @return string
     */
    public function toGanZhi($offset)
    {
        return $this->gan[$offset % 10] . $this->zhi[$offset % 12];
    }

    /**
     * 传入公历年获得该年第n个节气的公历日期
     *
     * @param int $year 公历年(1900-2100)；
     * @param int $no   二十四节气中的第几个节气(1~24)；从n=1(小寒)算起
     *
     * @return int
     *
     * @example
     * <pre>
     *  $_24 = $this->getTerm(1987,3) ;// _24 = 4; 意即 1987 年 2 月 4 日立春
     * </pre>
     */
    public function getTerm($year, $no)
    {
        if ($year < 1900 || $year > 2100) {
            return -1;
        }
        if ($no < 1 || $no > 24) {
            return -1;
        }
        $solarTermsOfYear = array_map('hexdec', str_split($this->solarTerms[$year - 1900], 5));
        $positions = [
            0 => [0, 1],
            1 => [1, 2],
            2 => [3, 1],
            3 => [4, 2],
        ];
        $group = intval(($no - 1) / 4);
        list($offset, $length) = $positions[($no - 1) % 4];

        return substr($solarTermsOfYear[$group], $offset, $length);
    }

    public function toChinaYear($year)
    {
        if (!is_numeric($year)) {
            throw new InvalidArgumentException("错误的年份:{$year}");
        }
        $lunarYear = '';
        $year = (string) $year;
        for ($i = 0, $l = strlen($year); $i < $l; ++$i) {
            $lunarYear .= '0' !== $year[$i] ? $this->weekdayAlias[$year[$i]] : '零';
        }

        return $lunarYear;
    }

    /**
     * 传入农历数字月份返回汉语通俗表示法.
     *
     * @param int $month
     *
     * @return string
     */
    public function toChinaMonth($month)
    {
        // 若参数错误 返回 -1
        if ($month > 12 || $month < 1) {
            throw new InvalidArgumentException("错误的月份:{$month}");
        }

        return $this->monthAlias[abs($month) - 1] . '月';
    }

    /**
     * 传入农历日期数字返回汉字表示法.
     *
     * @param int $day
     *
     * @return string
     */
    public function toChinaDay($day)
    {
        switch ($day) {
            case 10:
                return '初十';
            case 20:
                return '二十';
            case 30:
                return '三十';
            default:
                return $this->dateAlias[intval($day / 10)] . $this->weekdayAlias[$day % 10];
        }
    }

    /**
     * 年份转生肖.
     *
     * 仅能大致转换, 精确划分生肖分界线是 “立春”.
     *
     * @param int      $year
     * @param null|int $termIndex
     *
     * @return string
     */
    public function getAnimal($year, $termIndex = null)
    {
        // 认为此逻辑不需要，详情参见 ganZhiYear 相关注释
        $adjust = null !== $termIndex && 3 > $termIndex ? 1 : 0;

        $animalIndex = ($year + $adjust - 4) % 12;

        return $this->animals[$animalIndex];
    }

    /**
     * 干支转色彩.
     *
     * @param $ganZhi
     *
     * @return string
     */
    protected function getColor($ganZhi)
    {
        if (!$ganZhi) {
            return null;
        }

        $gan = substr($ganZhi, 0, 3);

        if (!$gan) {
            return null;
        }

        return $this->colors[array_search($gan, $this->gan)];
    }

    /**
     * 干支转五行.
     *
     * @param $ganZhi
     *
     * @return string
     */
    protected function getWuXing($ganZhi)
    {
        if (!$ganZhi) {
            return null;
        }

        $gan = substr($ganZhi, 0, 3);
        $zhi = substr($ganZhi, 3);

        if (!$gan || !$zhi) {
            return null;
        }

        $wGan = $this->wuXing[array_search($gan, $this->gan)];
        $wZhi = $this->zhiWuxing[array_search($zhi, $this->zhi)];

        return $wGan . $wZhi;
    }

    /**
     * 阳历转阴历.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     *
     * @return array
     */
    public function solar2lunar($year, $month, $day, $hour = null)
    {
        if (23 == $hour) {
            // 23点过后算子时，农历以子时为一天的起始
            $date = $this->makeDate("{$year}-{$month}-{$day} +1day");
        } else {
            $date = $this->makeDate("{$year}-{$month}-{$day}");
        }

        list($year, $month, $day) = explode('-', $date->format('Y-n-j'));

        // 参数区间1900.1.31~2100.12.31
        if ($year < 1900 || $year > 2100) {
            throw new InvalidArgumentException("不支持的年份:{$year}");
        }

        // 年份限定、上限
        if (1900 == $year && 1 == $month && $day < 31) {
            throw new InvalidArgumentException("不支持的日期:{$year}-{$month}-{$day}");
        }

        $offset = $this->dateDiff($date, '1900-01-31')->days;

        for ($i = 1900; $i < 2101 && $offset > 0; ++$i) {
            $daysOfYear = $this->daysOfYear($i);
            $offset -= $daysOfYear;
        }

        if ($offset < 0) {
            $offset += $daysOfYear;
            --$i;
        }

        // 农历年
        $lunarYear = $i;

        $leap = $this->leapMonth($i); // 闰哪个月
        $isLeap = false;

        // 用当年的天数 offset,逐个减去每月（农历）的天数，求出当天是本月的第几天
        for ($i = 1; $i < 13 && $offset > 0; ++$i) {
            // 闰月
            if ($leap > 0 && $i == ($leap + 1) && !$isLeap) {
                --$i;
                $isLeap = true;
                $daysOfMonth = $this->leapDays($lunarYear); // 计算农历月天数
            } else {
                $daysOfMonth = $this->lunarDays($lunarYear, $i); // 计算农历普通月天数
            }

            // 解除闰月
            if (true === $isLeap && $i == ($leap + 1)) {
                $isLeap = false;
            }

            $offset -= $daysOfMonth;
        }
        // offset为0时，并且刚才计算的月份是闰月，要校正
        if (0 === $offset && $leap > 0 && $i == $leap + 1) {
            if ($isLeap) {
                $isLeap = false;
            } else {
                $isLeap = true;
                --$i;
            }
        }

        if ($offset < 0) {
            $offset += $daysOfMonth;
            --$i;
        }

        // 农历月
        $lunarMonth = $i;

        // 农历日
        $lunarDay = $offset + 1;

        // 月柱 1900 年 1 月小寒以前为 丙子月(60进制12)
        $firstNode = $this->getTerm($year, ($month * 2 - 1)); // 返回当月「节气」为几日开始
        $secondNode = $this->getTerm($year, ($month * 2)); // 返回当月「节气」为几日开始

        // 依据 12 节气修正干支月
        $ganZhiMonth = $this->toGanZhi(($year - 1900) * 12 + $month + 11);

        if ($day >= $firstNode) {
            $ganZhiMonth = $this->toGanZhi(($year - 1900) * 12 + $month + 12);
        }

        // 获取该天的节气
        $termIndex = null;
        if ($firstNode == $day) {
            $termIndex = $month * 2 - 2;
        }

        if ($secondNode == $day) {
            $termIndex = $month * 2 - 1;
        }

        $term = null !== $termIndex ? $this->solarTerm[$termIndex] : null;

        // 日柱 当月一日与 1900/1/1 相差天数
        $dayCyclical = $this->dateDiff("{$year}-{$month}-01", '1900-01-01')->days + 10;
        $dayCyclical += $day - 1;
        $ganZhiDay = $this->toGanZhi($dayCyclical);

        // 时柱和时辰
        list($ganZhiHour, $lunarHour, $hour) = $this->ganZhiHour($hour, $dayCyclical);

        $ganZhiYear = $this->ganZhiYear($lunarYear, $termIndex);

        return [
            'lunar_year' => (string) $lunarYear,
            'lunar_month' => sprintf('%02d', $lunarMonth),
            'lunar_day' => sprintf('%02d', $lunarDay),
            'lunar_hour' => $hour,
            'lunar_year_chinese' => $this->toChinaYear($lunarYear),
            'lunar_month_chinese' => ($isLeap ? '闰' : '') . $this->toChinaMonth($lunarMonth),
            'lunar_day_chinese' => $this->toChinaDay($lunarDay),
            'lunar_hour_chinese' => $lunarHour,
            'ganzhi_year' => $ganZhiYear,
            'ganzhi_month' => $ganZhiMonth,
            'ganzhi_day' => $ganZhiDay,
            'ganzhi_hour' => $ganZhiHour,
            'wuxing_year' => $this->getWuXing($ganZhiYear),
            'wuxing_month' => $this->getWuXing($ganZhiMonth),
            'wuxing_day' => $this->getWuXing($ganZhiDay),
            'wuxing_hour' => $this->getWuXing($ganZhiHour),
            'color_year' => $this->getColor($ganZhiYear),
            'color_month' => $this->getColor($ganZhiMonth),
            'color_day' => $this->getColor($ganZhiDay),
            'color_hour' => $this->getColor($ganZhiHour),
            'animal' => $this->getAnimal($lunarYear, $termIndex),
            'term' => $term,
            'is_leap' => $isLeap,
        ];
    }

    /**
     * 阴历转阳历.
     *
     * @param int  $year
     * @param int  $month
     * @param int  $day
     * @param bool $isLeapMonth
     *
     * @return array|int
     */
    public function lunar2solar($year, $month, $day, $isLeapMonth = false)
    {
        // 参数区间 1900.1.3 1 ~2100.12.1
        $leapMonth = $this->leapMonth($year);

        // 传参要求计算该闰月公历 但该年得出的闰月与传参的月份并不同
        if ($isLeapMonth && ($leapMonth != $month)) {
            $isLeapMonth = false;
        }

        // 超出了最大极限值
        if (2100 == $year && 12 == $month && $day > 1 || 1900 == $year && 1 == $month && $day < 31) {
            return -1;
        }

        $maxDays = $days = $this->lunarDays($year, $month);

        // if month is leap, _day use leapDays method
        if ($isLeapMonth) {
            $maxDays = $this->leapDays($year, $month);
        }

        // 参数合法性效验
        if ($year < 1900 || $year > 2100 || $day > $maxDays) {
            throw new InvalidArgumentException('传入的参数不合法');
        }

        // 计算农历的时间差
        $offset = 0;

        for ($i = 1900; $i < $year; ++$i) {
            $offset += $this->daysOfYear($i);
        }

        $isAdd = false;
        for ($i = 1; $i < $month; ++$i) {
            $leap = $this->leapMonth($year);
            if (!$isAdd) { // 处理闰月
                if ($leap <= $i && $leap > 0) {
                    $offset += $this->leapDays($year);
                    $isAdd = true;
                }
            }
            $offset += $this->lunarDays($year, $i);
        }

        // 转换闰月农历 需补充该年闰月的前一个月的时差
        if ($isLeapMonth) {
            $offset += $days;
        }

        // 1900 年农历正月一日的公历时间为 1900 年 1 月 30 日 0 时 0 分 0 秒 (该时间也是本农历的最开始起始点)
        // XXX: 部分 windows 机器不支持负时间戳，所以这里就写死了,哈哈哈哈...
        $startTimestamp = -2206483200;
        $date = date('Y-m-d', ($offset + $day) * 86400 + $startTimestamp);

        list($solarYear, $solarMonth, $solarDay) = explode('-', $date);

        return [
            'solar_year' => $solarYear,
            'solar_month' => sprintf('%02d', $solarMonth),
            'solar_day' => sprintf('%02d', $solarDay),
        ];
    }

    /**
     * 获取两个日期之间的距离.
     *
     * @param string|\DateTime $date1
     * @param string|\DateTime $date2
     *
     * @return bool|\DateInterval
     */
    public function dateDiff($date1, $date2)
    {
        if (!($date1 instanceof DateTime)) {
            $date1 = $this->makeDate($date1);
        }

        if (!($date2 instanceof DateTime)) {
            $date2 = $this->makeDate($date2);
        }

        return $date1->diff($date2);
    }

    /**
     * 获取两个日期之间以年为单位的距离.
     *
     * @param array $lunar1
     * @param array $lunar2
     * @param bool  $absolute
     *
     * @return int
     */
    public function diffInYears($lunar1, $lunar2, $absolute = true)
    {
        $solar1 =
            $this->lunar2solar($lunar1['lunar_year'], $lunar1['lunar_month'], $lunar1['lunar_day'], $lunar1['is_leap']);
        $date1 = $this->makeDate("{$solar1['solar_year']}-{$solar1['solar_month']}-{$solar1['solar_day']}");

        $solar2 =
            $this->lunar2solar($lunar2['lunar_year'], $lunar2['lunar_month'], $lunar2['lunar_day'], $lunar2['is_leap']);
        $date2 = $this->makeDate("{$solar2['solar_year']}-{$solar2['solar_month']}-{$solar2['solar_day']}");

        if ($date1 < $date2) {
            $lessLunar = $lunar1;
            $greaterLunar = $lunar2;
            $changed = false;
        } else {
            $lessLunar = $lunar2;
            $greaterLunar = $lunar1;
            $changed = true;
        }

        $monthAdjustFactor = $greaterLunar['lunar_day'] >= $lessLunar['lunar_day'] ? 0 : 1;
        if ($greaterLunar['lunar_month'] == $lessLunar['lunar_month']) {
            if ($greaterLunar['is_leap'] && !$lessLunar['is_leap']) {
                $monthAdjustFactor = 0;
            } elseif (!$greaterLunar['is_leap'] && $lessLunar['is_leap']) {
                $monthAdjustFactor = 1;
            }
        }
        $yearAdjustFactor = $greaterLunar['lunar_month'] - $monthAdjustFactor >= $lessLunar['lunar_month'] ? 0 : 1;
        $diff = $greaterLunar['lunar_year'] - $yearAdjustFactor - $lessLunar['lunar_year'];

        return $absolute ? $diff : ($changed ? -1 * $diff : $diff);
    }

    /**
     * 获取两个日期之间以月为单位的距离.
     *
     * @param array $lunar1
     * @param array $lunar2
     * @param bool  $absolute
     *
     * @return int
     */
    public function diffInMonths($lunar1, $lunar2, $absolute = true)
    {
        $solar1 =
            $this->lunar2solar($lunar1['lunar_year'], $lunar1['lunar_month'], $lunar1['lunar_day'], $lunar1['is_leap']);
        $date1 = $this->makeDate("{$solar1['solar_year']}-{$solar1['solar_month']}-{$solar1['solar_day']}");

        $solar2 =
            $this->lunar2solar($lunar2['lunar_year'], $lunar2['lunar_month'], $lunar2['lunar_day'], $lunar2['is_leap']);
        $date2 = $this->makeDate("{$solar2['solar_year']}-{$solar2['solar_month']}-{$solar2['solar_day']}");

        if ($date1 < $date2) {
            $lessLunar = $lunar1;
            $greaterLunar = $lunar2;
            $changed = false;
        } else {
            $lessLunar = $lunar2;
            $greaterLunar = $lunar1;
            $changed = true;
        }

        $diff = 0;

        if ($lessLunar['lunar_year'] == $greaterLunar['lunar_year']) {
            $leapMonth = $this->leapMonth($lessLunar['lunar_year']);
            $lessLunarAdjustFactor =
                $lessLunar['is_leap'] || (0 < $leapMonth && $leapMonth < $lessLunar['lunar_month']) ? 1 : 0;
            $greaterLunarAdjustFactor =
                $greaterLunar['is_leap'] || (0 < $leapMonth && $leapMonth < $greaterLunar['lunar_month']) ? 1 : 0;
            $diff =
                $greaterLunar['lunar_month'] + $greaterLunarAdjustFactor - $lessLunar['lunar_month'] - $lessLunarAdjustFactor;
        } else {
            $lessLunarLeapMonth = $this->leapMonth($lessLunar['lunar_year']);
            $greaterLunarLeapMonth = $this->leapMonth($greaterLunar['lunar_year']);

            $lessLunarAdjustFactor =
                (!$lessLunar['is_leap'] && $lessLunarLeapMonth == $lessLunar['lunar_month']) || $lessLunarLeapMonth > $lessLunar['lunar_month'] ? 1 : 0;
            $diff += 12 + $lessLunarAdjustFactor - $lessLunar['lunar_month'];
            for ($i = $lessLunar['lunar_year'] + 1; $i < $greaterLunar['lunar_year']; ++$i) {
                $diff += $this->monthsOfYear($i);
            }
            $greaterLunarAdjustFactor =
                $greaterLunar['is_leap'] || (0 < $greaterLunarLeapMonth && $greaterLunarLeapMonth < $greaterLunar['lunar_month']) ? 1 : 0;
            $diff += $greaterLunarAdjustFactor + $greaterLunar['lunar_month'];
        }

        $diff -= $greaterLunar['lunar_day'] >= $lessLunar['lunar_day'] ? 0 : 1;

        return $absolute ? $diff : ($changed ? -1 * $diff : $diff);
    }

    /**
     * 获取两个日期之间以日为单位的距离.
     *
     * @param array $lunar1
     * @param array $lunar2
     * @param bool  $absolute
     *
     * @return int
     */
    public function diffInDays($lunar1, $lunar2, $absolute = true)
    {
        $solar1 =
            $this->lunar2solar($lunar1['lunar_year'], $lunar1['lunar_month'], $lunar1['lunar_day'], $lunar1['is_leap']);
        $date1 = $this->makeDate("{$solar1['solar_year']}-{$solar1['solar_month']}-{$solar1['solar_day']}");

        $solar2 =
            $this->lunar2solar($lunar2['lunar_year'], $lunar2['lunar_month'], $lunar2['lunar_day'], $lunar2['is_leap']);
        $date2 = $this->makeDate("{$solar2['solar_year']}-{$solar2['solar_month']}-{$solar2['solar_day']}");

        return $date1->diff($date2, $absolute)->format('%r%a');
    }

    /**
     * 增加年数.
     *
     * @param array $lunar
     * @param int   $value
     * @param bool  $overFlow
     *
     * @return array
     */
    public function addYears($lunar, $value = 1, $overFlow = true)
    {
        $newYear = $lunar['lunar_year'] + $value;
        $newMonth = $lunar['lunar_month'];
        $newDay = $lunar['lunar_day'];
        $isLeap = $lunar['is_leap'];
        $needOverFlow = false;

        $leapMonth = $this->leapMonth($newYear);
        $isLeap = $isLeap && $newMonth == $leapMonth;
        $maxDays = $isLeap ? $this->leapDays($newYear) : $this->lunarDays($newYear, $newMonth);

        if ($newDay > $maxDays) {
            if ($overFlow) {
                $newDay = 1;
                $needOverFlow = true;
            } else {
                $newDay = $maxDays;
            }
        }
        $ret = $this->lunar($newYear, $newMonth, $newDay, $isLeap);
        if ($needOverFlow) {
            $ret = $this->addMonths($ret, 1, $overFlow);
        }

        return $ret;
    }

    /**
     * 减少年数.
     *
     * @param array $lunar
     * @param int   $value
     * @param bool  $overFlow
     *
     * @return array
     */
    public function subYears($lunar, $value = 1, $overFlow = true)
    {
        return $this->addYears($lunar, -1 * $value, $overFlow);
    }

    /**
     * 增加月数.
     *
     * @param array $lunar
     * @param int   $value
     * @param bool  $overFlow
     *
     * @return array
     */
    public function addMonths($lunar, $value = 1, $overFlow = true)
    {
        if (0 > $value) {
            return $this->subMonths($lunar, -1 * $value, $overFlow);
        } else {
            $newYear = $lunar['lunar_year'];
            $newMonth = $lunar['lunar_month'];
            $newDay = $lunar['lunar_day'];
            $isLeap = $lunar['is_leap'];

            while (0 < $value) {
                $leapMonth = $this->leapMonth($newYear);
                if (0 < $leapMonth) {
                    $currentIsLeap = $isLeap;
                    $isLeap = $newMonth + $value == $leapMonth + ($isLeap ? 0 : 1);

                    if ((!$currentIsLeap && $leapMonth == $newMonth) || ($newMonth < $leapMonth && $newMonth + $value > $leapMonth)) {
                        --$value;
                    }
                } else {
                    $isLeap = false;
                }

                if (13 > $newMonth + $value) {
                    $newMonth += $value;
                    $value = 0;
                } else {
                    $value = $value + $newMonth - 13;
                    ++$newYear;
                    $newMonth = 1;
                }

                if (0 == $value) {
                    $maxDays = $isLeap ? $this->leapDays($newYear) : $this->lunarDays($newYear, $newMonth);
                    if ($newDay > $maxDays) {
                        if ($overFlow) {
                            $newDay = 1;
                            ++$value;
                        } else {
                            $newDay = $maxDays;
                        }
                    }
                }
            }

            return $this->lunar($newYear, $newMonth, $newDay, $isLeap);
        }
    }

    /**
     * 减少月数.
     *
     * @param array $lunar
     * @param int   $value
     * @param bool  $overFlow
     *
     * @return array
     */
    public function subMonths($lunar, $value = 1, $overFlow = true)
    {
        if (0 > $value) {
            return $this->addMonths($lunar, -1 * $value, $overFlow);
        } else {
            $newYear = $lunar['lunar_year'];
            $newMonth = $lunar['lunar_month'];
            $newDay = $lunar['lunar_day'];
            $isLeap = $lunar['is_leap'];
            $needOverFlow = false;

            while (0 < $value) {
                $leapMonth = $this->leapMonth($newYear);

                if (0 < $leapMonth) {
                    $isLeap = $newMonth - $value == $leapMonth;

                    if ($newMonth >= $leapMonth && $newMonth - $value < $leapMonth) {
                        --$value;
                    }
                } else {
                    $isLeap = false;
                }

                if ($newMonth > $value) {
                    $newMonth -= $value;
                    $value = 0;
                } else {
                    $value = $value - $newMonth;
                    --$newYear;
                    $newMonth = 12;
                }

                if (0 == $value) {
                    $maxDays = $isLeap ? $this->leapDays($newYear) : $this->lunarDays($newYear, $newMonth);
                    if ($newDay > $maxDays) {
                        $newDay = $maxDays;
                        $needOverFlow = $overFlow;
                    }
                }
            }

            $ret = $this->lunar($newYear, $newMonth, $newDay, $isLeap);
            if ($needOverFlow) {
                $ret = $this->addDays($ret, 1);
            }

            return $ret;
        }
    }

    /**
     * 增加天数.
     *
     * @param array $lunar
     * @param int   $value
     *
     * @return array
     */
    public function addDays($lunar, $value = 1)
    {
        $solar =
            $this->lunar2solar($lunar['lunar_year'], $lunar['lunar_month'], $lunar['lunar_day'], $lunar['is_leap']);
        $date = $this->makeDate("{$solar['solar_year']}-{$solar['solar_month']}-{$solar['solar_day']}");
        $date->modify($value . ' day');

        return $this->solar2lunar($date->format('Y'), $date->format('m'), $date->format('d'));
    }

    /**
     * 减少天数.
     *
     * @param array $lunar
     * @param int   $value
     *
     * @return array
     */
    public function subDays($lunar, $value = 1)
    {
        return $this->addDays($lunar, -1 * $value);
    }

    /**
     * 创建日期对象
     *
     * @param string $string
     * @param string $timezone
     *
     * @return \DateTime
     */
    protected function makeDate($string = 'now', $timezone = 'PRC')
    {
        return new DateTime($string, new DateTimeZone($timezone));
    }

    /**
     * 获取时柱.
     *
     * @param int $hour      0~23 小时格式
     * @param int $ganZhiDay 干支日期
     *
     * @return array
     *
     * @see https://baike.baidu.com/item/%E6%97%B6%E6%9F%B1/6274024
     */
    protected function ganZhiHour($hour, $ganZhiDay)
    {
        if (!is_numeric($hour) || $hour < 0 || $hour > 23) {
            return [null, null, null];
        }

        $zhiHour = intval(($hour + 1) / 2);
        $zhiHour = 12 === $zhiHour ? 0 : $zhiHour;

        return [
            $this->gan[($ganZhiDay % 10 % 5 * 2 + $zhiHour) % 10] . $this->zhi[$zhiHour],
            $this->zhi[$zhiHour] . '时',
            sprintf('%02d', $hour),
        ];
    }
}
