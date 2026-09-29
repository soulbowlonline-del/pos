<?php
namespace app\components\ai;

use yii\helpers\Html;

/**
 * The small part of Markdown Claude's answers use - paragraphs, bullet and
 * numbered lists, pipe tables, headings, **bold**, *italic* and `code` - as
 * HTML. Everything is escaped first, so nothing in an answer can become
 * markup or script of its own.
 */
class AiMarkdown
{
    public static function toHtml($md)
    {
        $lines = preg_split('/\r?\n/', (string) $md);
        $html = '';
        $i = 0;
        $n = count($lines);
        while ($i < $n) {
            $line = rtrim($lines[$i]);
            if (trim($line) === '') {
                $i++;
                continue;
            }
            if (preg_match('/^\s*\|.*\|\s*$/', $line) && $i + 1 < $n && preg_match('/^\s*\|?\s*:?-{2,}/', $lines[$i + 1])) {
                $head = self::cells($line);
                $i += 2;
                $body = [];
                while ($i < $n && preg_match('/^\s*\|.*\|\s*$/', $lines[$i])) {
                    $body[] = self::cells($lines[$i]);
                    $i++;
                }
                $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed ai-table"><thead><tr>';
                foreach ($head as $c) {
                    $html .= '<th>' . self::inline($c) . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach ($body as $row) {
                    $html .= '<tr>';
                    foreach ($row as $c) {
                        $html .= '<td>' . self::inline($c) . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div>';
                continue;
            }
            if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $m)) {
                $html .= '<h4>' . self::inline($m[2]) . '</h4>';
                $i++;
                continue;
            }
            if (preg_match('/^\s*([-*•]|\d+[.)])\s+/', $line, $m)) {
                $ordered = ctype_digit(substr($m[1], 0, 1));
                $html .= $ordered ? '<ol>' : '<ul>';
                while ($i < $n && preg_match('/^\s*([-*•]|\d+[.)])\s+(.*)$/', $lines[$i], $mm)) {
                    $html .= '<li>' . self::inline($mm[2]) . '</li>';
                    $i++;
                }
                $html .= $ordered ? '</ol>' : '</ul>';
                continue;
            }
            $para = [];
            while ($i < $n && trim($lines[$i]) !== '' && !preg_match('/^(#{1,4}\s|\s*([-*•]|\d+[.)])\s|\s*\|)/', $lines[$i])) {
                $para[] = self::inline(trim($lines[$i]));
                $i++;
            }
            if ($para) {
                $html .= '<p>' . implode('<br>', $para) . '</p>';
            } else {
                $html .= '<p>' . self::inline($line) . '</p>';
                $i++;
            }
        }
        return $html;
    }

    private static function cells($line)
    {
        $line = trim(trim($line), '|');
        return array_map('trim', explode('|', $line));
    }

    private static function inline($text)
    {
        $t = Html::encode($text);
        $t = preg_replace('/`([^`]+)`/', '<code>$1</code>', $t);
        $t = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $t);
        $t = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/u', '<em>$1</em>', $t);
        $t = preg_replace('/(?<![\w_])_(?!\s)(.+?)(?<!\s)_(?![\w_])/u', '<em>$1</em>', $t);
        return $t;
    }
}
