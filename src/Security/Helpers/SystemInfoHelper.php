<?php

declare(strict_types=1);

namespace Maher\CoreTools\Security\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SystemInfoHelper
{
	public static $allCountries = [
		"AF" => ["en" => "Afghanistan", "ar" => "أفغانستان",],
		"AX" => ["en" => "Åland Islands", "ar" => "جزر آلاند",],
		"AL" => ["en" => "Albania", "ar" => "ألبانيا",],
		"DZ" => ["en" => "Algeria", "ar" => "الجزائر",],
		"AS" => ["en" => "American Samoa", "ar" => "ساموا الأمريكية",],
		"AD" => ["en" => "Andorra", "ar" => "أندورا",],
		"AO" => ["en" => "Angola", "ar" => "أنغولا",],
		"AI" => ["en" => "Anguilla", "ar" => "أنغويلا",],
		"AQ" => ["en" => "Antarctica", "ar" => "أنتاركتيكا",],
		"AG" => ["en" => "Antigua and Barbuda", "ar" => "أنتيغوا وباربودا",],
		"AR" => ["en" => "Argentina", "ar" => "الأرجنتين",],
		"AM" => ["en" => "Armenia", "ar" => "أرمينيا",],
		"AW" => ["en" => "Aruba", "ar" => "أروبا",],
		"AU" => ["en" => "Australia", "ar" => "أستراليا",],
		"AT" => ["en" => "Austria", "ar" => "النمسا",],
		"AZ" => ["en" => "Azerbaijan", "ar" => "أذربيجان",],
		"BS" => ["en" => "Bahamas", "ar" => "جزر البهاما",],
		"BH" => ["en" => "Bahrain", "ar" => "البحرين",],
		"BD" => ["en" => "Bangladesh", "ar" => "بنغلاديش",],
		"BB" => ["en" => "Barbados", "ar" => "بربادوس",],
		"BY" => ["en" => "Belarus", "ar" => "بيلاروسيا",],
		"BE" => ["en" => "Belgium", "ar" => "بلجيكا",],
		"BZ" => ["en" => "Belize", "ar" => "بليز",],
		"BJ" => ["en" => "Benin", "ar" => "بنن",],
		"BM" => ["en" => "Bermuda", "ar" => "برمودا",],
		"BT" => ["en" => "Bhutan", "ar" => "بوتان",],
		"BO" => ["en" => "Bolivia", "ar" => "بوليفيا",],
		"BA" => ["en" => "Bosnia and Herzegovina", "ar" => "البوسنة والهرسك",],
		"BW" => ["en" => "Botswana", "ar" => "بوتسوانا",],
		"BV" => ["en" => "Bouvet Island", "ar" => "جزيرة بوفيه",],
		"BR" => ["en" => "Brazil", "ar" => "البرازيل",],
		"IO" => ["en" => "British Indian Ocean Territory", "ar" => "إقليم المحيط الهندي البريطاني",],
		"BN" => ["en" => "Brunei Darussalam", "ar" => "بروناي",],
		"BG" => ["en" => "Bulgaria", "ar" => "بلغاريا",],
		"BF" => ["en" => "Burkina Faso", "ar" => "بوركينا فاسو",],
		"BI" => ["en" => "Burundi", "ar" => "بوروندي",],
		"KH" => ["en" => "Cambodia", "ar" => "كمبوديا",],
		"CM" => ["en" => "Cameroon", "ar" => "الكاميرون",],
		"CA" => ["en" => "Canada", "ar" => "كندا",],
		"CV" => ["en" => "Cape Verde", "ar" => "الرأس الأخضر",],
		"KY" => ["en" => "Cayman Islands", "ar" => "جزر كايمن",],
		"CF" => ["en" => "Central African Republic", "ar" => "جمهورية أفريقيا الوسطى",],
		"TD" => ["en" => "Chad", "ar" => "تشاد",],
		"CL" => ["en" => "Chile", "ar" => "تشيلي",],
		"CN" => ["en" => "China", "ar" => "الصين",],
		"CX" => ["en" => "Christmas Island", "ar" => "جزيرة الكريسماس",],
		"CC" => ["en" => "Cocos (Keeling) Islands", "ar" => "جزر كوكوس (كيلينغ)",],
		"CO" => ["en" => "Colombia", "ar" => "كولومبيا",],
		"KM" => ["en" => "Comoros", "ar" => "جزر القمر",],
		"CG" => ["en" => "Congo", "ar" => "الكونغو",],
		"CD" => ["en" => "Congo, Democratic Republic", "ar" => "جمهورية الكونغو الديمقراطية",],
		"CK" => ["en" => "Cook Islands", "ar" => "جزر كوك",],
		"CR" => ["en" => "Costa Rica", "ar" => "كوستاريكا",],
		"CI" => ["en" => "Côte d'Ivoire", "ar" => "ساحل العاج",],
		"HR" => ["en" => "Croatia", "ar" => "كرواتيا",],
		"CU" => ["en" => "Cuba", "ar" => "كوبا",],
		"CY" => ["en" => "Cyprus", "ar" => "قبرص",],
		"CZ" => ["en" => "Czech Republic", "ar" => "جمهورية التشيك",],
		"DK" => ["en" => "Denmark", "ar" => "الدنمارك",],
		"DJ" => ["en" => "Djibouti", "ar" => "جيبوتي",],
		"DM" => ["en" => "Dominica", "ar" => "دومينيكا",],
		"DO" => ["en" => "Dominican Republic", "ar" => "جمهورية الدومينيكان",],
		"EC" => ["en" => "Ecuador", "ar" => "الإكوادور",],
		"EG" => ["en" => "Egypt", "ar" => "مصر",],
		"SV" => ["en" => "El Salvador", "ar" => "السلفادور",],
		"GQ" => ["en" => "Equatorial Guinea", "ar" => "غينيا الاستوائية",],
		"ER" => ["en" => "Eritrea", "ar" => "إريتريا",],
		"EE" => ["en" => "Estonia", "ar" => "إستونيا",],
		"ET" => ["en" => "Ethiopia", "ar" => "إثيوبيا",],
		"FK" => ["en" => "Falkland Islands (Malvinas)", "ar" => "جزر فوكلاند (مالفينا)",],
		"FO" => ["en" => "Faroe Islands", "ar" => "جزر فارو",],
		"FJ" => ["en" => "Fiji", "ar" => "فيجي",],
		"FI" => ["en" => "Finland", "ar" => "فنلندا",],
		"FR" => ["en" => "France", "ar" => "فرنسا",],
		"GF" => ["en" => "French Guiana", "ar" => "غيانا الفرنسية",],
		"PF" => ["en" => "French Polynesia", "ar" => "بولينيزيا الفرنسية",],
		"TF" => ["en" => "French Southern Territories", "ar" => "الأراضي الفرنسية الجنوبية",],
		"GA" => ["en" => "Gabon", "ar" => "الغابون",],
		"GM" => ["en" => "Gambia", "ar" => "غامبيا",],
		"GE" => ["en" => "Georgia", "ar" => "جورجيا",],
		"DE" => ["en" => "Germany", "ar" => "ألمانيا",],
		"GH" => ["en" => "Ghana", "ar" => "غانا",],
		"GI" => ["en" => "Gibraltar", "ar" => "جبل طارق",],
		"GR" => ["en" => "Greece", "ar" => "اليونان",],
		"GL" => ["en" => "Greenland", "ar" => "غرينلاند",],
		"GD" => ["en" => "Grenada", "ar" => "غرينادا",],
		"GP" => ["en" => "Guadeloupe", "ar" => "غوادلوب",],
		"GU" => ["en" => "Guam", "ar" => "غوام",],
		"GT" => ["en" => "Guatemala", "ar" => "غواتيمالا",],
		"GG" => ["en" => "Guernsey", "ar" => "غيرنزي",],
		"GN" => ["en" => "Guinea", "ar" => "غينيا",],
		"GW" => ["en" => "Guinea-Bissau", "ar" => "غينيا بيساو",],
		"GY" => ["en" => "Guyana", "ar" => "غيانا",],
		"HT" => ["en" => "Haiti", "ar" => "هايتي",],
		"HM" => ["en" => "Heard Island and McDonald Islands", "ar" => "جزيرة هيرد وجزر ماكدونالد",],
		"VA" => ["en" => "Holy See (Vatican City State)", "ar" => "الفاتيكان",],
		"HN" => ["en" => "Honduras", "ar" => "هندوراس",],
		"HK" => ["en" => "Hong Kong", "ar" => "هونغ كونغ",],
		"HU" => ["en" => "Hungary", "ar" => "المجر",],
		"IS" => ["en" => "Iceland", "ar" => "أيسلندا",],
		"IN" => ["en" => "India", "ar" => "الهند",],
		"ID" => ["en" => "Indonesia", "ar" => "إندونيسيا",],
		"IR" => ["en" => "Iran, Islamic Republic of", "ar" => "إيران",],
		"IQ" => ["en" => "Iraq", "ar" => "العراق",],
		"IE" => ["en" => "Ireland", "ar" => "أيرلندا",],
		"IM" => ["en" => "Isle of Man", "ar" => "جزيرة مان",],
		"IL" => ["en" => "Israel", "ar" => "إسرائيل",],
		"IT" => ["en" => "Italy", "ar" => "إيطاليا",],
		"JM" => ["en" => "Jamaica", "ar" => "جامايكا",],
		"JP" => ["en" => "Japan", "ar" => "اليابان",],
		"JE" => ["en" => "Jersey", "ar" => "جيرسي",],
		"JO" => ["en" => "Jordan", "ar" => "الأردن",],
		"KZ" => ["en" => "Kazakhstan", "ar" => "كازاخستان",],
		"KE" => ["en" => "Kenya", "ar" => "كينيا",],
		"KI" => ["en" => "Kiribati", "ar" => "كيريباتي",],
		"KP" => ["en" => "Korea, Democratic People's Republic of", "ar" => "كوريا الشمالية",],
		"KR" => ["en" => "Korea, Republic of", "ar" => "كوريا الجنوبية",],
		"KW" => ["en" => "Kuwait", "ar" => "الكويت",],
		"KG" => ["en" => "Kyrgyzstan", "ar" => "قيرغيزستان",],
		"LA" => ["en" => "Lao People's Democratic Republic", "ar" => "لاوس",],
		"LV" => ["en" => "Latvia", "ar" => "لاتفيا",],
		"LB" => ["en" => "Lebanon", "ar" => "لبنان",],
		"LS" => ["en" => "Lesotho", "ar" => "ليسوتو",],
		"LR" => ["en" => "Liberia", "ar" => "ليبيريا",],
		"LY" => ["en" => "Libya", "ar" => "ليبيا",],
		"LI" => ["en" => "Liechtenstein", "ar" => "ليختنشتاين",],
		"LT" => ["en" => "Lithuania", "ar" => "لتوانيا",],
		"LU" => ["en" => "Luxembourg", "ar" => "لوكسمبورغ",],
		"MO" => ["en" => "Macao", "ar" => "ماكاو",],
		"MG" => ["en" => "Madagascar", "ar" => "مدغشقر",],
		"MW" => ["en" => "Malawi", "ar" => "مالاوي",],
		"MY" => ["en" => "Malaysia", "ar" => "ماليزيا",],
		"MV" => ["en" => "Maldives", "ar" => "جزر المالديف",],
		"ML" => ["en" => "Mali", "ar" => "مالي",],
		"MT" => ["en" => "Malta", "ar" => "مالطا",],
		"MH" => ["en" => "Marshall Islands", "ar" => "جزر مارشال",],
		"MQ" => ["en" => "Martinique", "ar" => "مارتينيك",],
		"MR" => ["en" => "Mauritania", "ar" => "موريتانيا",],
		"MU" => ["en" => "Mauritius", "ar" => "موريشيوس",],
		"YT" => ["en" => "Mayotte", "ar" => "مايوت",],
		"MX" => ["en" => "Mexico", "ar" => "المكسيك",],
		"FM" => ["en" => "Micronesia, Federated States of", "ar" => "ميكرونيزيا",],
		"MD" => ["en" => "Moldova, Republic of", "ar" => "مولدوفا",],
		"MC" => ["en" => "Monaco", "ar" => "موناكو",],
		"MN" => ["en" => "Mongolia", "ar" => "منغوليا",],
		"ME" => ["en" => "Montenegro", "ar" => "الجبل الأسود",],
		"MS" => ["en" => "Montserrat", "ar" => "مونتسيرات",],
		"MA" => ["en" => "Morocco", "ar" => "المغرب",],
		"MZ" => ["en" => "Mozambique", "ar" => "موزمبيق",],
		"MM" => ["en" => "Myanmar", "ar" => "ميانمار",],
		"NA" => ["en" => "Namibia", "ar" => "ناميبيا",],
		"NR" => ["en" => "Nauru", "ar" => "ناورو",],
		"NP" => ["en" => "Nepal", "ar" => "نيبال",],
		"NL" => ["en" => "Netherlands", "ar" => "هولندا",],
		"NC" => ["en" => "New Caledonia", "ar" => "كاليدونيا الجديدة",],
		"NZ" => ["en" => "New Zealand", "ar" => "نيوزيلندا",],
		"NI" => ["en" => "Nicaragua", "ar" => "نيكاراغوا",],
		"NE" => ["en" => "Niger", "ar" => "النيجر",],
		"NG" => ["en" => "Nigeria", "ar" => "نيجيريا",],
		"NU" => ["en" => "Niue", "ar" => "نيوي",],
		"NF" => ["en" => "Norfolk Island", "ar" => "جزيرة نورفولك",],
		"MP" => ["en" => "Northern Mariana Islands", "ar" => "جزر ماريانا الشمالية",],
		"NO" => ["en" => "Norway", "ar" => "النرويج",],
		"OM" => ["en" => "Oman", "ar" => "عُمان",],
		"PK" => ["en" => "Pakistan", "ar" => "باكستان",],
		"PW" => ["en" => "Palau", "ar" => "بالاو",],
		"PS" => ["en" => "Palestine, State of", "ar" => "فلسطين",],
		"PA" => ["en" => "Panama", "ar" => "بنما",],
		"PG" => ["en" => "Papua New Guinea", "ar" => "بابوا غينيا الجديدة",],
		"PY" => ["en" => "Paraguay", "ar" => "باراغواي",],
		"PE" => ["en" => "Peru", "ar" => "بيرو",],
		"PH" => ["en" => "Philippines", "ar" => "الفلبين",],
		"PN" => ["en" => "Pitcairn", "ar" => "جزر بيتكيرن",],
		"PL" => ["en" => "Poland", "ar" => "بولندا",],
		"PT" => ["en" => "Portugal", "ar" => "البرتغال",],
		"PR" => ["en" => "Puerto Rico", "ar" => "بورتوريكو",],
		"QA" => ["en" => "Qatar", "ar" => "قطر",],
		"RE" => ["en" => "Réunion", "ar" => "ريونيون",],
		"RO" => ["en" => "Romania", "ar" => "رومانيا",],
		"RU" => ["en" => "Russian Federation", "ar" => "روسيا",],
		"RW" => ["en" => "Rwanda", "ar" => "رواندا",],
		"BL" => ["en" => "Saint Barthélemy", "ar" => "سان بارتيلمي",],
		"SH" => ["en" => "Saint Helena, Ascension and Tristan da Cunha", "ar" => "سانت هيلينا",],
		"KN" => ["en" => "Saint Kitts and Nevis", "ar" => "سانت كيتس ونيفيس",],
		"LC" => ["en" => "Saint Lucia", "ar" => "سانت لوسيا",],
		"MF" => ["en" => "Saint Martin (French part)", "ar" => "سانت مارتن (الجزء الفرنسي)",],
		"PM" => ["en" => "Saint Pierre and Miquelon", "ar" => "سانت بيير وميكلون",],
		"VC" => ["en" => "Saint Vincent and the Grenadines", "ar" => "سانت فنسنت والغرينادين",],
		"WS" => ["en" => "Samoa", "ar" => "ساموا",],
		"SM" => ["en" => "San Marino", "ar" => "سان مارينو",],
		"ST" => ["en" => "Sao Tome and Principe", "ar" => "ساو تومي وبرينسيبي",],
		"SA" => ["en" => "Saudi Arabia", "ar" => "المملكة العربية السعودية",],
		"SN" => ["en" => "Senegal", "ar" => "السنغال",],
		"RS" => ["en" => "Serbia", "ar" => "صربيا",],
		"SC" => ["en" => "Seychelles", "ar" => "سيشل",],
		"SL" => ["en" => "Sierra Leone", "ar" => "سيراليون",],
		"SG" => ["en" => "Singapore", "ar" => "سنغافورة",],
		"SX" => ["en" => "Sint Maarten (Dutch part)", "ar" => "سانت مارتن (الجزء الهولندي)",],
		"SK" => ["en" => "Slovakia", "ar" => "سلوفاكيا",],
		"SI" => ["en" => "Slovenia", "ar" => "سلوفينيا",],
		"SB" => ["en" => "Solomon Islands", "ar" => "جزر سليمان",],
		"SO" => ["en" => "Somalia", "ar" => "الصومال",],
		"ZA" => ["en" => "South Africa", "ar" => "جنوب أفريقيا",],
		"GS" => ["en" => "South Georgia and the South Sandwich Islands", "ar" => "جورجيا الجنوبية وجزر ساندويتش الجنوبية",],
		"SS" => ["en" => "South Sudan", "ar" => "جنوب السودان",],
		"ES" => ["en" => "Spain", "ar" => "إسبانيا",],
		"LK" => ["en" => "Sri Lanka", "ar" => "سريلانكا",],
		"SD" => ["en" => "Sudan", "ar" => "السودان",],
		"SR" => ["en" => "Suriname", "ar" => "سورينام",],
		"SJ" => ["en" => "Svalbard and Jan Mayen", "ar" => "سفالبارد ويان ماين",],
		"SZ" => ["en" => "Swaziland", "ar" => "إسواتيني",],
		"SE" => ["en" => "Sweden", "ar" => "السويد",],
		"CH" => ["en" => "Switzerland", "ar" => "سويسرا",],
		"SY" => ["en" => "Syrian Arab Republic", "ar" => "سوريا",],
		"TW" => ["en" => "Taiwan, Province of China", "ar" => "تايوان",],
		"TJ" => ["en" => "Tajikistan", "ar" => "طاجيكستان",],
		"TZ" => ["en" => "Tanzania, United Republic of", "ar" => "تنزانيا",],
		"TH" => ["en" => "Thailand", "ar" => "تايلاند",],
		"TL" => ["en" => "Timor-Leste", "ar" => "تيمور الشرقية",],
		"TG" => ["en" => "Togo", "ar" => "توغو",],
		"TK" => ["en" => "Tokelau", "ar" => "توكيلاو",],
		"TO" => ["en" => "Tonga", "ar" => "تونغا",],
		"TT" => ["en" => "Trinidad and Tobago", "ar" => "ترينيداد وتوباغو",],
		"TN" => ["en" => "Tunisia", "ar" => "تونس",],
		"TR" => ["en" => "Turkey", "ar" => "تركيا",],
		"TM" => ["en" => "Turkmenistan", "ar" => "تركمانستان",],
		"TC" => ["en" => "Turks and Caicos Islands", "ar" => "جزر توركس وكايكوس",],
		"TV" => ["en" => "Tuvalu", "ar" => "توفالو",],
		"UG" => ["en" => "Uganda", "ar" => "أوغندا",],
		"UA" => ["en" => "Ukraine", "ar" => "أوكرانيا",],
		"AE" => ["en" => "United Arab Emirates", "ar" => "الإمارات العربية المتحدة",],
		"GB" => ["en" => "United Kingdom", "ar" => "المملكة المتحدة",],
		"US" => ["en" => "United States", "ar" => "الولايات المتحدة",],
		"UM" => ["en" => "United States Minor Outlying Islands", "ar" => "جزر الولايات المتحدة الصغيرة النائية",],
		"UY" => ["en" => "Uruguay", "ar" => "أوروغواي",],
		"UZ" => ["en" => "Uzbekistan", "ar" => "أوزبكستان",],
		"VU" => ["en" => "Vanuatu", "ar" => "فانواتو",],
		"VE" => ["en" => "Venezuela, Bolivarian Republic of", "ar" => "فنزويلا",],
		"VN" => ["en" => "Viet Nam", "ar" => "فيتنام",],
		"VG" => ["en" => "Virgin Islands, British", "ar" => "جزر العذراء البريطانية",],
		"VI" => ["en" => "Virgin Islands, U.S.", "ar" => "جزر العذراء الأمريكية",],
		"WF" => ["en" => "Wallis and Futuna", "ar" => "واليس وفوتونا",],
		"EH" => ["en" => "Western Sahara", "ar" => "الصحراء الغربية",],
		"YE" => ["en" => "Yemen", "ar" => "اليمن",],
		"ZM" => ["en" => "Zambia", "ar" => "زامبيا",],
		"ZW" => ["en" => "Zimbabwe", "ar" => "زيمبابوي",],
	];

