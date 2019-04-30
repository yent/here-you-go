<?php


namespace HereYouGo\Form;

/**
 * Interface DataHolder
 *
 * @package HereYouGo\Form
 */
interface DataHolder {
    /**
     * Get holder path
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Build HTML
     *
     * @return string
     */
    public function getHtml(): string;

    /**
     * Validate own data
     *
     * @param mixed $data
     */
    public function validate($data);
}