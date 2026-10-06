---
section: Usage
order: 2
---

# Testing

Don't run ab-av1 in tests. Fake laravel-media with `Media::fake()`, and let `FakeAbAv1` answer ab-av1:

```php
use Foxws\AbAv1\AbAv1Executable;
use Foxws\AbAv1\Testing\FakeAbAv1;
use Foxws\Media\Facades\Media;
use Illuminate\Support\Facades\Storage;

it('encodes uploads to AV1', function () {
    Storage::fake('media');
    Storage::fake('encoded');

    $fake = FakeAbAv1::respond(Media::fake(), crf: 30.25, score: 95.1);

    EncodeVideo::dispatchSync($video);

    $fake->assertRan(AbAv1Executable::AbAv1, fn (array $arguments) => $arguments[0] === 'auto-encode');
    Storage::disk('encoded')->assertExists("encoded/{$video->id}.mp4");
});
```

Encodes write a placeholder file to their output and report the CRF and score, and `vmaf()` and `xpsnr()` return the score.

To test failures, make the next run fail:

```php
$fake->failNext(AbAv1Executable::AbAv1, 'Error: ffmpeg encode exit code 1');
```

laravel-media's other assertions work too, such as `assertNotRan()` and `assertRanTimes()`. `command()` returns the command line without running it.
