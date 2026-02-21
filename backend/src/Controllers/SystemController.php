<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class SystemController
{
    public function getClasses(Request $request, Response $response): void
    {
        $classes = [
            [ 'id' => 'bug-hunter', 'label' => 'Bug Hunter', 'icon' => '🐞' ],
            [ 'id' => 'patch-crafter', 'label' => 'Patch Crafter', 'icon' => '🩹' ],
            [ 'id' => 'feature-smith', 'label' => 'Feature Smith', 'icon' => '⚔️' ],
            [ 'id' => 'doc-sage', 'label' => 'Doc Sage', 'icon' => '📜' ],
            [ 'id' => 'ux-alchemist', 'label' => 'UX Alchemist', 'icon' => '⚗️' ],
            [ 'id' => 'ops-ranger', 'label' => 'Ops Ranger', 'icon' => '🛡️' ],
            [ 'id' => 'test-summoner', 'label' => 'Test Summoner', 'icon' => '🧪' ],
            [ 'id' => 'hatchling', 'label' => 'Hatchling', 'icon' => '🐣' ]
        ];

        $response->success($classes);
    }
}
