<?php

/**
 * cool class to pass status message and data.
 */
class Orbisius_Just_Write_Result {
    public $status = 0;
    public $msg = '';

    public function success() {
        return !empty($this->status);
    }

    public function error() {
        return empty($this->status);
    }

    public function status($status = null) {
        if (!is_null($status)) {
            $this->status = $status;
        }

        return $this->status;
    }

    public function msg($msg = '') {
        if (!empty($msg)) {
            $this->msg = trim($msg);
        }

        return $this->msg;
    }

    private $data = array();

    /**
     * Data container.
     *
     * @param str $key
     * @param str $val
     * @return mixed
     */
    public function data($key = '', $val = null) {
        if (!empty($key)) {
            if (!is_null($val)) {
                $this->data[$key] = $val;
            }

            return isset($this->data[$key]) ? $this->data[$key] : null;
        } else {
            $val = $this->data;
        }

        return $val;
    }

}
