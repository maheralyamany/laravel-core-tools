<?php
declare(strict_types=1);
namespace Maher\CoreTools\Support;
class StringValidator
{
    /**
     * التحقق من أن السلسلة تحتوي فقط على أرقام ورموز بدون حروف
     * 
     * @param string $string السلسلة المراد التحقق منها
     * @param bool $allowSpaces هل يسمح بالمسافات؟
     * @return bool
     */
    public static function containsOnlyNumbersAndSymbols(string $string, bool $allowSpaces = true): bool
    {
        $cleanString = $allowSpaces ? $string : str_replace(' ', '', $string);
        if (empty(trim($cleanString))) {
            return false;
        }
        // التحقق من عدم وجود حروف إنجليزية أو عربية
        return !preg_match('/[a-zA-Zأ-ي]/u', $cleanString);
    }
    /**
     * التحقق من أن السلسلة تحتوي فقط على أرقام (بدون رموز أو حروف)
     * 
     * @param string $string السلسلة المراد التحقق منها
     * @param bool $allowSpaces هل يسمح بالمسافات؟
     * @return bool
     */
    public static function containsOnlyNumbers(string $string, bool $allowSpaces = true): bool
    {
        $cleanString = $allowSpaces ? $string : str_replace(' ', '', $string);
        if (empty(trim($cleanString))) {
            return false;
        }
        return ctype_digit(str_replace(' ', '', $cleanString));
    }
    /**
     * التحقق من أن السلسلة تحتوي فقط على رموز (بدون أرقام أو حروف)
     * 
     * @param string $string السلسلة المراد التحقق منها
     * @param bool $allowSpaces هل يسمح بالمسافات؟
     * @return bool
     */
    public static function containsOnlySymbols(string $string, bool $allowSpaces = true): bool
    {
        $cleanString = $allowSpaces ? $string : str_replace(' ', '', $string);
        if (empty(trim($cleanString))) {
            return false;
        }
        $cleanString = str_replace(' ', '', $cleanString);
        return !empty($cleanString) && preg_match('/^[\W_]+$/', $cleanString);
    }
    /**
     * التحقق من أن السلسلة تحتوي فقط على أرقام ورموز محددة
     * 
     * @param string $string السلسلة المراد التحقق منها
     * @param string $allowedSymbols الرموز المسموحة (افتراضي: جميع الرموز)
     * @param bool $allowSpaces هل يسمح بالمسافات؟
     * @return bool
     */
    public static function containsOnlyNumbersAndSpecificSymbols(
        string $string, 
        string $allowedSymbols = '\W_', 
        bool $allowSpaces = true
    ): bool {
        $cleanString = $allowSpaces ? $string : str_replace(' ', '', $string);
        if (empty(trim($cleanString))) {
            return false;
        }
        $pattern = $allowSpaces 
            ? "/^[0-9{$allowedSymbols}\s]+$/"
            : "/^[0-9{$allowedSymbols}]+$/";
        return (bool) preg_match($pattern, $cleanString);
    }
    /**
     * التحقق من أن السلسلة تحتوي على حروف
     * 
     * @param string $string السلسلة المراد التحقق منها
     * @param bool $checkArabic التحقق من الحروف العربية
     * @param bool $checkEnglish التحقق من الحروف الإنجليزية
     * @return bool
     */
    public static function containsLetters(
        string $string, 
        bool $checkArabic = true, 
        bool $checkEnglish = true
    ): bool {
        if (empty(trim($string))) {
            return false;
        }
        $pattern = '';
        if ($checkEnglish) {
            $pattern .= 'a-zA-Z';
        }
        if ($checkArabic) {
            $pattern .= 'أ-ي';
        }
        if (empty($pattern)) {
            return false;
        }
        return (bool) preg_match("/[{$pattern}]/u", $string);
    }
    /**
     * فحص شامل للسلسلة وإرجاع تقرير مفصل
     * 
     * @param string $string السلسلة المراد فحصها
     * @return array
     */
    public static function analyzeString(string $string): array
    {
        return [
            'string' => $string,
            'contains_letters' => self::containsLetters($string),
            'contains_english_letters' => self::containsLetters($string, false, true),
            'contains_arabic_letters' => self::containsLetters($string, true, false),
            'contains_only_numbers' => self::containsOnlyNumbers($string),
            'contains_only_symbols' => self::containsOnlySymbols($string),
            'contains_only_numbers_and_symbols' => self::containsOnlyNumbersAndSymbols($string),
            'is_empty' => empty(trim($string)),
            'length' => strlen($string),
            'length_without_spaces' => strlen(str_replace(' ', '', $string))
        ];
    }
    /**
     * عرض تقرير تحليل السلسلة بشكل منسق
     * 
     * @param string $string السلسلة المراد تحليلها
     * @return void
     */
    public static function printAnalysisReport(string $string): void
    {
        $analysis = self::analyzeString($string);
        echo "============================================\n";
        echo "تقرير تحليل السلسلة النصية\n";
        echo "============================================\n";
        echo "السلسلة: '{$analysis['string']}'\n";
        echo "الطول: {$analysis['length']} حرف\n";
        echo "الطول بدون مسافات: {$analysis['length_without_spaces']} حرف\n";
        echo "فارغة: " . ($analysis['is_empty'] ? 'نعم' : 'لا') . "\n";
        echo "تحتوي على حروف: " . ($analysis['contains_letters'] ? 'نعم' : 'لا') . "\n";
        echo "تحتوي على حروف إنجليزية: " . ($analysis['contains_english_letters'] ? 'نعم' : 'لا') . "\n";
        echo "تحتوي على حروف عربية: " . ($analysis['contains_arabic_letters'] ? 'نعم' : 'لا') . "\n";
        echo "أرقام فقط: " . ($analysis['contains_only_numbers'] ? 'نعم' : 'لا') . "\n";
        echo "رموز فقط: " . ($analysis['contains_only_symbols'] ? 'نعم' : 'لا') . "\n";
        echo "أرقام ورموز فقط: " . ($analysis['contains_only_numbers_and_symbols'] ? 'نعم' : 'لا') . "\n";
        echo "============================================\n";
    }
}



