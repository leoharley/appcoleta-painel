<?php
if (!function_exists('img_url')) {
    function img_url($img = '') {
        return base_url('assets/img/' . $img);
    }
}