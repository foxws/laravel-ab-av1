<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

enum AbAv1Command: string
{
    /** Search the best CRF for the target quality, then encode with it. */
    case AutoEncode = 'auto-encode';

    /** Search the best CRF for the target quality, without encoding. */
    case CrfSearch = 'crf-search';

    /** Encode samples at a CRF and report their quality and predicted size. */
    case SampleEncode = 'sample-encode';

    /** Encode at a CRF. */
    case Encode = 'encode';

    /** Compare a distorted file to a reference with VMAF. */
    case Vmaf = 'vmaf';

    /** Compare a distorted file to a reference with XPSNR. */
    case Xpsnr = 'xpsnr';

    /**
     * Whether the command compares two files instead of encoding one.
     */
    public function compares(): bool
    {
        return $this === self::Vmaf || $this === self::Xpsnr;
    }

    /**
     * Whether the command encodes samples, which ab-av1 writes to its --temp-dir.
     */
    public function encodesSamples(): bool
    {
        return in_array($this, [self::AutoEncode, self::CrfSearch, self::SampleEncode], true);
    }
}
