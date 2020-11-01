<?php

namespace App\Asset;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class ModifiedTimeVersionStrategy implements VersionStrategyInterface
{
    /**
     * Returns the asset version for an asset.
     *
     * @param string $path A path
     *
     * @return string The version string
     */
    public function getVersion($path)
    {
        return filemtime($path);
    }

    /**
     * Applies version to the supplied path.
     *
     * @param string $path A path
     *
     * @return string The versionized path
     */
    public function applyVersion($path)
    {
        return sprintf('%s?v=%s', $path, $this->getVersion($path));
    }
}