<?php
/**
 * IDE Helper Stubs for Static Analysis & Language Server (Intelephense / PHPStan)
 * This file provides type definitions for IDE indexing.
 */

namespace Dompdf;

class Options
{
    public function __construct(array $attributes = []) {}
    public function set(string $option, $value) {}
    public function get(string $option) {}
    public function setIsRemoteEnabled(bool $isRemoteEnabled) {}
    public function setIsHtml5ParserEnabled(bool $isHtml5ParserEnabled) {}
    public function setDefaultFont(string $defaultFont) {}
    public function setChroot($chroot) {}
}

class Dompdf
{
    public function __construct(?Options $options = null) {}
    public function loadHtml(string $str, ?string $encoding = null) {}
    public function setPaper($size, string $orientation = 'portrait') {}
    public function render() {}
    public function output(array $options = []): string {}
    public function stream(string $filename = 'document.pdf', array $options = []) {}
}
