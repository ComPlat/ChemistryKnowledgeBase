<?php
namespace DIQA\FacetedSearch2\Model\Response;

trait DebugInfo {

    public string $debugInfo;

    /**
     * @return string
     */
    public function getDebugInfo(): string
    {
        return $this->debugInfo;
    }

    /**
     * @param string $debugInfo
     * @return DebugInfo
     */
    public function setDebugInfo(string $debugInfo)
    {
        global $fs2gDebugMode;
        if ($fs2gDebugMode ?? false) {
            $this->debugInfo = $debugInfo;
        }
        return $this;
    }

}
