<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

class ArrayComparator
{
    /**
     * استخراج العناصر الموجودة في المصفوفة الأولى وغير موجودة في الثانية
     * @param array $array1
     * @param array $array2
     * @param string $compare in ('both','key','value')
     * @return array
     */
    public static function diff(array $array1, array $array2, string $compare = 'both')
    {
        switch ($compare) {
            case 'key':
                return self::getKeysOnlyInFirst($array1, $array2);
            case 'value':
                return self::getValuesOnlyInFirst($array1, $array2);
            case 'both':
            default:
                return self::getItemsOnlyInFirst($array1, $array2);
        }
    }
    /**
     * استخراج العناصر الموجودة في المصفوفة الأولى وغير موجودة في الثانية
     * @param array $array1
     * @param array $array2
     * @param string $compare in ('both','key','value')
     * @return array
     */
    public static function arrayDiffAssoc(array $array1, array $array2, string $compare = 'both')
    {
        return self::diff($array1, $array2, $compare);
    }

    /**
     * Summary of doublicateKeys
     * @param array $items
     * @return array
     */
    public static function doublicateKeys($items)
    {
        $collect = collect($items);
        $keys = $collect->unique()->keys()->All();
        $doublicate = [];
        if (count($keys) === count($items)) {
            return $doublicate;
        }

        foreach ($keys as $key) {
            $f = $collect->filter(fn($v, $k) => $k === $key);
            if ($f->count() > 1) {
                $doublicate[$key] = $f->values()->toArray();
            }
        }

        return $doublicate;
    }

    /**
     * استخراج المفاتيح الموجودة فقط في المصفوفة الأولى
     */
    private static function getKeysOnlyInFirst($array1, $array2)
    {
        $keys1 = array_keys($array1);
        $keys2 = array_keys($array2);
        $diffKeys = array_diff($keys1, $keys2);
        $result = [];
        foreach ($diffKeys as $key) {
            $result[$key] = $array1[$key];
        }

        return $result;
    }

    /**
     * استخراج القيم الموجودة فقط في المصفوفة الأولى
     */
    private static function getValuesOnlyInFirst($array1, $array2)
    {
        $values1 = array_values($array1);
        $values2 = array_values($array2);
        return array_diff($values1, $values2);
    }

