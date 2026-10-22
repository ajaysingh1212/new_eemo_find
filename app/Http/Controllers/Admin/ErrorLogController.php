<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class ErrorLogController extends Controller
{
    private const MAX_LOG_BYTES = 131072;

    private const MAX_LOG_LINES = 400;

    public function __invoke()
    {
        Gate::authorize('manage-branding');

        $path = $this->configuredLogPath((string) config('logging.default'), []);

        if ($path === null) {
            return view('admin.security.error-log', [
                'lines' => [],
                'message' => 'The configured Laravel log channel does not write to a file.',
                'lineCount' => 0,
                'canClear' => false,
            ]);
        }

        if (! is_file($path)) {
            return view('admin.security.error-log', [
                'lines' => [],
                'message' => 'No Laravel log file has been created yet.',
                'lineCount' => 0,
                'canClear' => false,
            ]);
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('The Laravel log file exists but could not be opened.');
        }

        try {
            $size = fstat($handle)['size'] ?? 0;
            $offset = max(0, $size - self::MAX_LOG_BYTES);

            if ($offset > 0 && fseek($handle, $offset) !== 0) {
                throw new RuntimeException('The Laravel log file could not be positioned for reading.');
            }

            $contents = stream_get_contents($handle, self::MAX_LOG_BYTES);
        } finally {
            fclose($handle);
        }

        if ($contents === false) {
            throw new RuntimeException('The Laravel log file could not be read.');
        }

        if ($offset > 0) {
            $firstLineEnd = strpos($contents, "\n");
            $contents = $firstLineEnd === false ? '' : substr($contents, $firstLineEnd + 1);
        }

        $trimmedContents = trim($contents);
        $lines = $trimmedContents === '' ? [] : (preg_split('/\R/', $trimmedContents) ?: []);
        $lines = array_slice($lines, -self::MAX_LOG_LINES);
        $lines = array_map(fn (string $line): string => $this->redactSecrets($line), $lines);

        return view('admin.security.error-log', [
            'lines' => $lines,
            'message' => $lines === [] ? 'The Laravel log file is empty.' : null,
            'lineCount' => count($lines),
            'canClear' => true,
        ]);
    }

    public function destroy(): RedirectResponse
    {
        Gate::authorize('manage-branding');

        $path = $this->configuredLogPath((string) config('logging.default'), []);

        if ($path === null || ! is_file($path)) {
            return back()->with('status', 'No current Laravel log file is available to clear.');
        }

        $handle = fopen($path, 'r+b');

        if ($handle === false) {
            throw new RuntimeException('The Laravel log file exists but could not be opened for clearing.');
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException('The Laravel log file could not be locked for clearing.');
            }

            if (! ftruncate($handle, 0) || ! fflush($handle)) {
                throw new RuntimeException('The Laravel log file could not be cleared.');
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return back()->with('status', 'Laravel error log cleared.');
    }

    private function configuredLogPath(string $channelName, array $visited): ?string
    {
        if (in_array($channelName, $visited, true)) {
            return null;
        }

        $visited[] = $channelName;
        $channel = config("logging.channels.{$channelName}");

        if (! is_array($channel)) {
            return null;
        }

        if ($channel['driver'] === 'stack') {
            foreach ($channel['channels'] ?? [] as $stackChannel) {
                $path = $this->configuredLogPath($stackChannel, $visited);

                if ($path !== null) {
                    return $path;
                }
            }

            return null;
        }

        if ($channel['driver'] === 'single' || $channel['driver'] === 'daily') {
            $path = $channel['path'] ?? null;

            if (! is_string($path) || $path === '') {
                return null;
            }

            return $channel['driver'] === 'daily'
                ? $path.'-'.now()->format('Y-m-d').'.log'
                : $path;
        }

        return null;
    }

    private function redactSecrets(string $line): string
    {
        $line = preg_replace('/(Bearer\s+)[A-Za-z0-9._~+\/=-]+/i', '$1[REDACTED]', $line);
        $line = preg_replace(
            '/(["\']?(?:password|passwd|secret|token|authorization|api[_-]?key)["\']?\s*(?:=>|:|=)\s*)(?:"[^"]*"|\'[^\']*\'|[^\s,;]+)/i',
            '$1[REDACTED]',
            $line
        );

        return preg_replace('/([?&](?:token|secret|api[_-]?key)=)[^&\s]+/i', '$1[REDACTED]', $line) ?? $line;
    }
}