	/**
	 * Get Location from the given URL using cURL.
	 *
	 * @param string $url
	 *
	 * @return array
	 */
	private static  function getUrlLocation($url): array
	{
		try {
			$session = curl_init();
			curl_setopt($session, CURLOPT_URL, $url);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($session, CURLOPT_TIMEOUT, 2);
			curl_setopt($session, CURLOPT_CONNECTTIMEOUT,  2);
			$content = curl_exec($session);
			curl_close($session);
			$response = json_decode($content, true);
			return $response;
		} catch (\Throwable $th) {
			//throw $th;
		}
		try {
			$resp = Http::timeout(5)->get($url);
			if ($resp->ok()) {
				return $resp->json();
			}
		} catch (\Throwable $th) {
			//throw $th;
		}
		return [];
	}

	public static function getIpLocation($ip): string|array
	{


		$location = self::getUrlLocation("http://ip-api.com/json/$ip");

		if (\count($location) == 0) {
			$location = [
				'country' => '404',
				'city' => '',
				'region' => '',
			];
		} else {
			$location = [
				//"ip"=>$location["ip"],
				"city" => $location["cityName"],
				"country" => $location["countryName"],
				"region" => $location["regionName"],
				"loc" => $location["latitude"] . ',' . $location["longitude"],
			];
		}
		//unset_exists($location, 'timezone');
		//unset_exists($location, 'driver');
		return $location;
	}
	public static function getLocationFromIp(string $ip): string|array
	{
		$location = null;
		$location = self::lookupIp($ip);

		if (m_empty($location))
			return self::getIpLocation($ip);
		unset_exists($location, 'ip');
		unset_exists($location, 'readme');
		unset_exists($location, 'timezone');
		unset_exists($location, 'org');
		return $location;
	}
	public static  function lookupIp($ip)
	{

		$token =/* config('services.ipinfo.token')*/ null; // اجعله في .env ك IPINFO_TOKEN إن رغبت
		$url = 'https://ipinfo.io/' . $ip . '/json' . ($token ? "?token={$token}" : '');

		return self::getUrlLocation($url);
	}

