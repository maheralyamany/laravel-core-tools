<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

use Illuminate\Support\Facades\Cache;

class GoogleTranslation
{
  protected string $endpoint = 'https://translate.googleapis.com/translate_a/single';

  /** عدد الجُمل في كل Batch */
  protected int $batchSize = 5;

  /**
   * Translate single sentence
   * @param mixed $text
   * @param string $to
   * @param string $from
   * @return string
   */
  public function translate(
    mixed $text,
    string $to = 'ar',
    string $from = 'auto'
  ): string {
    if (is_numeric($text) || trim($text) === '' || !StringValidator::containsLetters($text)) {
      return $text;
    }


    $map = $this->protectPlaceholders($text);
    $translated = $this->googleTranslate($map['text'], $to, $from);

    return $this->restorePlaceholders($translated, $map['placeholders']);
  }

  /**
   * ✅ Translate (array of sentences) or single sentence
   * @param mixed $text
   * @param string $to
   * @param string $from
   * @return string|array<string|int, string>
   */
  public static function dyTranslate(
    mixed $text,
    string $to = 'ar',
    string $from = 'auto'
  ): string|array {

    $t = new static();
    if (is_array($text))
      return $t->translateArray($text, $to, $from);
    return $t->translate($text, $to, $from);
  }


  /**
   * ✅ Translate array of sentences using batching (5 per request)
   * @param array<string|int, string> $sentences
   * @param string $to
   * @param string $from
   * @return array<string|int, string>
   */
  public function translateArray(
    array $sentences,
    string $to = 'ar',
    string $from = 'auto'
  ): array {
    $results = [];
    $keys = [];
    $buffer = [];

    foreach ($sentences as $key => $sentence) {
      if (!is_string($sentence) || trim($sentence) === '') {
        $results[$key] = $sentence;
        continue;
      }

      $keys[] = $key;
      $buffer[] = $sentence;

      if (count($buffer) === $this->batchSize) {
        $translated = $this->translateBatch($buffer, $to, $from);

        foreach ($translated as $i => $value) {
          $results[$keys[$i]] = $value;
        }

        $keys = [];
        $buffer = [];
      }
    }

    // Translate remaining items
    if (!empty($buffer)) {
      $translated = $this->translateBatch($buffer, $to, $from);

      foreach ($translated as $i => $value) {
        $results[$keys[$i]] = $value;
      }
    }

    return $results;
  }

  /**
   * 🔹 Translate a batch of sentences in ONE request
   */
  protected function translateBatch(
    array $sentences,
    string $to,
    string $from
  ): array {
    $placeholderMaps = [];
    $protectedTexts = [];

    // Protect placeholders per sentence
    foreach ($sentences as $i => $text) {
      $map = $this->protectPlaceholders($text);
      $placeholderMaps[$i] = $map['placeholders'];
      $protectedTexts[] = $map['text'];
    }

    // Join with safe separator
    $separator = "\n---###---\n";
    $joinedText = implode($separator, $protectedTexts);

    $translatedJoined = $this->googleTranslate($joinedText, $to, $from);

    // Split back
    $translatedParts = explode($separator, $translatedJoined);

    // Restore placeholders
    foreach ($translatedParts as $i => $text) {
      $translatedParts[$i] = $this->restorePlaceholders(
        $text,
        $placeholderMaps[$i] ?? []
      );
    }

    return $translatedParts;
  }

  /**
   * Detect and protect placeholders
   */
  protected function protectPlaceholders(string $text): array
  {
    preg_match_all('/:\w+|#\$#/', $text, $matches);

    $placeholders = array_unique($matches[0]);
    $map = [];

    foreach ($placeholders as $i => $ph) {
      $map[$ph] = "__PH_{$i}__";
    }

    return [
      'text' => str_replace(array_keys($map), array_values($map), $text),
      'placeholders' => $map,
    ];
  }

  /**
   * Restore placeholders
   */
  protected function restorePlaceholders(string $text, array $map): string
  {
    return str_replace(
      array_values($map),
      array_keys($map),
      $text
    );
  }

  /**
   * Google Translate request
   */

  protected function googleTranslate(
    string $text,
    string $to,
    string $from
  ): string {
    $query = http_build_query([
      'client' => 'gtx',
      'sl'     => $from,
      'tl'     => $to,
      'dt'     => 't',
      'q'      => $text,
    ]);
    $hash = md5(trim($query));
    if (Cache::has($hash) || internet_available()) {
      return Cache::rememberForever(
        $hash,
        function () use ($query, $text) {
          try {
            $response = file_get_contents($this->endpoint . '?' . $query);
            $data = json_decode($response, true);
            $translated = null;
            if (isset($data[0])) {
              $translated = '';
              foreach ($data[0] as $translation) {
                $translated .= $translation[0];
              }
            }

            return $translated ?: ($data[0][0][0] ?? $text);
          } catch (\Throwable) {
            return $text;
          }
        }
      );
    }
    return $text;
  }
}
