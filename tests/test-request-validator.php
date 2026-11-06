<?php
declare(strict_types=1);

$validator = dirname(__DIR__) . '/scripts/validate-request.php';
$fixture = [
    'version' => 1,
    'site_url' => 'https://example.org',
    'request_id' => 'contract-fixture-001',
    'action' => 'inspect',
    'dry_run' => true,
    'confirm' => false,
    'payload' => new stdClass(),
];

$cases = [
    'empty JSON object' => [$fixture, true],
    'nonempty JSON object' => [array_replace($fixture, ['payload' => (object) ['limit' => 10]]), true],
    'empty JSON array' => [array_replace($fixture, ['payload' => []]), false],
    'nonempty JSON array' => [array_replace($fixture, ['payload' => ['one']]), false],
    'scalar payload' => [array_replace($fixture, ['payload' => 'wrong']), false],
    'array root' => [[$fixture], false],
    'empty array root' => [[], false],
    'insecure site URL' => [array_replace($fixture, ['site_url' => 'http://example.org']), false],
];

$temporary = tempnam(sys_get_temp_dir(), 'mim-json-request-');
if ($temporary === false) {
    throw new RuntimeException('Unable to create isolated JSON test fixture.');
}

try {
    foreach ($cases as $label => [$request, $expectedValid]) {
        file_put_contents($temporary, json_encode($request, JSON_THROW_ON_ERROR));
        $command = escapeshellarg(PHP_BINARY) . ' '
            . escapeshellarg($validator) . ' '
            . escapeshellarg($temporary) . ' 2>&1';
        $output = [];
        $exitCode = -1;
        exec($command, $output, $exitCode);
        if (($exitCode === 0) !== $expectedValid) {
            throw new RuntimeException(
                'Request validator case failed: ' . $label . '; exit=' . $exitCode
                . '; output=' . implode(' ', $output)
            );
        }
    }
} finally {
    @unlink($temporary);
}

echo "JSON request shape and URL validation: OK\n";
