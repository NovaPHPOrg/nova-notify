<?php

declare(strict_types=1);

namespace nova\plugin\notify\markdown;

class ParseMarkdownTxt
{
    public function parse(string $text): string
    {
        // 直接处理Markdown文本，不经过HTML转换
        return $this->markdown2text($text);
    }

    /**
     * Convert Markdown to plain text with special formatting
     * - Preserves links
     * - Formats headings with appropriate styling
     * - Handles blockquotes, horizontal rules, and code blocks
     * - Maintains list formatting and indentation
     * - Preserves emphasis (bold, italic)
     * - Formats tables for readability
     *
     * @param  string $markdown The Markdown text to convert
     * @return string Formatted plain text
     */
    private function markdown2text(string $markdown): string
    {
        // 替换多余的空行，保留单个换行
        $text = preg_replace('/\n{3,}/', "\n\n", $markdown);

        // 处理嵌套引用块 (需要先处理)
        $text = preg_replace_callback('/^(>+)(.*?)$/m', function ($matches) {
            // 获取引用的嵌套层级
            $content = trim($matches[2]); // 直接获取内容，不用trim，因为正则已经精确匹配了空白字符

            // 所有引用层级都简化为单个符号
            return '┃ ' . $content;
        }, $text);

        $repeat_count = 15;

        // 处理标题 (# 式标题)
        $text = preg_replace_callback('/^#\s+(.*?)$/m', function ($matches) use ($repeat_count) {
            return trim($matches[1]). "\n" . str_repeat('━', $repeat_count);
        }, $text);

        $text = preg_replace_callback('/^##\s+(.*?)$/m', function ($matches) use ($repeat_count) {
            return trim($matches[1]). "\n" . str_repeat('━', $repeat_count);
        }, $text);

        $text = preg_replace_callback('/^(#{3,6})\s+(.*?)$/m', function ($matches) {
            $level = strlen($matches[1]);
            $prefix = str_repeat('#', $level) . ' ';
            return $prefix . trim($matches[2]) . '】';
        }, $text);

        // 处理另一种标题格式 (=== 和 --- 式标题)
        $text = preg_replace_callback('/^(.*?)\n={3,}\s*$/m', function ($matches) use ($repeat_count) {
            return trim($matches[1]). "\n" . str_repeat('━', $repeat_count);
        }, $text);

        $text = preg_replace_callback('/^(.*?)\n-{3,}\s*$/m', function ($matches) use ($repeat_count) {
            return trim($matches[1]). "\n" . str_repeat('━', $repeat_count);
        }, $text);

        // 处理水平线
        $text = preg_replace('/^(-{3,}|\*{3,}|_{3,})$/m', str_repeat('━', 12), $text);

        // 处理代码块
        $text = preg_replace_callback('/```(?:.*?)\n([\s\S]*?)```/s', function ($matches) {
            $code = $matches[1];
            $lines = explode("\n", $code);
            $result = "\n┌" . str_repeat('─', 50) . "┐\n";
            $result .= "│ CODE BLOCK:\n";
            foreach ($lines as $line) {
                $result .= "│ " . $line . "\n";
            }
            $result .= "└" . str_repeat('─', 50) . "┘\n";
            return $result;
        }, $text);

        // 处理行内代码
        $text = preg_replace('/`([^`]+)`/', '`$1`', $text);

        // 处理表格
        $text = preg_replace_callback('/^\|(.*)\|\s*\n\|[-:|\s]+\|\s*\n(\|.*\|\s*\n)+/m', function ($matches) {
            $tableRows = explode("\n", trim($matches[0]));
            $result = "\n";

            // 处理表头
            if (isset($tableRows[0])) {
                $headerCells = explode('|', trim($tableRows[0], '|'));
                $line = "| ";
                foreach ($headerCells as $cell) {
                    $line .= trim($cell) . " | ";
                }
                $result .= $line . "\n";
                $result .= "|" . str_repeat('-', strlen($line) - 3) . "|\n";
            }

            // 处理数据行 (跳过表头和分隔行)
            for ($i = 2; $i < count($tableRows); $i++) {
                if (empty(trim($tableRows[$i]))) {
                    continue;
                }

                $dataCells = explode('|', trim($tableRows[$i], '|'));
                $line = "| ";
                foreach ($dataCells as $cell) {
                    $line .= trim($cell) . " | ";
                }
                $result .= $line . "\n";
            }

            return $result . "\n";
        }, $text);

        // 处理图片
        $text = preg_replace('/!\[(.*?)\]\(.*?\)/', '[图片: $1]', $text);

        // 将链接转换为HTML a标签
        $text = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($matches) {
            $linkText = $matches[1];
            $url = $matches[2];
            // 转换为HTML a标签
            return "<a href=\"$url\">$linkText</a>";
        }, $text);

        // 处理任务列表
        $text = preg_replace('/^\s*- \[ \]\s*(.*?)$/m', '[  ] $1', $text);
        $text = preg_replace('/^\s*- \[x\]\s*(.*?)$/mi', '[✓] $1', $text);

        // 处理有序列表
        $text = preg_replace_callback('/^\s*(\d+)\.\s+(.*?)$/m', function ($matches) {
            return $matches[1] . ". " . $matches[2];
        }, $text);

        // 处理无序列表
        $text = preg_replace_callback('/^\s*([-*+])\s+(.*?)$/m', function ($matches) {
            // 计算缩进级别
            $indentLevel = 0;
            $originalString = $matches[0];
            $leadingSpaces = strlen($originalString) - strlen(ltrim($originalString));
            if ($leadingSpaces > 0) {
                $indentLevel = floor($leadingSpaces / 2);
            }

            $indentation = str_repeat('  ', $indentLevel);
            $bullet = $indentLevel == 0 ? '• ' : '◦ ';
            return $indentation . $bullet . $matches[2];
        }, $text);

        // 处理强调 (粗体)
        $text = preg_replace('/\*\*(.*?)\*\*|__(.*?)__/s', '【$1$2】', $text);

        // 处理强调 (斜体)
        $text = preg_replace('/\*(.*?)\*|_(.*?)_/s', '_$1$2_', $text);

        // 去除多余空行
        while (preg_match('/\n{2,}/', $text)) {
            $text = preg_replace('/\n{2,}/', "\n", $text);
        }

        return trim($text);
    }

}
