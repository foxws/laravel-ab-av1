<?php

namespace Foxws\AbAv1\Tests\Unit;

use Foxws\AbAv1\Events\EncodingCompleted;
use Foxws\AbAv1\Events\EncodingFailed;
use Foxws\AbAv1\Events\EncodingStarted;
use Foxws\AbAv1\Support\EncodingResult;
use Illuminate\Support\Facades\Event;

it('dispatches encoding started event', function () {
    Event::fake();

    EncodingStarted::dispatch('/path/to/video.mp4', ['preset' => 'medium']);

    Event::assertDispatched(EncodingStarted::class, function ($event) {
        return $event->inputPath === '/path/to/video.mp4'
            && $event->options['preset'] === 'medium';
    });
});

it('dispatches encoding completed event', function () {
    Event::fake();

    $result = new EncodingResult('/path/to/video.mp4', 'output');
    $executionTime = 120.5;
    EncodingCompleted::dispatch($result, $executionTime);

    Event::assertDispatched(EncodingCompleted::class, function ($event) use ($result, $executionTime) {
        return $event->result === $result
            && $event->executionTime === $executionTime;
    });
});

it('dispatches encoding failed event', function () {
    Event::fake();

    $exception = new \Exception('Test error');
    $inputPath = '/path/to/video.mp4';
    EncodingFailed::dispatch($inputPath, $exception);

    Event::assertDispatched(EncodingFailed::class, function ($event) use ($inputPath, $exception) {
        return $event->inputPath === $inputPath
            && $event->exception === $exception;
    });
});