	public static function getCountryNameByCode(?string $code)
	{
		if (empty($code) || $code === '404' || \is_numeric($code))
			return '';
		$code = Str::upper($code);
		$country = self::$allCountries[$code] ?? ['en' => $code, 'ar' => $code];
		return  $country['ar'];
	}
	


	public static function get_user_agent()
	{

		return request()->header('User-Agent');
	}

	public static function get_ip()
	{

		$ipaddress = '';
		if (isset($_SERVER["HTTP_CF_CONNECTING_IP"]))
			$ipaddress = $_SERVER["HTTP_CF_CONNECTING_IP"];
		else if (isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if (isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if (isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if (isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else if (request()->ip() != null)
			$ipaddress =  request()->getClientIp();
		else
			$ipaddress = 'UNKNOWN';
		return $ipaddress;
	}


	public static function get_os()
	{

		$user_agent = self::get_user_agent();
		$os_platform = "Unknown OS Platform";
		$os_array = array(
			'/windows nt 10/i'  => 'Windows 10',
			'/windows nt 6.3/i'  => 'Windows 8.1',
			'/windows nt 6.2/i'  => 'Windows 8',
			'/windows nt 6.1/i'  => 'Windows 7',
			'/windows nt 6.0/i'  => 'Windows Vista',
			'/windows nt 5.2/i'  => 'Windows Server 2003/XP x64',
			'/windows nt 5.1/i'  => 'Windows XP',
			'/windows xp/i'  => 'Windows XP',
			'/windows nt 5.0/i'  => 'Windows 2000',
			'/windows me/i'  => 'Windows ME',
			'/win98/i'  => 'Windows 98',
			'/win95/i'  => 'Windows 95',
			'/win16/i'  => 'Windows 3.11',
			'/macintosh|mac os x/i' => 'Mac OS X',
			'/mac_powerpc/i'  => 'Mac OS 9',
			'/linux/i'  => 'Linux',
			'/ubuntu/i'  => 'Ubuntu',
			'/iphone/i'  => 'iPhone',
			'/ipod/i'  => 'iPod',
			'/ipad/i'  => 'iPad',
			'/android/i'  => 'Android',
			'/blackberry/i'  => 'BlackBerry',
			'/webos/i'  => 'Mobile',
		);

		foreach ($os_array as $regex => $value) {
			if (preg_match($regex, $user_agent)) {
				$os_platform = $value;
			}
		}
		return $os_platform;
	}

	public static function get_browsers()
	{

		$user_agent = self::get_user_agent();

		$browser = "Unknown Browser";

		$browser_array = array(
			'/msie/i'  => 'Internet Explorer',
			'/Trident/i'  => 'Internet Explorer',
			'/firefox/i'  => 'Firefox',
			'/safari/i'  => 'Safari',
			'/chrome/i'  => 'Chrome',
			'/edge/i'  => 'Edge',
			'/opera/i'  => 'Opera',
			'/netscape/'  => 'Netscape',
			'/maxthon/i'  => 'Maxthon',
			'/knoqueror/i'  => 'Konqueror',
			'/ubrowser/i'  => 'UC Browser',
			'/mobile/i'  => 'Safari Browser',
		);

		foreach ($browser_array as $regex => $value) {
			if (preg_match($regex, $user_agent)) {
				$browser = $value;
			}
		}
		return $browser;
	}

	public static function get_device()
	{
		$tablet_browser = 0;
		$mobile_browser = 0;
		if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower(request()->header('User-Agent')))) {
			$tablet_browser++;
		}
		if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower(request()->header('User-Agent')))) {
			$mobile_browser++;
		}
		if ((isset($_SERVER['HTTP_ACCEPT']) && strpos(
				strtolower($_SERVER['HTTP_ACCEPT']),
				'application/vnd.wap.xhtml+xml'
			) > 0) or
			((isset($_SERVER['HTTP_X_WAP_PROFILE']) or
				isset($_SERVER['HTTP_PROFILE'])))
		) {
			$mobile_browser++;
		}
		$mobile_ua = strtolower(substr(self::get_user_agent(), 0, 4));
		$mobile_agents = array('w3c', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac', 'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno', 'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-', 'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-', 'newt', 'noki', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox', 'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-', 'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp', 'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-');
		if (\in_array($mobile_ua, $mobile_agents)) {
			$mobile_browser++;
		}
		if (strpos(strtolower(self::get_user_agent()), 'opera mini') > 0) {
			$mobile_browser++;
			//Check for tables on opera mini alternative headers
			$stock_ua =
				strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) ?
					$_SERVER['HTTP_X_OPERAMINI_PHONE_UA'] : (isset($_SERVER['HTTP_DEVICE_STOCK_UA']) ?
						$_SERVER['HTTP_DEVICE_STOCK_UA'] : ''));
			if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
				$tablet_browser++;
			}
		}
		if ($tablet_browser > 0) {
			//do something for tablet devices
			return 'Tablet';
		} else if ($mobile_browser > 0) {
			//do something for mobile devices
			return 'Mobile';
		} else {
			//do something for everything else
			return 'Computer';
		}
	}
	public static function prev_url()
	{
		$prev_url = "";
		if (filter_var(url()->previous(), FILTER_VALIDATE_URL)) // is a valid url 
		{
			$parsex = parse_url(url()->previous());
			$prev_domain = $parsex['host'];
			try {
				$prev_url = url()->previous();
			} catch (\Exception $e) {
				report($e);
			}
		}
		return $prev_url;
	}
}
