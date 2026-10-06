<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Testing;

use Foxws\AbAv1\AbAv1Command;
use Foxws\AbAv1\AbAv1Executable;
use Foxws\Media\Filters\Number;
use Foxws\Media\Testing\MediaFake;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\FakeProcessResult;

/**
 * Answers ab-av1 in Media::fake(): encodes write a placeholder file and report the CRF and score,
 * and comparisons print the score.
 */
final class FakeAbAv1
{
    /**
     * @param  int  $percent  The predicted size as a percentage of the input.
     * @param  string  $errorOutput  What ab-av1 logs on its error output while it runs, e.g. its progress.
     */
    public static function respond(MediaFake $fake, float $crf = 30.0, float $score = 95.0, int $percent = 45, string $errorOutput = ''): MediaFake
    {
        return $fake->respondUsing(AbAv1Executable::AbAv1, function (array $arguments) use ($crf, $score, $percent, $errorOutput): ProcessResult {
            if (AbAv1Command::tryFrom($arguments[0] ?? '')?->compares() === true) {
                return new FakeProcessResult(output: Number::format($score, 4), errorOutput: $errorOutput);
            }

            $output = self::option($arguments, '--output');

            if ($output !== null) {
                new Filesystem()->ensureDirectoryExists(dirname($output));
                file_put_contents($output, 'fake media');
            }

            return new FakeProcessResult(output: sprintf(
                'crf %s %s %s predicted video stream size 105.69 MiB (%d%%) taking 9 minutes',
                Number::format(self::option($arguments, '--crf') !== null ? (float) self::option($arguments, '--crf') : $crf, 4),
                in_array('--min-xpsnr', $arguments, true) ? 'XPSNR' : 'VMAF',
                Number::format($score, 4),
                $percent,
            ), errorOutput: $errorOutput);
        });
    }

    /**
     * @param  list<string>  $arguments
     */
    private static function option(array $arguments, string $name): ?string
    {
        $index = array_search($name, $arguments, true);

        return $index !== false ? ($arguments[$index + 1] ?? null) : null;
    }
}
