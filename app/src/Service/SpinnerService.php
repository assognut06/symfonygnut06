<?php

namespace App\Service;

class SpinnerService
{
    public function showSpinner(): string
    {
        return '<div id="spinner" style="display: none;">
                    <div class="loader"></div>
                </div>';
    }

    public function hideSpinner(): string
    {
        return '<script>
                    document.getElementById("spinner").style.display = "none";
                </script>';
    }

    public function getSpinner(): string
    {
        return '<div id="spinner" style="display: none;">
                    <div class="loader"></div>
                </div>';

    }
}