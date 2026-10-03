<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/PostService.php';
$service = (new ReflectionClass(PostService::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod(PostService::class, 'sanitizeRichText');
$checks = 0;
foreach ([
    '<div><p onclick="alert(1)">Hello</p><script>alert(2)</script><a href="javascript:alert(3)">link</a></div>',
    '<section><div><img src=x onerror="alert(1)"><strong onmouseover="alert(2)">Safe</strong></div></section>',
    '<table><tr><td><a href="data:text/html,bad" onclick="alert(1)">Safe</a></td></tr></table>',
    '<div><svg><script>alert(1)</script></svg><p>Safe</p></div>'
] as $input) {
    $output = $method->invoke($service, $input);
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<div>' . $output . '</div>');
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    foreach ($document->getElementsByTagName('*') as $element) {
        if (in_array(strtolower($element->tagName), ['html','body','div'], true)) continue;
        if (!in_array(strtolower($element->tagName), ['p','br','strong','em','u','ul','ol','li','a'], true)) throw new RuntimeException('Unsafe nested tag survived.');
        $checks++;
        foreach ($element->attributes as $attribute) {
            if (!in_array($attribute->name, ['href','target','rel'], true)) throw new RuntimeException('Unsafe nested attribute survived.');
            if ($attribute->name === 'href' && !preg_match('/^(https?:\/\/|mailto:)/i', $attribute->value)) throw new RuntimeException('Unsafe URL survived.');
            $checks++;
        }
    }
}
$output = $method->invoke($service, '<div><p>Hello <strong>world</strong></p><a href="https://example.com">Source</a></div>');
foreach (['<p>Hello <strong>world</strong></p>', 'href="https://example.com"', 'rel="noopener noreferrer"'] as $expected) {
    if (!str_contains($output, $expected)) throw new RuntimeException('Supported formatting or link lost.');
    $checks++;
}
echo "PASS: $checks rich-text sanitization checks; no records or deliveries changed.\n";
