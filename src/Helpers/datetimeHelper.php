<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

if (!function_exists('getDiffBetweenTwoDate')) {
	function getDiffBetweenTwoDate($startDate, $endDate): int
	{
		$datetime1 = new DateTime(getValidStrDate($startDate));
		$datetime2 = new DateTime(getValidStrDate($endDate));
		$interval = $datetime1->diff($datetime2);
		$days = $interval->format('%a');
		return intval($days);
	}
}
if (!function_exists('getDiffInDays')) {
	function getDiffInDays($startDate, $endDate): int
	{
		try {
			$datetime1 = new DateTime(getValidStrDate($startDate));
			$datetime2 = new DateTime(getValidStrDate($endDate));
			$interval = $datetime1->diff($datetime2);
			$days = $interval->format('%a');
			return intval($days);
		} catch (\Throwable $th) {
			report($th);
		}
		return -1;
	}
}
if (!function_exists('getPassedDaysFromNow')) {
	function getPassedDaysFromNow($endDate): int
	{
		try {
			$datetime1 = new DateTime();
			$datetime2 = new DateTime(getValidStrDate($endDate));
			$interval = $datetime1->diff($datetime2);
			$days = $interval->format('%a');
			return intval($days);
		} catch (\Throwable $th) {
			report($th);
		}
		return -1;
	}
}
if (!function_exists('getDiffBetweenTwoDateIMinute')) {
	function getDiffBetweenTwoDateIMinute($startDate, $endDate)
	{
		if (is_null($startDate) || is_null($endDate)) {
			return 0;
		}
		$to = Carbon::createFromFormat('Y-m-d H:i:s', getValidStrDate($endDate));
		$from = Carbon::createFromFormat('Y-m-d H:i:s', getValidStrDate($startDate));
		return  $to->diffInMinutes($from);
	}
}
if (!function_exists('getDiffinSeconds')) {
	function getDiffinSeconds($created_date)
	{
		return Carbon::now()->diffInSeconds(Carbon::parse(getValidStrDate($created_date)));
	}
}
if (!function_exists('getCurrentDate')) {
	function getCurrentDate(): string
	{
		//return Carbon::now()->toDateString();
		// dd([Carbon::now()->toDateString(), date('Y-m-d')]);
		return date('Y-m-d');
	}
}
if (!function_exists('getValidStrDate')) {
	function getValidStrDate($date, $def_date = 'now')
	{
		try {
			if (m_empty($date)) {
				return $def_date;
			} elseif (is_numeric($date)) {
				//2024/01/01
				$lngth = strlen(strval($date));
				if ($lngth >= 4) {
					$date = 	Carbon::parse($date)->format('Y-m-d');
				}
				if ($lngth == 4) {
					$date = sprintf("%s-01-01", $date);
				} elseif ($lngth < 4) {
					$date = (m_empty($def_date)) ?	$def_date : Carbon::parse($date)->format('Y-m-d H:i:s');
				}
				return $date;
			} elseif (is_string($date)) {
				$date = str_replace(" -", '-', $date);
				$date = str_replace("- ", '-', $date);
				$date = str_replace("\\", '/', $date);
				$splits = explode(" ", $date);
				if (count($splits) > 2) {
					$date =	Str::replaceFirst(" ", "", $date);
					$splits = explode(" ", $date);
				}

				$dateArr = explode("/", $splits[0]);
				$day = strval($dateArr[0]);
				$year = strval(end($dateArr));
				if (strlen($day) <= 2 && strlen($year) == 4) {
					$month = count($dateArr) == 3 ? $dateArr[count($dateArr) - 2] : '01';
					$date = sprintf("%s-%s-%s", $year,	$month, $day);
					if (count($splits) > 1) {
						$date .= " " . end($splits);
					}
				}
				/* if (validateDateFormat($date, 'd-m-Y')) {
				$date =	\App\Utilities\Date::createFromFormatWithCurrentLocale('Y-m-d H:i:s', $date);
			} */
				if (validateDateFormat($date, 'Y-m-d')) {
					return 	$date;
				}
				//	$date = 	Carbon::parse($date)->format('Y-m-d H:i:s');
				if (validateDateFormat($date, 'Y-m-d H:i:s')) {
					return $date;
				}
				$date = (m_empty($def_date)) ?	$def_date : Carbon::parse($def_date)->format('Y-m-d H:i:s');
				//\Carbon/Traits/Creator::class;
				//strlen(trim($date))
			} elseif (($date instanceof DateTime) || ($date instanceof Carbon)) {
				if ($date->format('H:i:s') === '00:00:00') {
					return	$date->format('Y-m-d');
				}
				return	$date->format('Y-m-d H:i:s');
			}
		} catch (\Throwable $th) {
			report($th);
		}
		return $date;
	}
}
function validateDateFormat(&$date, $format = 'Y-m-d'): bool
{
	try {
		$date = transArDateToEn($date);
		while (str_contains((string) $date, " -") || str_contains((string) $date, "- ")) {
			$date = str_replace([" -", "- "], ['-', "-"], $date);
		}
		$dateTime = DateTime::createFromFormat($format, (new DateTime($date))->format($format));
		if (is_bool($dateTime)) {
			return $dateTime;
		}
		$dateformat = str_contains((string) $format, 'H:i:s') ? 'Y-m-d H:i:s' : "Y-m-d";
		return $dateTime->format($format) === (new DateTime($date))->format($dateformat);
	} catch (\Throwable $th) {
		//logger('validateDateFormat', ['date' => $date, 'format' => $format]);
		mx_report($th, ['date' => $date, 'format' => $format]);
		return false;
	}
}
function transArDateToEn(&$date): array|string
{
	$list = ["٠" => "0", "١" => "1", "٢" => "2", "٣" => "3", "٤" => "4", "٥" => "5", "٦" => "6", "٧" => "7", "٨" => "8", "٩" => "9",];
	foreach ($list as $key => $value) {
		$date = str_replace($key, $value, $date);
	}
	return $date;
}
function validateDate($date, $format = 'Y-m-d')
{
	try {
		$dateTime = DateTime::createFromFormat($format, ($date));
		return $dateTime && $dateTime->format($format) === $date;
	} catch (\Throwable $th) {
		report($th);
		return false;
	}
}
if (!function_exists('current_date')) {
	function current_date()
	{
		//return Carbon::now()->toDateString();
		// dd([Carbon::now()->toDateString(), date('Y-m-d')]);
		return format_date("now");
	}
}
if (!function_exists('get_doc_date')) {
	function get_doc_date($item, $def_date = '')
	{
		if ($item != null) {
			return format_date($item->doc_date);
		}
		return old('doc_date', is_null($def_date) ? current_date() : $def_date);
	}
}
if (!function_exists('format_carbon_date')) {
	function format_carbon_date($date, $def_date = null)
	{
		if (m_empty($date)) {
			return $def_date;
		}
		$dateformat = "Y-m-d";
		return Carbon::parse(getValidStrDate($date))->format($dateformat);
	}
}
if (!function_exists('to_carbon_date')) {
	function to_carbon_date($date, $def_date = null): Carbon|null
	{
		if (m_empty($date)) {
			if (!m_empty($def_date)) {
				return to_carbon_date($def_date);
			}
			return $def_date;
		}
		if ($date instanceof Carbon) {
			return $date;
		}
		return Carbon::parse(getValidStrDate($date));
	}
}
if (!function_exists('format_api_date')) {
	function format_api_date($date): ?string
	{
		if (m_empty($date)) {
			return null;
		}
		$dateformat = "Y-m-d";
		$datetime = new DateTime(getValidStrDate($date));
		return $datetime->format($dateformat);
	}
}
if (!function_exists('format_date')) {
	function format_date($date, $dateformat = "Y-m-d")
	{
		if (is_null($date)) {
			return $date;
		}
		try {
			$datetime = ($date instanceof DateTime) ? $date : new DateTime(getValidStrDate($date));
			return $datetime->format($dateformat);
		} catch (\Throwable $th) {
			report($th);
		}
		return $date;
	}
}
if (!function_exists('format_time')) {
	function format_time($date)
	{
		if (m_empty($date)) {
			return $date;
		}
		$dateformat = "H:i:s";
		$datetime = new DateTime(getValidStrDate($date));
		return $datetime->format($dateformat);
	}
}
if (!function_exists('get_time')) {
	function get_time($date = "now"): array
	{
		$d = new DateTime($date);
		return [
			'hour' => intval($d->format("H")),
			'minute' =>  intval($d->format("i")),
			'second' =>  intval($d->format("s")),
		];
	}
}
if (!function_exists('get_month')) {
	function get_month($date = "now"): int
	{
		$d = new DateTime($date);
		return intval($d->format("m"));
	}
}
if (!function_exists('add_years')) {
	function add_years($years)
	{
		$date =	Carbon::now()->addYears($years);
		return $date->format('Y-m-d');
	}
}
if (!function_exists('sub_years')) {
	function sub_years($years)
	{
		$date =	Carbon::now()->subYears($years);
		return $date->format('Y-m-d');
	}
}
if (!function_exists('add_months')) {
	function add_months($months)
	{
		$date =	Carbon::now()->addMonths($months);
		return $date->format('Y-m-d');
	}
}
if (!function_exists('tomorrow_date')) {
	function tomorrow_date()
	{
		//new DateTime('-1 days');
		//new DateTime('+1 hour')
		return format_date('tomorrow');
	}
}
if (!function_exists('add_days_date')) {
	function add_days_date($days, $date = null)
	{
		if (is_null($date)) {
			$date = current_date();
		}
		$date = Carbon::createFromFormat('Y-m-d', $date);
		$date->addDays($days);
		//new DateTime('-1 days');
		//new DateTime('+1 hour')
		return $date->format('Y-m-d');
	}
}
if (!function_exists('first_date')) {
	function first_date()
	{
		$year = getLoginYear();
		return format_date($year . '/01/01');
	}
}

if (!function_exists('getLoginYear')) {
	function getLoginYear()
	{

		return  date('Y');
	}
}


if (!function_exists('get_years_in')) {
	/**
	 * @return list<int>
	 */
	function get_years_in($min_year = 2023, $max_year = null): array
	{
		$min_year = intval($min_year);
		if (m_empty($max_year)) {
			$max_year = date('Y');
		}
		$max_year = intval($max_year);
		$list = [];
		for ($year = $max_year; $year >= $min_year; $year--) {
			$list[] = $year;
		}
		return $list;
	}
}
function getSeconds($hours, $mins, $secs): int|float
{
	return ($hours * 60 * 60) + ($mins * 60) + ($secs);
}