    /**
     * استخراج العناصر الكاملة الموجودة فقط في المصفوفة الأولى
     */
    private static function getItemsOnlyInFirst($array1, $array2)
    {
        $result = [];
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                // المفتاح غير موجود في المصفوفة الثانية
                $result[$key] = $value;
            } elseif ($value !== $array2[$key]) {
                // المفتاح موجود ولكن القيمة مختلفة
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * نسخة متقدمة مع خيارات إضافية
     */
    public static function diffAdvanced($array1, $array2, $options = [])
    {
        $defaultOptions = [
            'compare' => 'both',
            'deep_compare' => true,
            'case_sensitive' => true,
            'ignore_order' => false,
            'strict_type' => true,
            'include_changed' => true,
        ];
        $options = array_merge($defaultOptions, $options);
        if ($options['deep_compare']) {
            return self::deepDiff($array1, $array2, $options);
        }

        return self::diff($array1, $array2, $options['compare']);
    }

    /**
     * مقارنة عميقة للمصفوفات المتداخلة
     */
    private static function deepDiff($array1, $array2, $options)
    {
        $result = [];
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                // المفتاح غير موجود في المصفوفة الثانية
                $result[$key] = $value;
            } else {
                $value2 = $array2[$key];
                if (is_array($value) && is_array($value2)) {
                    $nestedDiff = self::deepDiff($value, $value2, $options);
                    // إضافة الاختلافات المتداخلة فقط إذا كانت موجودة
                    if (!empty($nestedDiff)) {
                        $result[$key] = $nestedDiff;
                    }
                } elseif (!self::valuesAreEqual($value, $value2, $options)) {
                    if ($options['include_changed']) {
                        $result[$key] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * مقارنة القيم مع مراعاة الخيارات
     */
    private static function valuesAreEqual($value1, $value2, $options)
    {
        if (!$options['case_sensitive'] && is_string($value1) && is_string($value2)) {
            $value1 = strtolower($value1);
            $value2 = strtolower($value2);
        }

        if ($options['ignore_order'] && is_array($value1) && is_array($value2)) {
            sort($value1);
            sort($value2);
        }

        if ($options['strict_type']) {
            return $value1 === $value2;
        } else {
            return $value1 == $value2;
        }
    }

    /**
     * دالة مساعدة للحصول على الاختلافات مع تفاصيل
     */
    public static function diffDetailed($array1, $array2, $compare = 'both')
    {
        $result = [
            'only_in_first' => [],
            'changed_values' => [],
        ];
        switch ($compare) {
            case 'key':
                $keys1 = array_keys($array1);
                $keys2 = array_keys($array2);
                $diffKeys = array_diff($keys1, $keys2);
                foreach ($diffKeys as $key) {
                    $result['only_in_first'][$key] = $array1[$key];
                }

                break;
            case 'value':
                $values1 = array_values($array1);
                $values2 = array_values($array2);
                $result['only_in_first'] = array_diff($values1, $values2);
                break;
            case 'both':
            default:
                foreach ($array1 as $key => $value) {
                    if (!array_key_exists($key, $array2)) {
                        $result['only_in_first'][$key] = $value;
                    } elseif ($value !== $array2[$key]) {
                        $result['changed_values'][$key] = [
                            'first' => $value,
                            'second' => $array2[$key],
                        ];
                    }
                }

                break;
        }

        return $result;
    }

    /**
     * التحقق مما إذا كانت المصفوفة الأولى تحتوي على عناصر غير موجودة في الثانية
     */
    public static function hasDifferences($array1, $array2, $compare = 'both')
    {
        $diff = self::diff($array1, $array2, $compare);
        return !empty($diff);
    }

    /**
     * المقارنة بالمفتاح فقط
     */
    public static function compareByKey($array1, $array2)
    {
        $diff = [
            'only_in_first' => [],
            'only_in_second' => [],
            'common_keys' => [],
        ];
        $keys1 = array_keys($array1);
        $keys2 = array_keys($array2);
        $diff['only_in_first'] = array_diff($keys1, $keys2);
        $diff['only_in_second'] = array_diff($keys2, $keys1);
        $diff['common_keys'] = array_intersect($keys1, $keys2);
        return $diff;
    }

    /**
     * المقارنة بالقيمة فقط (بغض النظر عن المفتاح)
     */
    public static function compareByValue($array1, $array2)
    {
        $diff = [
            'only_in_first' => [],
            'only_in_second' => [],
            'common_values' => [],
        ];
        $values1 = array_values($array1);
        $values2 = array_values($array2);
        $diff['only_in_first'] = array_diff($values1, $values2);
        $diff['only_in_second'] = array_diff($values2, $values1);
        $diff['common_values'] = array_intersect($values1, $values2);
        return $diff;
    }

    /**
     * المقارنة بالمفتاح والقيمة معاً
     */
    public static function compareByBoth($array1, $array2)
    {
        $diff = [
            'only_in_first' => [],
            'only_in_second' => [],
            'common_with_different_values' => [],
            'identical' => [],
        ];
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $diff['only_in_first'][$key] = $value;
            } elseif ($value !== $array2[$key]) {
                $diff['common_with_different_values'][$key] = [
                    'first' => $value,
                    'second' => $array2[$key],
                ];
            } else {
                $diff['identical'][$key] = $value;
            }
        }

        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                $diff['only_in_second'][$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * مقارنة عميقة للمصفوفات المتداخلة
     */
    public static function deepCompare($array1, $array2, $options)
    {
        $diff = [
            'only_in_first' => [],
            'only_in_second' => [],
            'common_with_differences' => [],
            'identical' => [],
        ];
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $diff['only_in_first'][$key] = $value;
            } else {
                $value2 = $array2[$key];
                if (is_array($value) && is_array($value2)) {
                    $nestedDiff = self::deepCompare($value, $value2, $options);
                    if (!empty($nestedDiff['only_in_first']) || !empty($nestedDiff['only_in_second']) || !empty($nestedDiff['common_with_differences'])) {
                        $diff['common_with_differences'][$key] = $nestedDiff;
                    } else {
                        $diff['identical'][$key] = $value;
                    }
                } elseif (self::valuesAreEqual($value, $value2, $options)) {
                    $diff['identical'][$key] = $value;
                } else {
                    $diff['common_with_differences'][$key] = [
                        'first' => $value,
                        'second' => $value2,
                    ];
                }
            }
        }

        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                $diff['only_in_second'][$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * مقارنة بسيطة بإرجاع نتيجة مختصرة
     */
    public static function simpleDiff($array1, $array2, $compare = 'both')
    {
        $fullDiff = self::diff($array1, $array2, $compare);
        switch ($compare) {
            case 'key':
                return [
                    'added_keys' => $fullDiff['only_in_second'],
                    'removed_keys' => $fullDiff['only_in_first'],
                    'total_differences' => count($fullDiff['only_in_first']) + count($fullDiff['only_in_second']),
                ];
            case 'value':
                return [
                    'added_values' => $fullDiff['only_in_second'],
                    'removed_values' => $fullDiff['only_in_first'],
                    'total_differences' => count($fullDiff['only_in_first']) + count($fullDiff['only_in_second']),
                ];
            case 'both':
            default:
                return [
                    'added_items' => $fullDiff['only_in_second'],
                    'removed_items' => $fullDiff['only_in_first'],
                    'changed_items' => $fullDiff['common_with_different_values'],
                    'total_differences' => count($fullDiff['only_in_first']) + count($fullDiff['only_in_second']) + count($fullDiff['common_with_different_values']),
                ];
        }
    }

    /**
     * التحقق مما إذا كانت المصفوفتان متطابقتين
     */
    public static function areEqual($array1, $array2, $compare = 'both')
    {
        $diff = self::diff($array1, $array2, $compare);
        switch ($compare) {
            case 'key':
                return empty($diff['only_in_first']) && empty($diff['only_in_second']);
            case 'value':
                return empty($diff['only_in_first']) && empty($diff['only_in_second']);
            case 'both':
            default:
                return empty($diff['only_in_first']) && empty($diff['only_in_second']) && empty($diff['common_with_different_values']);
        }
    }

    /**
     * إنشاء تقرير مفصل عن الاختلافات
     */
    public static function diffReport($array1, $array2, $compare = 'both')
    {
        $diff = self::diff($array1, $array2, $compare);
        $report = [];
        switch ($compare) {
            case 'key':
                $report[] = "=== تقرير مقارنة المفاتيح ===";
                $report[] = "المفاتيح الموجودة فقط في المصفوفة الأولى: " . count($diff['only_in_first']);
                $report[] = "المفاتيح الموجودة فقط في المصفوفة الثانية: " . count($diff['only_in_second']);
                $report[] = "المفاتيح المشتركة: " . count($diff['common_keys']);
                break;
            case 'value':
                $report[] = "=== تقرير مقارنة القيم ===";
                $report[] = "القيم الموجودة فقط في المصفوفة الأولى: " . count($diff['only_in_first']);
                $report[] = "القيم الموجودة فقط في المصفوفة الثانية: " . count($diff['only_in_second']);
                $report[] = "القيم المشتركة: " . count($diff['common_values']);
                break;
            case 'both':
            default:
                $report[] = "=== تقرير مقارنة كامل ===";
                $report[] = "العناصر الموجودة فقط في المصفوفة الأولى: " . count($diff['only_in_first']);
                $report[] = "العناصر الموجودة فقط في المصفوفة الثانية: " . count($diff['only_in_second']);
                $report[] = "العناصر المشتركة بقيم مختلفة: " . count($diff['common_with_different_values']);
                $report[] = "العناصر المتطابقة تماماً: " . count($diff['identical']);
                break;
        }

        return implode("\n", $report);
    }
}
