---
section: Usage
order: 2
---

# Events

Every ab-av1 command dispatches Laravel events, so you can log progress, update a model or notify someone without wrapping each call:

| Event | When | Properties |
| --- | --- | --- |
| `Foxws\AbAv1\Events\EncodingStarted` | Before ab-av1 runs | `$inputPath`, `$options` (the arguments passed to ab-av1) |
| `Foxws\AbAv1\Events\EncodingCompleted` | After ab-av1 succeeds | `$result` (an `EncodingResult`), `$executionTime` in seconds |
| `Foxws\AbAv1\Events\EncodingFailed` | After ab-av1 fails | `$inputPath`, `$exception`, `$executionTime` in seconds |

Listen to them like any other event, for example in a service provider:

```php
use Foxws\AbAv1\Events\EncodingCompleted;
use Illuminate\Support\Facades\Event;

Event::listen(function (EncodingCompleted $event) {
    logger("Encoded at CRF {$event->result->getCRFUsed()}, VMAF {$event->result->getVMAFScore()}");
});
```

To run code after one particular encode is saved to its disk, use `afterSaving()` on the export instead. See [Usage](usage.md#encode-a-file-on-a-disk).
